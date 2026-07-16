 <?php
session_start();
require_once "conecte.php";

// Verifica se o cliente está logado
if (!isset($_SESSION['id'])) {
    header("Location: cliente.php");
    exit;
}

// Aceita apenas requisição POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: agenda.php");
    exit;
}

// Validação do CSRF
if (
    !isset($_POST['csrf_token']) ||
    !isset($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    die("Sessão expirada. Atualize a página e tente novamente.");
}

$id_cliente = (int)$_SESSION['id'];

$servicos = $_POST['servocosalao'] ?? [];
$data = trim($_POST['data'] ?? '');
$hora = trim($_POST['hora'] ?? '');

// Valida serviços
if (empty($servicos)) {
    die("Selecione pelo menos um serviço.");
}

// Valida data e hora
if (empty($data) || empty($hora)) {
    die("Selecione uma data e um horário.");
}

// Limpa os nomes
$servicos = array_map('trim', $servicos);

// Busca os preços reais no banco
$placeholders = implode(",", array_fill(0, count($servicos), "?"));

$sql = "SELECT nome, preco
        FROM servico
        WHERE nome IN ($placeholders)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Erro: ".$conn->error);
}

$tipos = str_repeat("s", count($servicos));
$stmt->bind_param($tipos, ...$servicos);
$stmt->execute();

$result = $stmt->get_result();

$precoTotal = 0;
$servicosValidos = [];

while ($row = $result->fetch_assoc()) {

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

// Salva os dados temporariamente
$_SESSION['agendamento_temporario'] = [

    'id_cliente' => $id_cliente,

    'servico' => implode(", ", $servicosValidos),

    'preco_servico' => $precoTotal,

    'valor_sinal' => round($precoTotal * 0.30, 2),

    'data_agendamento' => $data,

    'hora_agendamento' => $hora,

    'criado_em' => time()

];

// Redireciona para a confirmação
header("Location: pagamendo.php");
exit;