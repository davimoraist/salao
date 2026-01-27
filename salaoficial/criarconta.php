<?php 
    session_start();
    require_once 'login.php';
    require_once 'conta.clienta.php';

    if (
    isset($_POST['nome']) && !empty($_POST['nome'])&&
    isset($_POST['email']) && !empty($_POST['email']) &&
    isset($_POST['senha']) && !empty($_POST['senha']) 
) 

$usuario = new Usuario();

$nome = $_POST['nome'];
$email = $_POST['email'];
$senha = $_POST['senha'];




?>