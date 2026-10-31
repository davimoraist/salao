 <?php
// Inicia a sessão de forma segura se ainda não tiver sido iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/conecte.php";

if (!isset($pdo)) {
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
   SANITIZAÇÃO DE INPUTS (APENAS LIMPEZA COM TRIM)
======================================================= */
$diabetes            = trim($_POST['diabetes'] ?? '');
$gestante            = trim($_POST['gestante'] ?? '');
$alergias            = trim($_POST['alergias'] ?? '');
$especificar_alergia = trim($_POST['especificar_alergia'] ?? '');
$cuticula            = trim($_POST['cuticula'] ?? '');
$onicomicose         = trim($_POST['onicomicose'] ?? '');
$especificar_onico   = trim($_POST['especificar_onico'] ?? '');
$medicamento         = trim($_POST['medicamento'] ?? '');
$qual_medicamento    = trim($_POST['qual_medicamento'] ?? '');
$lamina              = trim($_POST['lamina'] ?? '');
$outro_lamina_texto  = trim($_POST['outro_lamina_texto'] ?? '');
$encravada           = trim($_POST['encravada'] ?? '');
$onicofagia          = trim($_POST['onicofagia'] ?? '');
$esporte             = trim($_POST['esporte'] ?? '');
$piscina             = trim($_POST['piscina'] ?? '');

/* 🔎 Verifica se já existe ficha (PDO) */
$verifica = $pdo->prepare("SELECT id FROM ficha_anamnese WHERE id_cliente = ?");
$verifica->execute([$cliente_id]);

if ($verifica->fetch()) {
    $_SESSION['criar_error'] = "Você já enviou sua ficha.";
    header("Location: panel.php");
    exit;
}

/* ✅ INSERT CORRETO E SEGURO COM PDO */
$stmt = $pdo->prepare("INSERT INTO ficha_anamnese 
(id_cliente, diabetes, gestante, alergias, especificar_alergia, cuticula, onicomicose, especificar_onico, medicamento, qual_medicamento, lamina, outro_lamina_texto, encravada, onicofagia, esporte, piscina) 
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$executado = $stmt->execute([
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
]);

/* =======================================================
   TRAVA DE SEGURANÇA 3: VALIDAÇÃO DE EXECUÇÃO DA QUERY
======================================================= */
if ($executado) {
    $_SESSION['sucesso'] = "Ficha enviada com sucesso!";
} else {
    // Registra o erro técnico em log interno do servidor
    error_log("Erro ao salvar ficha de anamnese para o cliente ID: " . $cliente_id);
    $_SESSION['criar_error'] = "❌ Houve um erro interno ao salvar sua ficha. Tente novamente.";
}

header("Location: panel.php");
exit;
?>