 <?php
require_once "conecte.php";
session_start();

if (!isset($_SESSION['id'])) {
    header("Location: cliente.php");
    exit;
}

$id_cliente = $_SESSION['id'];

// Busca o último agendamento do cliente
$agendamento = $conn->query("SELECT servico, preco_servico FROM agendamentos WHERE id_cliente = $id_cliente ORDER BY id DESC LIMIT 1")->fetch_assoc();

if (!$agendamento) {
    die("Nenhum agendamento encontrado.");
}

// Transforma a string de serviços (ex: "Manicure, Escova") em um array
$servicos_marcados = explode(", ", $agendamento['servico']);
$precoTotal = $agendamento['preco_servico'];

// Tabela de preços de referência (de acordo com a sua primeira imagem)
$tabela_precos = [
    "Manicure" => 25.00,
    "Coloração / Pintura de Cabelo" => 10.00,
    "Hidratação Capilar" => 40.00,
    "Escova" => 35.00
];
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
                <th>serviço</th>
                <th>preço</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            // Loop para listar cada serviço marcado individualmente
            foreach ($servicos_marcados as $servico) {
                // Pega o preço da tabela de referência. Se não achar, assume 0.00
                $preco_individual = isset($tabela_precos[$servico]) ? $tabela_precos[$servico] : 0.00;
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($servico); ?></td>
                    <td>R$ <?php echo number_format($preco_individual, 2, ',', '.'); ?></td>
                </tr>
            <?php } ?>
            
            <tr class="linha-total">
                <td><strong>Total</strong></td>
                <td><strong>R$ <?php echo number_format($precoTotal, 2, ',', '.'); ?></strong></td>
            </tr>
        </tbody>
    </table>

    <input type="button" value="Pagamento" onclick="pagamento()">
</div>

<script src="pagamento.js"></script>

</body>
</html>