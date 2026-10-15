 <?php
session_start();
require_once "conecte.php";

// Verifica se o cliente está logado
if (!isset($_SESSION['id'])) {
    // Se for a chamada de finalização (via fetch, depois do pagamento),
    // responde em JSON. Se for acesso comum, redireciona.
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
// aprovado. Não recebe dados de formulário — só lê o que já foi
// validado e guardado na sessão pelo modo normal (abaixo) e grava
// o agendamento definitivo no banco. Sempre responde em JSON.
// ===================================================================
if (isset($_GET['acao']) && $_GET['acao'] === 'finalizar') {
    header('Content-Type: application/json; charset=utf-8');

    if (!isset($_SESSION['agendamento_temporario'])) {
        http_response_code(400);
        echo json_encode(['sucesso' => false, 'erro' => 'nenhum_agendamento_pendente']);
        exit;
    }

    $dados = $_SESSION['agendamento_temporario'];

    // Confere se o agendamento pendente é do mesmo cliente logado
    if ((int)$dados['id_cliente'] !== $id_cliente) {
        http_response_code(401);
        echo json_encode(['sucesso' => false, 'erro' => 'sessao_expirada']);
        exit;
    }

    // Revalida se o horário ainda está livre (pode ter sido ocupado
    // entre a escolha do serviço e a confirmação do pagamento)
    $stmt = $conn->prepare("
        SELECT id
        FROM agendamentos
        WHERE data_agendamento = ?
        AND hora_agendamento = ?
        LIMIT 1
    ");
    $stmt->bind_param("ss", $dados['data_agendamento'], $dados['hora_agendamento']);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->close();
        echo json_encode(['sucesso' => false, 'erro' => 'Este horário já foi reservado por outra pessoa.']);
        exit;
    }
    $stmt->close();

    // Grava o agendamento definitivo no banco
    // ATENÇÃO: confira se os nomes das colunas abaixo batem com a
    // estrutura real da sua tabela `agendamentos`.
    $stmt = $conn->prepare("
        INSERT INTO agendamentos
            (id_cliente, servico, id_servicos, preco_servico, valor_sinal,
             data_agendamento, hora_agendamento, status, criado_em)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    if (!$stmt) {
        error_log("Erro ao preparar INSERT em agendadado.php: " . $conn->error);
        echo json_encode(['sucesso' => false, 'erro' => 'Erro interno ao gravar o agendamento.']);
        exit;
    }

    $stmt->bind_param(
        "isiddssss",
        $dados['id_cliente'],
        $dados['servico'],
        $dados['id_servicos'],
        $dados['preco_servico'],
        $dados['valor_sinal'],
        $dados['data_agendamento'],
        $dados['hora_agendamento'],
        $dados['status'],
        $dados['criado_em']
    );

    if (!$stmt->execute()) {
        error_log("Erro ao executar INSERT em agendadado.php: " . $stmt->error);
        $stmt->close();
        echo json_encode(['sucesso' => false, 'erro' => 'Erro ao gravar o agendamento no banco.']);
        exit;
    }

    $stmt->close();

    // Limpa os dados temporários para não gravar duas vezes
    unset($_SESSION['agendamento_temporario']);

    echo json_encode(['sucesso' => true]);
    exit;
}

// ===================================================================
// MODO ORIGINAL: validação da seleção de serviço/data/hora,
// feita via formulário comum (não é AJAX).
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

// Busca os IDs, nomes e preços reais no banco filtrando por ID
$placeholders = implode(",", array_fill(0, count($servicosIds), "?"));

$sql = "SELECT id, nome, preco
        FROM servico
        WHERE id IN ($placeholders)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Erro: " . $conn->error);
}

// "i" para inteiros (IDs)
$tipos = str_repeat("i", count($servicosIds));
$stmt->bind_param($tipos, ...$servicosIds);
$stmt->execute();

$result = $stmt->get_result();

$precoTotal = 0;
$servicosValidos = [];
$idsServicos = [];

while ($row = $result->fetch_assoc()) {
    $idsServicos[] = (int)$row['id'];
    $servicosValidos[] = $row['nome'];
    $precoTotal += (float)$row['preco'];
}

$stmt->close();

if ($precoTotal <= 0) {
    die("Não foi possível calcular o valor do serviço.");
}

// Verifica se o horário já está ocupado
$stmt = $conn->prepare("
SELECT id
FROM agendamentos
WHERE data_agendamento = ?
AND hora_agendamento = ?
LIMIT 1
");

$stmt->bind_param("ss", $data, $hora);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->close();
    die("Este horário já está reservado.");
}

$stmt->close();

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