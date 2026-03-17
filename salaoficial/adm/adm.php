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
    <button onclick="mostrarTela('servico')">Histórico</button>
    <button onclick="mostrarTela('servicos')">Cadastro de Serviços</button>
    <button onclick="mostrarTela('faturamento')">Faturamento</button>
    <button onclick="mostrarTela('grafico')">Gráficos</button>
    <button onclick="mostrarTela('config')">Configurações</button>
    <button onclick="window.location='sair.php'">Sair</button>
</div>

<div class="conteudo">

    <div id="agenda" class="tela ativa">
        <?php
        // Adicionado a.id para o botão finalizar funcionar
        $sql = $pdo->query("SELECT 
            a.id,
            c.nome,
            a.servico,
            a.data_agendamento,
            a.hora_agendamento,
            a.status,
            a.id_cliente
        FROM agendamentos a
        LEFT JOIN cliente c ON a.id_cliente = c.id
        WHERE a.status = 'pendente'
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
                        <button type="button" onclick="mostrarTela('cliente', <?php echo $agendamento['id_cliente']; ?>)">
                            Ver mais
                        </button>
                    </td>

                    <td>
                        <button type="button" 
                                onclick="if(confirm('Finalizar este serviço?')){ location.href='finalizar_agendamento.php?id=<?php echo $agendamento['id']; ?>'; }" >
                            Finalizar
                        </button>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <div id="servicos" class="tela">
        <h1 class="titulo-servicos">Cadastro de Serviços</h1>
        <h2 class="subtitulo-servicos">Gerenciar Serviços e Preços</h2>
        <div id="servicos-area"></div>
        <button id="fab">+</button>
    </div>

    <div id="servico" class="tela">
        <h1>Histórico do Cliente</h1>

    </div>

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

    <div id="faturamento" class="tela"><h1>Faturamento</h1></div>
    <div id="grafico" class="tela"><h1>Gráficos</h1></div>
    <div id="config" class="tela"><h1>Configurações</h1></div>

</div>

<script src="script.js"></script>

</body>
</html>