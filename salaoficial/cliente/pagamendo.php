 <?php
require_once "conecte.php";
session_start();

if (!isset($_SESSION['id'])) {
    die("Você precisa estar logado.");
}

$id_cliente = (int)$_SESSION['id'];

// Busca o último agendamento
$stmt = $conn->prepare("
    SELECT servico, preco_servico
    FROM agendamentos
    WHERE id_cliente = ?
    ORDER BY id DESC
    LIMIT 1
");

$stmt->bind_param("i", $id_cliente);
$stmt->execute();

$agendamento = $stmt->get_result()->fetch_assoc();

$stmt->close();

if (!$agendamento) {
    die("Nenhum agendamento encontrado.");
}

$servicos_marcados = explode(", ", $agendamento['servico']);
$precoTotal = (float)$agendamento['preco_servico'];

?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>mtnaildesigner.com</title>

    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="css/pagamento.css">
</head>

<body>

<div class="paga">

    <h1>Seu agendamento foi marcado</h1>

    <table>

        <thead>
            <tr>
                <th>Serviço</th>
                <th>Preço</th>
            </tr>
        </thead>

        <tbody>

        <?php foreach ($servicos_marcados as $servico): ?>

            <?php

            $servico = trim($servico);

            $stmtPreco = $conn->prepare("
                SELECT preco
                FROM servico
                WHERE nome = ?
                LIMIT 1
            ");

            $stmtPreco->bind_param("s", $servico);
            $stmtPreco->execute();

            $resultado = $stmtPreco->get_result()->fetch_assoc();

            $preco = $resultado['preco'] ?? 0;

            $stmtPreco->close();

            ?>

            <tr>

                <td><?= htmlspecialchars($servico) ?></td>

                <td>
                    R$ <?= number_format($preco, 2, ',', '.') ?>
                </td>

            </tr>

        <?php endforeach; ?>

        <tr class="linha-total">

            <td><strong>Total</strong></td>

            <td>
                <strong id="total">
                    R$ <?= number_format($precoTotal, 2, ',', '.') ?>
                </strong>
            </td>

        </tr>

        </tbody>

    </table>

    <p>
        Para confirmar o agendamento, é necessário o pagamento de 30% do valor do serviço.
    </p>

    <a href="paga.php">
        Fazer pagamento
    </a>

</div>

</body>
</html>