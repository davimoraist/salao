<?php
require_once("login.php");

$id = $_POST['id'] ?? '';

if ($id == '') {
    echo "ID inválido!";
    exit;
}

$sql = $pdo->prepare("DELETE FROM servicos WHERE id = ?");
$sql->execute([$id]);

echo "Serviço excluído com sucesso!";
?>