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

$agendamento = $_SESSION['agendamento_temporario'];

$servico = $agendamento['servico'];

$preco_total = (float)$agendamento['preco_servico'];

$valor_sinal = (float)$agendamento['valor_sinal'];

$data = date(
    "d/m/Y",
    strtotime($agendamento['data_agendamento'])
);

$hora = substr(
    $agendamento['hora_agendamento'],
    0,
    5
);
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

<input
type="text"
value="<?= htmlspecialchars($user['nome']) ?>"
readonly>

<label>E-mail</label>

<input
type="email"
value="<?= htmlspecialchars($user['email']) ?>"
readonly>

<label>CPF</label>

<input
type="text"
value="<?= htmlspecialchars($user['cpf']) ?>"
readonly>

<label>CEP</label>

<input
type="text"
placeholder="Digite seu CEP">

<p>Forma de pagamento</p>

<div class="forma-pix">

<button
id="btn-pix"
onclick="pixgera()">

PIX

</button>

</div>

<div class="forma-credito">

<button
id="btn-credito"
onclick="creditopaga()">

Crédito

</button>

</div>

<div class="forma-debito">

<button
id="btn-debito"
onclick="debitopaga()">

Débito

</button>

</div>

</div>

<div class="paga" id="peco">

<p>Importante: O pagamento realizado nesta etapa corresponde apenas ao sinal de 30% do valor do serviço. O valor restante deverá ser pago no salão após a realização do atendimento.</p>

<p>

<strong>Serviços:</strong>

<?= htmlspecialchars($servico) ?>

</p>

<p>

<strong>Data:</strong>

<?= $data ?>



<strong>Horário:</strong>

<?= $hora ?>

</p>

 

<p>



R$

<?= number_format(
$valor_sinal,
2,
",",
"."
) ?>

</p>

<div
id="container-pix"
class="metodo-pagamento">

<button class="btn-pagar">

Gerar QR Code

</button>

</div>

<div
id="container-credito"
class="metodo-pagamento paga-credito">

<input
type="text"
placeholder="Número do Cartão">

<button class="btn-pagar">

Pagar com Crédito

</button>

</div>

<div
id="container-debito"
class="metodo-pagamento paga-credito">

<input
type="text"
placeholder="Número do Cartão">

<button class="btn-pagar">

Pagar com Débito

</button>

</div>

</div>

</div>


<script src="paga.js"></script>

</body>

</html>