 <?php
// Inicia a sessão de forma segura se ainda não tiver sido iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/conecte.php"; // sua conexão mysqli

// Verifica login
if (!isset($_SESSION['id'])) {
    header("Location: cliente.php");
    exit;
}

// Garante a existência e a persistência do Token CSRF para validar o envio do formulário
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Pega os serviços do banco
$sql = "SELECT nome, preco FROM servico ORDER BY id DESC";
$result = $conn->query($sql);

$dados = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $dados[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
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
                <h2>Nome: <?php echo htmlspecialchars($_SESSION['nome'], ENT_QUOTES, 'UTF-8'); ?></h2>
            </div>

            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

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
                        <label>
                          <input type="checkbox" name="servocosalao[]" value="<?= htmlspecialchars($servico['nome'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?= htmlspecialchars($servico['nome'], ENT_QUOTES, 'UTF-8'); ?>
                        </label>
                        <p>R$ <?= number_format($servico['preco'], 2, ',', '.'); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <div class="pagamento">
                <button type="submit" name="pagamento">Pagamento</button>
            </div>

        </form>
    <script src="agenda.js"></script>
</body>
</html>