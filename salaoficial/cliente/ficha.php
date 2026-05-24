 <?php
session_start();
require_once __DIR__ . "/conecte.php";

if (!isset($_SESSION['id'])) {
    header("Location: cliente.php");
    exit;
}

$cliente_id = $_SESSION['id'];

$diabetes = $_POST['diabetes'] ?? '';
$gestante = $_POST['gestante'] ?? '';
$alergias = $_POST['alergias'] ?? '';
$especificar_alergia = $_POST['especificar_alergia'] ?? '';
$cuticula = $_POST['cuticula'] ?? '';
$onicomicose = $_POST['onicomicose'] ?? '';
$especificar_onico = $_POST['especificar_onico'] ?? '';
$medicamento = $_POST['medicamento'] ?? '';
$qual_medicamento = $_POST['qual_medicamento'] ?? '';
$lamina = $_POST['lamina'] ?? '';
$outro_lamina_texto = $_POST['outro_lamina_texto'] ?? '';
$encravada = $_POST['encravada'] ?? '';
$onicofagia = $_POST['onicofagia'] ?? '';
$esporte = $_POST['esporte'] ?? '';
$piscina = $_POST['piscina'] ?? '';

/* 🔎 Verifica se já existe ficha */
$verifica = $conn->prepare("SELECT id FROM ficha_anamnese WHERE id_cliente = ?");
$verifica->bind_param("i", $cliente_id);
$verifica->execute();
$verifica->store_result();

if ($verifica->num_rows > 0) {
    $_SESSION['criar_error'] = "Você já enviou sua ficha.";
    header("Location: panel.php");
    exit;
}

/* ✅ INSERT CORRETO */
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

$stmt->execute();

$_SESSION['sucesso'] = "Ficha enviada com sucesso!";
header("Location: panel.php");
exit;
?>