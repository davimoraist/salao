 <?php
session_start();
require_once 'confima.php';
require_once 'globo.php';

if (
    !empty($_POST['nome']) &&
    !empty($_POST['email']) &&
    !empty($_POST['senha'])
) {
    $cliente = new Cliente();

    $nome  = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];

    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

    if ($cliente->criar($nome, $email, $senhaHash)) {
        $_SESSION['nome']  = $nome;
        $_SESSION['email'] = $email;

        header("Location: anamnes.php");
        exit();
    }
}

header("Location: teste2.php");
exit();
