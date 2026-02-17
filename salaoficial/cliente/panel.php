 <?php
session_start();
require_once __DIR__ . "/conecte.php";

 if (!isset($_SESSION['id']) || !isset($_SESSION['nome'])) {
    header("Location: cliente.php");
    exit();
}

$id = $_SESSION['id'];

$sql = "SELECT  c.id, c.nome, c.email, p.endereco,  p.telefone, p.cpf, p.data_nascimento, p.como_conheceu,  p.data_cadastro FROM cliente c INNER JOIN pessoais p ON c.id = p.id_cliente WHERE c.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo "Cliente não encontrado.";
    exit();
}

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>mtnaildesigner.com</title>
    <link rel="stylesheet" href="css/panal.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&icon_names=account_circle" />
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
</head>
<body>

<div class="container">
    <div class="logo">
    <img src="imagem/Maria.png" alt="logo da empresa">
</div>
    <div class="menu">
        <span class="material-symbols-outlined" >account_circle</span>
        <h2>Olá, <?= htmlspecialchars(explode(' ', $user['nome'])[0]) ?></h2>
    </div>
    
</div>
    <div class="agendamedo">
        <div class="horario">
            <p>Escolha o melhor horário e confirme seu agendamento abaixo.</p>
            <a href="anamnes.php">agendar</a>
        </div>
        <div class="frase">
            <p>“Um espaço feito para cuidar <br> da beleza e do bem-estar da mulher.”</p>
        </div>
    </div>

    <section class="trabalhos">
    <h2>Nossos Trabalhos</h2>

    <div class="galeria">

        <div class="item">
            <img src="imagem/pe-mao.jpeg" alt="">
            <p>Pé Mão</p>
        </div>

        <div class="item">
            <img src="imagem/Manutenção-banho-gel.jpeg" alt="">
            <p>Manutenção Banho Gel</p>
        </div>

        <div class="item">
            <img src="imagem/Manutenção -alongamento.jpeg" alt="">
            <p>Manutenção Alongamento</p>
        </div>

        <div class="item">
            <img src="imagem/Esmaltação-em-gel.jpeg" alt="">
            <p>Esmaltação em Gel</p>
        </div>

        <div class="item">
            <img src="imagem/decoraco.jpeg" alt="">
            <p>Decoração</p>
        </div>

        <div class="item">
            <img src="imagem/Banho-de-gel.jpeg" alt="">
            <p>Banho de Gel.</p>
        </div>

        <div class="item">
            <img src="imagem/Alongamento.jpeg" alt="">
            <p>Alongamento</p>
        </div>

        <div class="item">
            <img src="imagem/WhatsApp Image 2026-01-27 at 09.52.04.jpeg" alt="">
            <p>Decoração em Alongamento</p>
        </div>

    </div>
</section>

</body>
</html>
