 <?php
session_start();
require_once __DIR__ . "/conecte.php";


if (!isset($_SESSION['id']) || !isset($_SESSION['nome'])) {
    header("Location: cliente.php");
    exit();
}

$id = $_SESSION['id'];

$sql = "SELECT c.id, c.nome, c.email,
               p.endereco, p.telefone, p.cpf,
               p.data_nascimento, p.como_conheceu, p.data_cadastro
        FROM cliente c
        INNER JOIN pessoais p ON c.id = p.id
        WHERE c.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo "Cliente não encontrado.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel do Cliente</title>
    <link rel="stylesheet" href="css/panal.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&icon_names=account_circle" />
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
</head>
<body>

<div class="container">
    <div class="logo">
    <img src="imagem/Maria.png" alt="logo da empresa">
</div>
    <div class="menu">
        <span class="material-symbols-outlined" >account_circle</span>
        <h1>Olá, <?= htmlspecialchars(explode(' ', $user['nome'])[0]) ?></h1>
    </div>
</div>

</body>
</html>
