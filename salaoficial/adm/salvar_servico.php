<?php
require_once("login.php");

$nome = $_POST['nome'] ?? '';
$preco = $_POST['preco'] ?? '';

if ($nome == '' || $preco == '') {
    echo "Preencha todos os campos!";
    exit;
}

$sql = $pdo->prepare("INSERT INTO servicos (nome, preco) VALUES (?, ?)");
$sql->execute([$nome, $preco]);

echo "Serviço salvo com sucesso!";
?>