 <?php
session_start();
require_once "conecte.php";

if (!isset($_SESSION['id'])) {
    die("Você precisa estar logado.");
}

// Verifica se existe um agendamento temporário
if (!isset($_SESSION['agendamento_temporario'])) {
    die("Nenhum agendamento encontrado.");
}

$agendamento = $_SESSION['agendamento_temporario'];

$servicos = array_map('trim', explode(',', $agendamento['servico']));
$precoTotal = (float)$agendamento['preco_servico'];
$valorSinal = (float)$agendamento['valor_sinal'];
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>mtnaildesigner.com</title>

    <link rel="shortcut icon" href="favicon.ico">
    <link rel="stylesheet" href="css/pagamento.css">
</head>

<body>

<div class="paga">

    <h1>Confira seu agendamento</h1>

    <table>

        <thead>

            <tr>
                <th>Serviço</th>
                <th>Preço</th>
            </tr>

        </thead>

        <tbody>

        <?php

        foreach ($servicos as $servico):

            $stmt = $conn->prepare("
                SELECT preco
                FROM servico
                WHERE nome = ?
                LIMIT 1
            ");

            $stmt->bind_param("s",$servico);
            $stmt->execute();

            $resultado = $stmt->get_result()->fetch_assoc();

            $stmt->close();

            $preco = $resultado['preco'] ?? 0;

        ?>

        <tr>

            <td><?= htmlspecialchars($servico) ?></td>

            <td>
                R$
                <?= number_format($preco,2,",",".") ?>
            </td>

        </tr>

        <?php endforeach; ?>

        <tr>

            <td><strong>Total</strong></td>

            <td>

                <strong>

                    R$
                    <?= number_format($precoTotal,2,",",".") ?>

                </strong>

            </td>

        </tr>

        </tbody>

    </table>

    <br>

    <p>

        <strong>Data:</strong>

        <?= date("d/m/Y",strtotime($agendamento['data_agendamento'])) ?>

    </p>

    <p>

        <strong>Horário:</strong>

        <?= htmlspecialchars(substr($agendamento['hora_agendamento'],0,5)) ?>

    </p>

    <br>

    <div class="valor">

        <p>

            Valor Total:

            <strong>

                R$
                <?= number_format($precoTotal,2,",",".") ?>

            </strong>

        </p>

        <p>

            Sinal (30%):

            <strong>

                R$
                <?= number_format($valorSinal,2,",",".") ?>

            </strong>

        </p>

    </div>

    <br>

    <p>

        Para confirmar o agendamento é necessário pagar
        <strong>30%</strong>
        do valor do serviço.

    </p>

    <a href="paga.php">

        Fazer Pagamento

    </a>

</div>

</body>

</html>