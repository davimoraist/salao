 <?php
session_start();
require_once __DIR__ . "/conecte.php";

if (!isset($_SESSION['id'])) {
    die("Você precisa estar logado.");
}

$id = (int) $_SESSION['id'];

// Captura o ID do agendamento que veio pela URL
$id_agendamento = isset($_GET['agendamento_id']) ? (int)$_GET['agendamento_id'] : 0;

if ($id_agendamento > 0) {
    // Busca EXATAMENTE o agendamento gerado no passo anterior
    $sql = "SELECT
                c.id,
                c.nome,
                c.email,
                p.cpf,
                a.servico,
                a.preco_servico,
                a.data_agendamento,
                a.hora_agendamento
            FROM cliente c
            INNER JOIN pessoais p
                ON c.id = p.id_cliente
            LEFT JOIN agendamentos a
                ON c.id = a.id_cliente AND a.id = ?
            WHERE c.id = ?
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Erro na consulta: " . $conn->error);
    }
    $stmt->bind_param("ii", $id_agendamento, $id);
} else {
    // Fallback de segurança: caso o cliente acesse sem ID na URL, 
    // busca o agendamento mais recente ordenando por "a.id DESC" (evita bug de ordenação por data)
    $sql = "SELECT
                c.id,
                c.nome,
                c.email,
                p.cpf,
                a.servico,
                a.preco_servico,
                a.data_agendamento,
                a.hora_agendamento
            FROM cliente c
            INNER JOIN pessoais p
                ON c.id = p.id_cliente
            LEFT JOIN agendamentos a
                ON c.id = a.id_cliente
            WHERE c.id = ?
            ORDER BY a.id DESC 
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Erro na consulta: " . $conn->error);
    }
    $stmt->bind_param("i", $id);
}

$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    die("Nenhum dado encontrado.");
}
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
            <label for="nome">Nome Completo</label>
            <input
                type="text"
                name="nome"
                id="nome"
                value="<?= htmlspecialchars($user['nome']) ?>"
                readonly> <label for="email">Seu E-mail</label>
            <input
                type="email"
                name="email"
                id="email"
                value="<?= htmlspecialchars($user['email']) ?>"
                readonly>

            <label for="cpf">CPF</label>
            <input
                type="text"
                name="cpf"
                id="cpf"
                value="<?= htmlspecialchars($user['cpf']) ?>"
                readonly>

            <label for="cep">CEP</label>
            <input
                type="text"
                name="cep"
                id="cep">

            <p>Qual é a forma de pagamento?</p>
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
            <p><strong>Total:</strong> R$ <span id="total"><?= $user['preco_servico'] ? number_format($user['preco_servico'], 2, ',', '.') : '0,00' ?></span></p>
            
            <p><strong>Serviço:</strong> <?= $user['servico'] ? htmlspecialchars($user['servico']) : 'Nenhum agendamento ativo' ?></p>
            
            <p><strong>Horário e Data:</strong> 
                <?php if ($user['data_agendamento']): ?>
                    <?= date('d/m/Y', strtotime($user['data_agendamento'])) ?> às <?= htmlspecialchars(substr($user['hora_agendamento'], 0, 5)) ?>
                <?php else: ?>
                    Sem agendamento marcado
                <?php endif; ?>
            </p>

            <div id="container-pix" class="metodo-pagamento">
                <button class="btn-pagar">Gerar QR Code</button>
            </div>

            <div id="container-credito" class="metodo-pagamento paga-credito">
                <input type="text" name="numero" id="numero" placeholder="Número do Cartão de Crédito">
                <button class="btn-pagar" style="margin-top: 15px;">Pagar com Crédito</button>
            </div>

            <div id="container-debito" class="metodo-pagamento paga-credito">
                <input type="text" name="numero-debito" id="numero-debito" placeholder="Número do Cartão de Débito">
                <button class="btn-pagar" style="margin-top: 15px;">Pagar com Débito</button>
            </div>
        </div>
    </div>
    <script src="paga.js"></script>
</body>

</html>