<?php
include('conexao.php');
$resultado = $conexao->query("SELECT * FROM agendamentos ORDER BY data, hora");
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Agendamentos do Salão</title>
<style>
body {
  font-family: Arial, sans-serif;
  background: #f7f7f7;
  padding: 20px;
}
h2 {
  text-align: center;
}
table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 20px;
}
th, td {
  border: 1px solid #ccc;
  padding: 10px;
  text-align: center;
}
th {
  background-color: #ff69b4;
  color: white;
}
</style>
</head>
<body>
<h2>📋 Agendamentos Marcados</h2>
<table>
  <tr>
    <th>ID</th>
    <th>Cliente</th>
    <th>Data</th>
    <th>Hora</th>
    <th>Serviço</th>
    <th>Observação</th>
  </tr>
  <?php while($linha = $resultado->fetch_assoc()) { ?>
  <tr>
    <td><?= $linha['id'] ?></td>
    <td><?= $linha['nome_cliente'] ?></td>
    <td><?= $linha['data'] ?></td>
    <td><?= $linha['hora'] ?></td>
    <td><?= $linha['servico'] ?></td>
    <td><?= $linha['observacao'] ?></td>
  </tr>
  <?php } ?>
</table>
</body>
</html>
