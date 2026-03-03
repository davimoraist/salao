 <?php
session_start();
require_once __DIR__ . "/conecte.php"; // sua conexão mysqli

// Verifica login
if (!isset($_SESSION['id'])) {
    header("Location: cliente.php");
    exit;
}

// Pega os serviços do banco
$sql = "SELECT nome, preco FROM servicos ORDER BY id DESC";
$result = $conn->query($sql);

$dados = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $dados[] = $row;
    }
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>mtnaildesigner.com</title>
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="css/agenda.css">
</head>
<body>
    <form action="#">
        <h1>agendamento</h1>
        <div class="linha"></div>
        <div class="none">
            <H2>Nome: <?php echo htmlspecialchars($_SESSION['nome']); ?></H2>
        </div>
        <H2>Escolha a data</H2>
        <div class="carrossel">
            <div class="seta" onclick="mover(-1)">◀</div>
            <div class="datas" id="datas"></div>
            <div class="seta" onclick="mover(1)">▶</div>
        </div>
        <h2>Horários disponíveis</h2>
        <div class="horarios" id="horarios"></div>
        <div class="servico">
            <h2>Serviço</h2>

    <?php if (empty($dados)): ?>
        <p>Nenhum serviço cadastrado.</p>
    <?php else: ?>
        <?php foreach ($dados as $servico): ?>
            <div class="servico-box">
                <h3><input type="checkbox" name="servocosalao" id="servocosalao"><?= htmlspecialchars($servico['nome']); ?></h3>
                <p>R$ <?= number_format($servico['preco'], 2, ',', '.'); ?></p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

         <div class="pagamento"><button onclick="pagamento()" type="submit" name="pagamento">Pagemento</button></div>
        
    </form>
    <script src="agenda.js"></script>
</body>
</html>