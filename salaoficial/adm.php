 <?php
session_start();
require "login.php";

if (isset($_SESSION['idusuario']) && !empty($_SESSION['idusuario'])):
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/adm.css">
    <title>ADM DO SALÃO</title> 
</head>
<body>

    <div class="cabeca">
        <h1>Salão de Maria - ADM</h1>
    </div>

    <div class="menu">
        <button onclick="mostrarTela('agenda')" class="active">Agenda</button>
        <button onclick="mostrarTela('servicos')">Serviços e preços</button>
        <button onclick="mostrarTela('dados')">Dados</button>
        <button onclick="mostrarTela('config')">Configurações</button>
        <button><a href="sair.php">Sair</a></button>
    </div>

    <div class="conteudo">

        <div id="agenda" class="tela ativa">
            <h1>Agenda</h1>
            <p>Aqui aparece a agenda do salão.</p>
        </div>

        <div id="servicos" class="tela">
            <h1 class="titulo-servicos">Serviços</h1>
            <h2 class="subtitulo-servicos">Gerenciar Serviços e Preços</h2>
            <div id="servicos-area"></div>
            <button id="fab">+</button>
        </div>

        <div id="dados" class="tela">
            <h1>Dados</h1>
            <p>Informações cadastradas.</p>
        </div>

        <div id="config" class="tela">
            <h1>Configurações</h1>
            <p>Configurações do sistema.</p>
        </div>

    </div>

    <script src="script.js"></script>
</body>
</html>

<?php
else:
    header("Location: index.php");
    exit;
endif;
?>
 