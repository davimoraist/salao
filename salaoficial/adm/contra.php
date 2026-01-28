<?php
session_start();

require_once 'login.php';
require_once 'usuario.php';

if (
    isset($_POST['email']) && !empty($_POST['email']) &&
    isset($_POST['senha']) && !empty($_POST['senha'])
) {

    $usuario = new Usuario();

    $email = $_POST['email'];
    $senha = $_POST['senha'];

    if ($usuario->login($email, $senha)) {
        header("Location: adm.php");
        exit;
    } else {
        header("Location: index.php");
        exit;
    }

} else {
    header("Location: index.php");
    exit;
}
?>