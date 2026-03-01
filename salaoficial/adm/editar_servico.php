<?php
require_once("login.php");

$id = $_POST['id'] ?? '';
$nome = $_POST['nome'] ?? '';
$preco = $_POST['preco'] ?? '';

if ($nome == '' || $preco == '') {
    echo "Preencha todos os campos!";
    exit;
}

if ($id == '') {

    // 🔵 NOVO SERVIÇO
    $sql = $pdo->prepare("INSERT INTO servicos (nome, preco) VALUES (?, ?)");
    $sql->execute([$nome, $preco]);

    echo "Serviço cadastrado com sucesso!";

} else {

    // 🟢 EDITAR SERVIÇO
    $sql = $pdo->prepare("UPDATE servicos SET nome = ?, preco = ? WHERE id = ?");
    $sql->execute([$nome, $preco, $id]);

    echo "Serviço atualizado com sucesso!";
}
?>