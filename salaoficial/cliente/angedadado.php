 <?php
session_start();
require_once "conecte.php";

if (!isset($pdo) || !($pdo instanceof PDO)) {
    die("Erro: conexão com o banco não encontrada.");
}

// Verifica se o cliente está logado
if (!isset($_SESSION['id'])) {
    if (isset($_GET['acao']) && $_GET['acao'] === 'finalizar') {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(401);
        echo json_encode(['sucesso' => false, 'erro' => 'sessao_expirada']);
        exit;
    }
    header("Location: cliente.php");
    exit;
}

// Aceita apenas requisição POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: agenda.php");
    exit;
}

$id_cliente = (int)$_SESSION['id'];

// ===================================================================
// MODO "FINALIZAR": chamado pelo paga.js (fetch) depois do pagamento
// aprovado. Lê os dados guardados na sessão e grava no banco via PDO.
// ===================================================================
if (isset($_GET['acao']) && $_GET['acao'] === 'finalizar') {
    header('Content-Type: application/json; charset=utf-8');

    if (!isset($_SESSION['agendamento_temporario'])) {
        http_response_code(400);
        echo json_encode(['sucesso' => false, 'erro' => 'nenhum_agendamento_pendente']);
        exit;
    }

    $dados = $_SESSION['agendamento_temporario'];

    if ((int)$dados['id_cliente'] !== $id_cliente) {
        http_response_code(401);
        echo json_encode(['sucesso' => false, 'erro' => 'sessao_expirada']);
        exit;
    }

    // Revalida se o horário ainda está livre com PDO
    $stmt = $pdo->prepare("
        SELECT id
        FROM agendamentos
        WHERE data_agendamento = ?
        AND hora_agendamento = ?
        LIMIT 1
    ");
    $stmt->execute([$dados['data_agendamento'], $dados['hora_agendamento']]);
    
    if ($stmt->fetch()) {
        echo json_encode(['sucesso' => false, 'erro' => 'Este horário já foi reservado por outra pessoa.']);
        exit;
    }

    // Grava o agendamento definitivo no banco com PDO
    $stmt = $pdo->prepare("
        INSERT INTO agendamentos
            (id_cliente, servico, id_servicos, preco_servico, valor_sinal,
             data_agendamento, hora_agendamento, status, criado_em)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    try {
        $stmt->execute([
            $dados['id_cliente'],
            $dados['servico'],
            $dados['id_servicos'],
            $dados['preco_servico'],
            $dados['valor_sinal'],
            $dados['data_agendamento'],
            $dados['hora_agendamento'],
            $dados['status'],
            $dados['criado_em']
        ]);
    } catch (\PDOException $e) {
        error_log("Erro ao executar INSERT em angedadado.php: " . $e->getMessage());
        echo json_encode(['sucesso' => false, 'erro' => 'Erro ao gravar o agendamento no banco.']);
        exit;
    }

    // Limpa os dados temporários
    unset($_SESSION['agendamento_temporario']);

    echo json_encode(['sucesso' => true]);
    exit;
}

// ===================================================================
// MODO ORIGINAL: validação da seleção de serviço/data/hora via formulário
// ===================================================================

// Validação do CSRF
if (
    !isset($_POST['csrf_token']) ||
    !isset($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    die("Sessão expirada. Atualize a página e tente novamente.");
}

// Converte os IDs recebidos para inteiros
$servicosIds = array_map('intval', $_POST['servocosalao'] ?? []);
$data = trim($_POST['data'] ?? '');
$hora = trim($_POST['hora'] ?? '');

// Valida serviços
if (empty($servicosIds)) {
    die("Selecione pelo menos um serviço.");
}

// Valida data e hora
if (empty($data) || empty($hora)) {
    die("Selecione uma data e um horário.");
}

// Busca os IDs, nomes e preços reais no banco filtrando por ID usando PDO com placeholders dinâmicos
$placeholders = implode(',', array_fill(0, count($servicosIds), '?'));
$sql = "SELECT id, nome, preco FROM servico WHERE id IN ($placeholders)";

$stmt = $pdo->prepare($sql);
$stmt->execute($servicosIds);
$resultados = $stmt->fetchAll();

$precoTotal = 0;
$servicosValidos = [];
$idsServicos = [];

foreach ($resultados as $row) {
    $idsServicos[] = (int)$row['id'];
    $servicosValidos[] = $row['nome'];
    $precoTotal += (float)$row['preco'];
}

if ($precoTotal <= 0) {
    die("Não foi possível calcular o valor do serviço.");
}

// Verifica se o horário já está ocupado com PDO
$stmt = $pdo->prepare("
    SELECT id
    FROM agendamentos
    WHERE data_agendamento = ?
    AND hora_agendamento = ?
    LIMIT 1
");
$stmt->execute([$data, $hora]);

if ($stmt->fetch()) {
    die("Este horário já está reservado.");
}

// Salva os dados temporariamente na sessão
$_SESSION['agendamento_temporario'] = [
    'id_cliente'       => $id_cliente,
    'servico'          => implode(", ", $servicosValidos),
    'id_servicos'      => $idsServicos[0] ?? 0,
    'preco_servico'    => $precoTotal,
    'valor_sinal'      => round($precoTotal * 0.30, 2),
    'data_agendamento' => $data,
    'hora_agendamento' => $hora,
    'status'           => 'Pendente',
    'criado_em'        => date('Y-m-d H:i:s')
];

// Redireciona para o pagamento
header("Location: pagamendo.php");
exit;
?>