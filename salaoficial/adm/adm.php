<?php
session_start();
require "verificar.php";

if (isset($_SESSION['idusuario']) && !empty($_SESSION['idusuario'])):

    
if (!isset($_SESSION['idusuario']) || empty($_SESSION['idusuario'])) {
    header("Location: index.php");
    exit;
}

// Conexão com o banco
$host = "localhost";
$db = "salao";
$user = "root";
$pass = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
} catch (PDOException $e) {
    die("Erro: " . $e->getMessage());
}

// Pega todos os clientes
 $sql = $pdo->query(" SELECT c.id, c.nome, c.email, p.id_cliente, p.endereco, p.telefone, p.cpf, p.data_nascimento, p.como_conheceu, 	data_cadastro  FROM cliente c INNER JOIN pessoais p ON c.id = p.id_cliente
");

$clientes = $sql->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/adm.css">
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <title>ADM DO SALÃO</title> 
</head>
<body>
    
    <div class="cabeca">
    <h1>Salão da Maria - ADM</h1>

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
            <h1>cliente</h1>
               <table class="tabela-clientes">
    <tr>
        <th>Nome</th>
        <th>Email</th>
        <th>Ver mais</th>
    </tr>

    <?php foreach ($clientes as $index => $user): ?>
    <tr>
        <td><?= htmlspecialchars($user['nome']) ?></td>
        <td><?= htmlspecialchars($user['email']) ?></td>
        <td class="ver-mais">
            <button type="button" onclick="verMais(<?= $index ?>)" class="ver-mais">
                Ver mais
            </button>
        </td>
    </tr>

    <tr id="detalhes-<?= $index ?>" style="display:none;">
        <td colspan="3">
            <strong>Telefone:</strong> <?= htmlspecialchars($user['telefone']) ?><br>
            <strong>Endereço:</strong> <?= htmlspecialchars($user['endereco']) ?><br>
            <strong>CPF:</strong> <?= htmlspecialchars($user['cpf']) ?><br>
            <strong>Data de Nascimento:</strong> <?= htmlspecialchars($user['data_nascimento']) ?><br>
            <strong>Como nos conheceu:</strong> <?= htmlspecialchars($user['como_conheceu']) ?><br>
            <strong>Data de Cadastro:</strong> <?= htmlspecialchars($user['data_cadastro']) ?><br>
        </td>
    </tr>
    <?php endforeach; ?>
</table>


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
 