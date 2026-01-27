<?php
session_start();
require "verificar.php";

if (isset($_SESSION['idusuario']) && !empty($_SESSION['idusuario'])):
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/adm.css">
    <link rel="shortcut icon" href="favicon_io/favicon.ico" type="image/x-icon">
    <title>ADM DO SALÃO</title> 
</head>
<body>

    <div class="cabeca">
    <h1>Salão de Maria - ADM</h1>

    <span class="nome-usuario">
        <label>Olá!  <?php echo $pessoa; ?></label>
    </span>
    </div>

    <div class="menu">
        <button onclick="mostrarTela('agenda')" class="active">Agenda</button>
        <button onclick="mostrarTela('cliente')">Clientes</button>
        <button onclick="mostrarTela('servicos')">Serviços e preços</button>
        <button onclick="mostrarTela('servico')">Serviços</button>
        <button onclick="mostrarTela('faturamento')">Faturamento</button>
        <button onclick="mostrarTela('grafico')">Gráficos</button>
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
        
        <div id="servico" class="tela">
            <h1>Serviços</h1>
            <p>Configurações do sistema.</p>
        </div>

        <div id="cliente" class="tela"> 
            <h1>clienta</h1>
            <p>Configurações do sistema.</p>
        </div>

        <div id="faturamento" class="tela">
            <h1>Faturamento</h1>
            <p>Configurações do sistema.</p>
        </div>

        <div id="grafico" class="tela">
            <h1>Gráficos</h1>
            <p>Configurações do sistema.</p>
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
 