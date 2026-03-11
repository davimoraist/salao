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
        <form action="angedadado.php" method="post" class="form-box">

            <h1>Agendamento</h1>
            <div class="linha"></div>

            <div class="none">
                <h2>Nome: <?php echo htmlspecialchars($_SESSION['nome']); ?></h2>
            </div>

            <!-- DATA E HORA (JS VAI PREENCHER) -->
            <input type="hidden" name="data" id="dataSelecionada">
            <input type="hidden" name="hora" id="horaSelecionada">

            <h2 class="titulo">Escolha a data</h2>
            <div class="carrossel">
                <div class="seta" onclick="mover(-1)">◀</div>
                <div class="datas" id="datas"></div>
                <div class="seta" onclick="mover(1)">▶</div>
            </div>

            <h2 class="titulo">Horários disponíveis</h2>
            <div class="horarios" id="horarios"></div>
        
            <div class="servico">
                <h2 class="titulo">Serviço</h2>
            </div>
                <?php if (empty($dados)): ?>
                    <p>Nenhum serviço cadastrado.</p>
                <?php else: ?>
                    <?php foreach ($dados as $servico): ?>
                        <div class="servico-box">
                            
                            <!-- AGORA É RADIO E TEM VALUE -->
                             
                            <label>
                              <input type="checkbox" name="servocosalao[]" value="<?= htmlspecialchars($servico['nome']); ?>">
                                <?= htmlspecialchars($servico['nome']); ?>
                            </label>

                            <p>R$ <?= number_format($servico['preco'], 2, ',', '.'); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

            </div>

            <div class="pagamento">
                <button type="submit" name="pagamento">Pagamento</button>
            </div>

        </form>
    <script src="agenda.js"></script>
</body>
</html>