 <?php
session_start();
require "verificar.php";

if (!isset($_SESSION['idusuario']) || empty($_SESSION['idusuario'])) {
    header("Location: index.php");
    exit;
}

// conexão banco
$host = "localhost";
$db = "salao";
$user = "root";
$pass = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
} catch (PDOException $e) {
    die("Erro: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
<link rel="stylesheet" href="css/adm.css">

<title>ADM DO SALÃO</title>

</head>

<body>

<div class="cabeca">

<h1>Salão da Maria - ADM</h1>

<span class="nome-usuario">
Olá! <?php echo $pessoa; ?>
</span>

</div>


<div class="menu">

<button onclick="mostrarTela('agenda')" class="active">Agenda</button>

<button onclick="mostrarTela('cliente')">Clientes</button>

<button onclick="mostrarTela('servicos')">Cadastro de Serviços</button>

<button onclick="mostrarTela('servico')">Serviços</button>

<button onclick="mostrarTela('faturamento')">Faturamento</button>

<button onclick="mostrarTela('grafico')">Gráficos</button>

<button onclick="mostrarTela('config')">Configurações</button>

<button onclick="window.location='sair.php'">Sair</button>

</div>



<div class="conteudo">

<!-- AGENDA -->

<div id="agenda" class="tela ativa">

<?php

$sql = $pdo->query("SELECT 
c.nome,
a.servico,
a.data_agendamento,
a.hora_agendamento,
a.status
FROM agendamentos a
LEFT JOIN cliente c
ON a.id_cliente = c.id
ORDER BY a.data_agendamento, a.hora_agendamento");

$agendamentos = $sql->fetchAll(PDO::FETCH_ASSOC);

?>

<h2>Agendamentos</h2>

<table class="tabela-agendamentos">

<thead>

<tr>
<th>Cliente</th>
<th>Serviço</th>
<th>Data</th>
<th>Hora</th>
<th>Status</th>
<th>Ver mais</th>
<th>Finalizar</th>
</tr>

</thead>

<tbody>

<?php foreach($agendamentos as $agendamento){ ?>

<tr>

<td><?php echo $agendamento['nome']; ?></td>

<td><?php echo $agendamento['servico']; ?></td>

<td><?php echo $agendamento['data_agendamento']; ?></td>

<td><?php echo $agendamento['hora_agendamento']; ?></td>

<td><?php echo $agendamento['status']; ?></td>

<td>
<button onclick="mostrarTela('cliente')">Ver mais</button>
</td>

<td>
<button>Finalizar</button>
</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>



<!-- CADASTRO SERVIÇOS -->

<div id="servicos" class="tela">

<h1 class="titulo-servicos">Cadastro de Serviços</h1>

<h2 class="subtitulo-servicos">Gerenciar Serviços e Preços</h2>

<div id="servicos-area"></div>

<button id="fab">+</button>

</div>



<!-- SERVIÇOS -->

<div id="servico" class="tela">

<h1>Serviços</h1>

<p>Lista de serviços cadastrados.</p>

</div>



<!-- CLIENTES -->

<div id="cliente" class="tela">

<h1>Clientes</h1>

<table class="tabela-clientes">

<thead>

<tr>

<th>Nome</th>

<th>Email</th>

<th>Ver mais</th>

</tr>

</thead>

<tbody id="corpo-tabela-clientes">

</tbody>

</table>

</div>



<!-- FATURAMENTO -->

<div id="faturamento" class="tela">

<h1>Faturamento</h1>

<p>Controle financeiro do salão.</p>

</div>



<!-- GRAFICO -->

<div id="grafico" class="tela">

<h1>Gráficos</h1>

<p>Estatísticas do salão.</p>

</div>



<!-- CONFIG -->

<div id="config" class="tela">

<h1>Configurações</h1>

<p>Configurações do sistema.</p>

</div>



</div>

<script src="script.js"></script>

</body>
</html>