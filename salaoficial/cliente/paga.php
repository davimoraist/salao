 <?php
session_start();
require_once __DIR__ . "/conecte.php";

if (!isset($_SESSION['id'])) {
    die("Você precisa estar logado.");
}

if (!isset($_SESSION['agendamento_temporario'])) {
    die("Nenhum agendamento encontrado.");
}

$id = (int)$_SESSION['id'];

$stmt = $conn->prepare("
SELECT
    c.nome,
    c.email,
    p.cpf
FROM cliente c
INNER JOIN pessoais p
ON p.id_cliente = c.id
WHERE c.id = ?
LIMIT 1
");

$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    die("Cliente não encontrado.");
}

$agendamento  = $_SESSION['agendamento_temporario'];
$servico      = $agendamento['servico'];
$preco_total  = (float)$agendamento['preco_servico'];
$valor_sinal  = (float)$agendamento['valor_sinal'];

$data = date("d/m/Y", strtotime($agendamento['data_agendamento']));
$hora = substr($agendamento['hora_agendamento'], 0, 5);
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>mtnaildesigner.com</title>
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="css/paga.css">
</head>

<body>

<div id="pagamento">

    <div class="paga" id="dados">

        <label>Nome Completo</label>
        <input type="text" id="cliente_nome" value="<?= htmlspecialchars($user['nome']) ?>" readonly>

        <label>E-mail</label>
        <input type="email" id="cliente_email" value="<?= htmlspecialchars($user['email']) ?>" readonly>

        <label>CPF</label>
        <input type="text" id="cliente_cpf" value="<?= htmlspecialchars($user['cpf']) ?>" readonly>

        <label>CEP</label>
        <input type="text" id="cliente_cep" placeholder="Digite seu CEP">

        <p>Forma de pagamento</p>

        <div class="forma-pix">
            <button id="btn-pix" onclick="pixgera()">PIX</button>
        </div>

        <div class="forma-credito">
            <button id="btn-credito" onclick="creditopaga()">Crédito</button>
        </div>

        <div class="forma-debito">
            <button id="btn-debito" onclick="debitopaga()">Débito</button>
        </div>

    </div>

    <div class="paga" id="peco">

        <p>Importante: O pagamento realizado nesta etapa corresponde apenas ao sinal de 30% do valor do serviço. O valor restante deverá ser pago no salão após a realização do atendimento.</p>

        <p>
            <strong>Serviços:</strong> <?= htmlspecialchars($servico) ?>
        </p>

        <p>
            <strong>Data:</strong> <?= $data ?>
            <strong>Horário:</strong> <?= $hora ?>
        </p>

        <p>
            R$ <?= number_format($valor_sinal, 2, ",", ".") ?>
        </p>
        <input type="hidden" id="valor_sinal" value="<?= $valor_sinal ?>">

        <!-- METODO 1: PIX -->
        <div id="container-pix" class="metodo-pagamento">
            <button class="btn-pagar" id="btn-gerar-pix" onclick="enviarPagamento('PIX')">
                Gerar QR Code Pix
            </button>

            <div id="area-qr-code" style="display: none; margin-top: 15px; text-align: center;">
                <p>Escaneie o QR Code abaixo para pagar:</p>
                <img id="img-qrcode" src="" alt="QR Code Pix" style="max-width: 200px; width: 100%; height: auto;">
                
                <p style="margin-top: 10px;">Ou copie o código Pix abaixo:</p>
                <input type="text" id="input-copia-cola" readonly style="width: 100%; padding: 8px; text-align: center;">
                <button type="button" onclick="copiarPix()" style="margin-top: 8px; padding: 6px 12px; cursor: pointer;">
                    Copiar Código Pix
                </button>
            </div>
        </div>

        <!-- METODO 2: CRÉDITO -->
        <div id="container-credito" class="metodo-pagamento paga-credito">
            <input type="text" id="credito_nome" placeholder="Nome impresso no cartão">
            <input type="text" id="credito_numero" placeholder="Número do Cartão">
            <div style="display: flex; gap: 8px;">
                <input type="text" id="credito_mes" placeholder="MM (Ex: 12)" maxlength="2">
                <input type="text" id="credito_ano" placeholder="AAAA (Ex: 2028)" maxlength="4">
                <input type="text" id="credito_ccv" placeholder="CCV" maxlength="4">
            </div>
            <button class="btn-pagar" id="btn-pagar-credito" onclick="enviarPagamento('CREDIT_CARD')">
                Pagar com Crédito
            </button>
        </div>

        <!-- METODO 3: DÉBITO -->
        <div id="container-debito" class="metodo-pagamento paga-credito">
            <input type="text" id="debito_nome" placeholder="Nome impresso no cartão">
            <input type="text" id="debito_numero" placeholder="Número do Cartão">
            <div style="display: flex; gap: 8px;">
                <input type="text" id="debito_mes" placeholder="MM (Ex: 12)" maxlength="2">
                <input type="text" id="debito_ano" placeholder="AAAA (Ex: 2028)" maxlength="4">
                <input type="text" id="debito_ccv" placeholder="CCV" maxlength="4">
            </div>
            <button class="btn-pagar" id="btn-pagar-debito" onclick="enviarPagamento('DEBIT_CARD')">
                Pagar com Débito
            </button>
        </div>

    </div>

</div>

<script src="paga.js"></script>

</body>

</html>