<?php

require_once("login.php");

$id = $_GET['id'];

$sql = $pdo->prepare("UPDATE agendamentos SET status='finalizado' WHERE id=?");
$sql->execute([$id]);

header("Location: adm.php");

?>