 <?php
// Inicia a sessão de forma segura se ainda não tiver sido iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/conecte.php";

if (!isset($conn)) {
    die("Erro: conexão com o banco não encontrada.");
}

// Verifica se o usuário está devidamente autenticado
if (!isset($_SESSION['id'])) {
    header("Location: cliente.php");
    exit;
}

/* =======================================================
   TRAVA DE SEGURANÇA 1: VALIDAÇÃO DE TOKEN CSRF
======================================================= */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['criar_error'] = '❌ Requisição inválida ou expirada.';
        header("Location: panel.php");
        exit;
    }
}

$cliente_id = $_SESSION['id'];

/* =======================================================
   TRAVA DE SEGURANÇA 2: SANITIZAÇÃO DE INPUTS (ANTI-XSS)
======================================================= */
// Aplica escape estrito em todos os dados textuais recebidos pelo POST
$diabetes            = htmlspecialchars(trim($_POST['diabetes'] ?? ''), ENT_QUOTES, 'UTF-8');
$gestante            = htmlspecialchars(trim($_POST['gestante'] ?? ''), ENT_QUOTES, 'UTF-8');
$alergias            = htmlspecialchars(trim($_POST['alergias'] ?? ''), ENT_QUOTES, 'UTF-8');
$especificar_alergia = htmlspecialchars(trim($_POST['especificar_alergia'] ?? ''), ENT_QUOTES, 'UTF-8');
$cuticula            = htmlspecialchars(trim($_POST['cuticula'] ?? ''), ENT_QUOTES, 'UTF-8');
$onicomicose         = htmlspecialchars(trim($_POST['onicomicose'] ?? ''), ENT_QUOTES, 'UTF-8');
$especificar_onico   = htmlspecialchars(trim($_POST['especificar_onico'] ?? ''), ENT_QUOTES, 'UTF-8');
$medicamento         = htmlspecialchars(trim($_POST['medicamento'] ?? ''), ENT_QUOTES, 'UTF-8');
$qual_medicamento    = htmlspecialchars(trim($_POST['qual_medicamento'] ?? ''), ENT_QUOTES, 'UTF-8');
$lamina              = htmlspecialchars(trim($_POST['lamina'] ?? ''), ENT_QUOTES, 'UTF-8');
$outro_lamina_texto  = htmlspecialchars(trim($_POST['outro_lamina_texto'] ?? ''), ENT_QUOTES, 'UTF-8');
$encravada           = htmlspecialchars(trim($_POST['encravada'] ?? ''), ENT_QUOTES, 'UTF-8');
$onicofagia          = htmlspecialchars(trim($_POST['onicofagia'] ?? ''), ENT_QUOTES, 'UTF-8');
$esporte             = htmlspecialchars(trim($_POST['esporte'] ?? ''), ENT_QUOTES, 'UTF-8');
$piscina             = htmlspecialchars(trim($_POST['piscina'] ?? ''), ENT_QUOTES, 'UTF-8');

/* 🔎 Verifica se já existe ficha */
$verifica = $conn->prepare("SELECT id FROM ficha_anamnese WHERE id_cliente = ?");
$verifica->bind_param("i", $cliente_id);
$verifica->execute();
$verifica->store_result();

if ($verifica->num_rows > 0) {
    $_SESSION['criar_error'] = "Você já enviou sua ficha.";
    $verifica->close();
    header("Location: panel.php");
    exit;
}
$verifica->close();

/* ✅ INSERT CORRETO E SEGURO */
$stmt = $conn->prepare("INSERT INTO ficha_anamnese 
(id_cliente, diabetes, gestante, alergias, especificar_alergia, cuticula, onicomicose, especificar_onico, medicamento, qual_medicamento, lamina, outro_lamina_texto, encravada, onicofagia, esporte, piscina) 
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$stmt->bind_param(
    "isssssssssssssss",
    $cliente_id,
    $diabetes,
    $gestante,
    $alergias,
    $especificar_alergia,
    $cuticula,
    $onicomicose,
    $especificar_onico,
    $medicamento,
    $qual_medicamento,
    $lamina,
    $outro_lamina_texto,
    $encravada,
    $onicofagia,
    $esporte,
    $piscina
);

/* =======================================================
   TRAVA DE SEGURANÇA 3: VALIDAÇÃO DE EXECUÇÃO DA QUERY
======================================================= */
if ($stmt->execute()) {
    $_SESSION['sucesso'] = "Ficha enviada com sucesso!";
} else {
    // Registra o erro técnico em log interno do servidor, mas sem exibi-lo ao cliente
    error_log("Erro ao salvar ficha de anamnese: " . $stmt->error);
    $_SESSION['criar_error'] = "❌ Houve um erro interno ao salvar sua ficha. Tente novamente.";
}

$stmt->close();
$conn->close();

header("Location: panel.php");
exit;
?>