 <?php
session_start();

if (!isset($_SESSION['idcliente'])) {
    header("Location: cliente.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>mtnaildesigner.com</title>
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="css/panal.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=account_circle" />
</head>
<body>
    <div class="container">
        <div class="menu">
            <span class="material-symbols-outlined">account_circle</span>
            <a href="agenda.php">agenda</a>
            <h1>Olá <?= htmlspecialchars($_SESSION['nome']) ?></h1>
        </div>
    </div>
     
</body>
</html>