 <!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>mtnaildesigner.com</title>

    <link rel="stylesheet" href="css/pagamento.css">
</head>
<body>

<div class="paga">
    <h1>Seu agendamento foi marcado</h1>
    <?php
    require_once "conecte.php";
    session_start();
    ?>

    <table>
        <tbody>
            <td>serviço</td>
            <td>serviço</td>
        </tbody>
        <tbody>
            <td>preço</td>
            <td>preço</td>
        </tbody>
            <tbody>
                <td>Total</td>
                <td>Total</td>
            </tbody>
    </table>

<input type="button" value="Pagamento" onclick="pagamento()">

</div>

<script src="pagamento.js"></script>

</body>
</html>