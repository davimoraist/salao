<?php
$host = "localhost";
$user = "root"; // Padrão do XAMPP
$pass = "";     // Padrão do XAMPP (vazio)
$banco = "test";

$conexao = new mysqli($host, $user, $pass, $banco);

if ($conexao->connect_error) {
    die(json_encode(['success' => false, 'error' => 'Erro na conexão: ' . $conexao->connect_error]));
}