<?php
$servidor = "localhost";
$usuario = "root";
$senha = "";
$banco = "salao_db";

// Criar conexão
$conexao = new mysqli($servidor, $usuario, $senha, $banco);

// Verificar conexão
if ($conexao->connect_error) {
    die("Erro na conexão: " . $conexao->connect_error);
}
//echo "Conexão bem-sucedida!";
?>
