<?php
require_once("login.php");

$id = $_POST['id'] ?? '';
$nome = $_POST['nome'] ?? '';
$preco = $_POST['preco'] ?? '';

if ($id == '' || $nome == '' || $preco == '') {
    echo "Preencha todos os campos!";
    exit;
}

$sql = $pdo->prepare("UPDATE servicos SET nome = ?, preco = ? WHERE id = ?");
$sql->execute([$nome, $preco, $id]);

echo "Serviço atualizado com sucesso!";
?>