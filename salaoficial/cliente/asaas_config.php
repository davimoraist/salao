 <?php
session_start();
require_once "conecte.php"; // deve definir $pdo (PDO) já conectado

// Oculta avisos visuais do PHP para evitar quebrar a resposta JSON do JavaScript
ini_set('display_errors', 0);
error_reporting(E_ALL);

header("Content-Type: application/json; charset=utf-8");

if (!isset($pdo) || !($pdo instanceof PDO)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro crítico: Conexão PDO não encontrada.']);
    exit;
}

// Configuração do Asaas
define('ASAAS_API_URL', 'https://sandbox.asaas.com/api/v3'); // Mude para https://www.asaas.com/api/v3 em produção
$apiKey = 'coloca seu api aqui!'; // Insira sua API Key do Asaas aqui

function criarCpfFicticioForcado() {
    $n = [];
    for ($i = 0; $i < 9; $i++) {
        $n[$i] = rand(0, 9);
    }

    // Primeiro dígito verificador
    $d1 = 0;
    for ($i = 0; $i < 9; $i++) {
        $d1 += $n[$i] * (10 - $i);
    }
    $d1 = 11 - ($d1 % 11);
    if ($d1 >= 10) $d1 = 0;

    // Segundo dígito verificador
    $d2 = 0;
    for ($i = 0; $i < 9; $i++) {
        $d2 += $n[$i] * (11 - $i);
    }
    $d2 += $d1 * 2;
    $d2 = 11 - ($d2 % 11);
    if ($d2 >= 10) $d2 = 0;

    $todosDigitos = array_merge($n, [$d1, $d2]);
    return implode('', $todosDigitos);
}

// ====== FUNÇÃO QUE GRAVA O AGENDAMENTO + O PAGAMENTO NO BANCO (PDO) ======
function salvarAgendamentoNoBanco(PDO $pdo, $metodo, $paymentIdAsaas = null) {
    if (!isset($_SESSION['agendamento_temporario'])) {
        return false;
    }

    $dados = $_SESSION['agendamento_temporario'];

    try {
        // --- 1. Grava o agendamento ---
        $sql = "INSERT INTO agendamentos 
                (id_cliente, servico, preco_servico, data_agendamento, hora_agendamento, status, criado_em, id_servicos) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $pdo->prepare($sql);

        $status = 'Confirmado';
        $criado_em = date('Y-m-d H:i:s');
        $id_servico = $dados['id_servicos'] ?? 0;

        $sucesso = $stmt->execute([
            $dados['id_cliente'],
            $dados['servico'],
            $dados['preco_servico'],
            $dados['data_agendamento'],
            $dados['hora_agendamento'],
            $status,
            $criado_em,
            $id_servico
        ]);

        $idAgendamento = $pdo->lastInsertId();

        if (!$sucesso) {
            error_log("Erro ao executar INSERT em agendamentos.");
            return false;
        }

        // --- 2. Grava o pagamento (valor pago agora x valor restante) ---
        $valorTotal    = (float)($dados['preco_servico'] ?? 0);
        $valorPago     = (float)($dados['valor_sinal'] ?? 0);
        $valorRestante = round($valorTotal - $valorPago, 2);
        $statusPagamento = 'Confirmado';

        $sqlPagamento = "INSERT INTO pagamentos
                (id_agendamento, id_cliente, metodo_pagamento, payment_id_asaas,
                 valor_total, valor_pago, valor_restante, status_pagamento, criado_em)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmtPagamento = $pdo->prepare($sqlPagamento);
        $stmtPagamento->execute([
            $idAgendamento,
            $dados['id_cliente'],
            $metodo,
            $paymentIdAsaas,
            $valorTotal,
            $valorPago,
            $valorRestante,
            $statusPagamento,
            $criado_em
        ]);

        unset($_SESSION['agendamento_temporario']);

        return true;
    } catch (\PDOException $e) {
        error_log("Erro no banco de dados (salvarAgendamentoNoBanco): " . $e->getMessage());
        return false;
    }
}

// ====== ROTA: CHECAR STATUS DO PIX (chamada via GET pelo polling do JS) ======
$acao = filter_input(INPUT_GET, 'acao', FILTER_DEFAULT);

if ($acao === 'checar_status') {
    $paymentId = filter_input(INPUT_GET, 'payment_id', FILTER_DEFAULT);

    if (!$paymentId) {
        echo json_encode(['pago' => false]);
        exit;
    }

    $ch = curl_init(ASAAS_API_URL . "/payments/{$paymentId}");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'User-Agent: SistemaAgendamento',
            'access_token: ' . $apiKey
        ]
    ]);

    $res = json_decode(curl_exec($ch), true);
    curl_close($ch);

    $status = $res['status'] ?? '';
    if (in_array($status, ['RECEIVED', 'CONFIRMED', 'RECEIVED_IN_CASH'])) {
        $metodoSalvo = $_SESSION['agendamento_temporario']['metodo_pagamento'] ?? 'PIX';
        $gravou = salvarAgendamentoNoBanco($pdo, $metodoSalvo, $paymentId);
        echo json_encode(['pago' => true, 'salvo_no_banco' => $gravou]);
    } else {
        echo json_encode(['pago' => false, 'status' => $status]);
    }
    exit;
}

// ====== FLUXO DE CRIAÇÃO DE COBRANÇA (POST vindo de enviarPagamento no JS) ======

$metodo       = filter_input(INPUT_POST, 'metodo', FILTER_DEFAULT) ?? 'PIX';
$nomeCliente  = filter_input(INPUT_POST, 'nome', FILTER_DEFAULT);
$cpfCliente   = filter_input(INPUT_POST, 'cpf', FILTER_DEFAULT);
$valor        = filter_input(INPUT_POST, 'valor', FILTER_VALIDATE_FLOAT);

if (!$nomeCliente)  $nomeCliente  = "Cliente Ficticio " . rand(10, 99);

$cpfLimpo = preg_replace('/[^0-9]/', '', (string)$cpfCliente);
if (empty($cpfLimpo) || strlen($cpfLimpo) !== 11) {
    $cpfLimpo = criarCpfFicticioForcado();
}

if (!$valor || $valor <= 0) $valor = 50.00;

// --- 1. CRIAR OU BUSCAR CLIENTE NO ASAAS ---
$ch = curl_init(ASAAS_API_URL . '/customers');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_POSTFIELDS     => json_encode([
        'name'    => $nomeCliente,
        'cpfCnpj' => $cpfLimpo
    ]),
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'User-Agent: SistemaAgendamento',
        'access_token: ' . $apiKey
    ]
]);

$respostaRaw = curl_exec($ch);
if (curl_errno($ch)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro de conexão cURL: ' . curl_error($ch)]);
    curl_close($ch);
    exit;
}
curl_close($ch);

$respostaCliente = json_decode($respostaRaw, true);
$customerId = $respostaCliente['id'] ?? null;

if (!$customerId) {
    $erroMsg = $respostaCliente['errors'][0]['description'] ?? 'Erro ao cadastrar cliente no Asaas.';
    echo json_encode(['sucesso' => false, 'mensagem' => $erroMsg, 'detalhes' => $respostaCliente]);
    exit;
}

// --- 2. PROCESSAR PIX ---
if ($metodo === 'PIX') {
    $ch = curl_init(ASAAS_API_URL . '/payments');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_POSTFIELDS     => json_encode([
            'customer'    => $customerId,
            'billingType' => 'PIX',
            'value'       => $valor,
            'dueDate'     => date('Y-m-d'),
            'description' => 'Sinal de Agendamento'
        ]),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'User-Agent: SistemaAgendamento',
            'access_token: ' . $apiKey
        ]
    ]);

    $resCobranca = json_decode(curl_exec($ch), true);
    curl_close($ch);

    $paymentId = $resCobranca['id'] ?? null;

    if (!$paymentId) {
        $erroMsg = $resCobranca['errors'][0]['description'] ?? 'Erro ao gerar cobrança no Asaas.';
        echo json_encode(['sucesso' => false, 'mensagem' => $erroMsg]);
        exit;
    }

    if (isset($_SESSION['agendamento_temporario'])) {
        $_SESSION['agendamento_temporario']['payment_id'] = $paymentId;
        $_SESSION['agendamento_temporario']['metodo_pagamento'] = 'PIX';
    }

    $resPix = null;
    $tentativas = 3;

    for ($i = 1; $i <= $tentativas; $i++) {
        usleep(800000); // 0.8 segundo antes de cada tentativa

        $ch = curl_init(ASAAS_API_URL . "/payments/{$paymentId}/pixQrCode");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'User-Agent: SistemaAgendamento',
                'access_token: ' . $apiKey
            ]
        ]);

        $resPix = json_decode(curl_exec($ch), true);
        curl_close($ch);

        if (isset($resPix['encodedImage']) && isset($resPix['payload'])) {
            break;
        }
    }

    if (isset($resPix['encodedImage']) && isset($resPix['payload'])) {
        echo json_encode([
            'sucesso'    => true,
            'tipo'       => 'PIX',
            'payment_id' => $paymentId,
            'qr_code'    => $resPix['encodedImage'],
            'copia_cola' => $resPix['payload']
        ]);
    } elseif (isset($resPix['payload'])) {
        echo json_encode([
            'sucesso'           => true,
            'tipo'              => 'PIX',
            'payment_id'        => $paymentId,
            'qr_code'           => null,
            'gerar_qr_localmente' => true,
            'copia_cola'        => $resPix['payload']
        ]);
    } else {
        $erroPix = $resPix['errors'][0]['description'] ?? 'Erro ao obter o QR Code do Asaas.';
        echo json_encode(['sucesso' => false, 'mensagem' => $erroPix]);
    }
    exit;
}

// --- 3. PROCESSAR CRÉDITO OU DÉBITO ---
if ($metodo === 'CREDIT_CARD' || $metodo === 'DEBIT_CARD') {
    $ch = curl_init(ASAAS_API_URL . '/payments');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_POSTFIELDS     => json_encode([
            'customer'    => $customerId,
            'billingType' => 'CREDIT_CARD',
            'value'       => $valor,
            'dueDate'     => date('Y-m-d'),
            'description' => 'Sinal de Agendamento'
        ]),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'User-Agent: SistemaAgendamento',
            'access_token: ' . $apiKey
        ]
    ]);

    $resCartao = json_decode(curl_exec($ch), true);
    curl_close($ch);

    $paymentId  = $resCartao['id'] ?? null;
    $invoiceUrl = $resCartao['invoiceUrl'] ?? null;

    if (!$paymentId || !$invoiceUrl) {
        $erroMsg = $resCartao['errors'][0]['description'] ?? 'Erro ao gerar a cobrança no Asaas.';
        echo json_encode(['sucesso' => false, 'mensagem' => $erroMsg]);
        exit;
    }

    if (isset($_SESSION['agendamento_temporario'])) {
        $_SESSION['agendamento_temporario']['payment_id'] = $paymentId;
        $_SESSION['agendamento_temporario']['metodo_pagamento'] = $metodo;
    }

    echo json_encode([
        'sucesso'     => true,
        'tipo'        => 'CARD_REDIRECT',
        'metodo'      => $metodo,
        'payment_id'  => $paymentId,
        'invoice_url' => $invoiceUrl
    ]);
    exit;
}

echo json_encode(['sucesso' => false, 'mensagem' => 'Método de pagamento inválido.']);
exit;
?>