 <?php
session_start();
require_once "conecte.php";

if (!isset($_SESSION['id'])) {
    die("Você precisa estar logado.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $id_cliente = (int)$_SESSION['id'];

    if (
        !isset($_POST['csrf_token']) ||
        !isset($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        die("Sessão expirada.");
    }

    $servicos = $_POST['servocosalao'] ?? [];

    if (empty($servicos)) {
        die("Selecione um serviço.");
    }

    // Limpa os nomes
    $servicos = array_map('trim', $servicos);

    // Texto dos serviços
    $servicosTexto = implode(", ", $servicos);

    // Calcula o preço total buscando na tabela de serviços do banco
    $placeholders = implode(",", array_fill(0, count($servicos), "?"));
    $sqlPreco = "SELECT SUM(preco) AS total FROM servico WHERE nome IN ($placeholders)";

    $stmtPreco = $conn->prepare($sqlPreco);
    $tipos = str_repeat("s", count($servicos));
    $stmtPreco->bind_param($tipos, ...$servicos);
    $stmtPreco->execute();
    $resultado = $stmtPreco->get_result()->fetch_assoc();
    $stmtPreco->close();

    $precoTotal = (float)$resultado['total'];

    if ($precoTotal <= 0) {
        die("Preço dos serviços inválido.");
    }

    $data = trim($_POST['data']);
    $hora = trim($_POST['hora']);

    if (empty($data) || empty($hora)) {
        die("Informe a data e a hora.");
    }

    // Verifica se o horário já está reservado no banco
    $verifica = $conn->prepare("
        SELECT id
        FROM agendamentos
        WHERE data_agendamento = ?
        AND hora_agendamento = ?
    ");
    $verifica->bind_param("ss", $data, $hora);
    $verifica->execute();
    $verifica->store_result();

    if ($verifica->num_rows > 0) {
        $verifica->close();
        die("Este horário já está reservado.");
    }
    $verifica->close();

    // ==========================================
    // EM VEZ DE SALVAR NO BANCO, SALVAMOS NA SESSÃO
    // ==========================================
    $_SESSION['agendamento_temporario'] = [
        'servico' => $servicosTexto,
        'preco_servico' => $precoTotal,
        'data_agendamento' => $data,
        'hora_agendamento' => $hora
    ];

    // Redireciona direto para a página de pagamento
    header("Location: pagamendo.php");
    exit();
}