<?php

$localhost = "localhost";
$user = "root";
$passw = "";
$banco = "salão";

// Conectar ao banco
$conecta = mysqli_connect($localhost, $user, $passw, $banco);

// Verificar conexão
if (mysqli_connect_errno()) {
    echo "Erro ao conectar ao banco: " . mysqli_connect_error();
    exit();
}

// Consultar tabela
$sql = mysqli_query($conecta, "SELECT * FROM usuarios");

// Verificar se a consulta deu certo
if (!$sql) {
    echo "Erro na consulta SQL: " . mysqli_error($conecta);
    exit();
}

// Mostrar quantidade de registros
echo mysqli_num_rows($sql);

?>
