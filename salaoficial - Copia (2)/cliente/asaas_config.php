 <?php
session_start();
require_once "conecte.php"; // deve definir $conn (mysqli) já conectado

// Oculta avisos visuais do PHP para evitar quebrar a resposta JSON do JavaScript
ini_set('display_errors', 0);
error_reporting(E_ALL);

header("Content-Type: application/json; charset=utf-8");

// Configuração do Asaas
define('ASAAS_API_URL', 'https://sandbox.asaas.com/api/v3'); // Mude para https://www.asaas.com/api/v3 em produção
$apiKey = '$aact_hmlg_000MzkwODA2MWY2OGM3MWRlMDU2NWM3MzJlNzZmNGZhZGY6OjlmMGVjOThlLTNlMmEtNDM1Ni1iMzRhLTgxODM1MjU0Yzk1OTo6JGFhY2hfN2E5ZmIwYTgtY2JkOS00ZDc2LWI3YzctM2I1MGYwMzdiMDM3'; // Insira sua API Key do Asaas aqui

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

// ====== FUNÇÃO QUE GRAVA O AGENDAMENTO + O PAGAMENTO NO BANCO ======
// $metodo: 'PIX', 'CREDIT_CARD' ou 'DEBIT_CARD' (débito vem pela página do Asaas)
// $paymentIdAsaas: id da cobrança no Asaas (pode vir null)
function salvarAgendamentoNoBanco($conn, $metodo, $paymentIdAsaas = null) {
    if (!isset($_SESSION['agendamento_temporario'])) {
        return false;
    }

    $dados = $_SESSION['agendamento_temporario'];

    // --- 1. Grava o agendamento ---
    $sql = "INSERT INTO agendamentos 
            (id_cliente, servico, preco_servico, data_agendamento, hora_agendamento, status, criado_em, id_servicos) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        error_log("Erro ao preparar INSERT em agendamentos: " . $conn->error);
        return false;
    }

    $status = 'Confirmado';
    $criado_em = date('Y-m-d H:i:s');
    $id_servico = $dados['id_servicos'] ?? 0;

    $stmt->bind_param(
        "isdssssi",
        $dados['id_cliente'],
        $dados['servico'],
        $dados['preco_servico'],
        $dados['data_agendamento'],
        $dados['hora_agendamento'],
        $status,
        $criado_em,
        $id_servico
    );

    $sucesso = $stmt->execute();
    $idAgendamento = $conn->insert_id;
    $stmt->close();

    if (!$sucesso) {
        error_log("Erro ao executar INSERT em agendamentos: " . $conn->error);
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

    $stmtPagamento = $conn->prepare($sqlPagamento);

    if (!$stmtPagamento) {
        error_log("Erro ao preparar INSERT em pagamentos: " . $conn->error);
        // O agendamento já foi gravado; retornamos true mesmo assim,
        // mas registramos o erro pra investigar depois.
        return true;
    }

    $stmtPagamento->bind_param(
        "iissdddss",
        $idAgendamento,
        $dados['id_cliente'],
        $metodo,
        $paymentIdAsaas,
        $valorTotal,
        $valorPago,
        $valorRestante,
        $statusPagamento,
        $criado_em
    );

    if (!$stmtPagamento->execute()) {
        error_log("Erro ao executar INSERT em pagamentos: " . $stmtPagamento->error);
    }

    $stmtPagamento->close();

    unset($_SESSION['agendamento_temporario']);

    return true;
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
        $gravou = salvarAgendamentoNoBanco($conn, $metodoSalvo, $paymentId);
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

    // Buscar QR Code e Código Copia e Cola.
    // No ambiente Sandbox do Asaas, a geração da IMAGEM do QR code às
    // vezes falha de forma intermitente (limitação documentada do
    // próprio Asaas), mesmo com a cobrança e a chave Pix corretas.
    // Por isso tentamos algumas vezes, com uma pequena espera entre
    // as tentativas.
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

        // Já temos imagem + payload: sucesso completo, pode parar.
        if (isset($resPix['encodedImage']) && isset($resPix['payload'])) {
            break;
        }
    }

    if (isset($resPix['encodedImage']) && isset($resPix['payload'])) {
        // Caminho normal: o Asaas devolveu a imagem pronta.
        echo json_encode([
            'sucesso'    => true,
            'tipo'       => 'PIX',
            'payment_id' => $paymentId,
            'qr_code'    => $resPix['encodedImage'],
            'copia_cola' => $resPix['payload']
        ]);
    } elseif (isset($resPix['payload'])) {
        // A imagem falhou (comum no Sandbox), mas o payload (código
        // "copia e cola") veio certo — o front gera o QR code a
        // partir desse texto, sem depender da imagem do Asaas.
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

// --- 3. PROCESSAR CRÉDITO OU DÉBITO (página de pagamento do Asaas) ---
// Os dados do cartão nunca passam pelo nosso site: criamos a cobrança sem
// dados de cartão e mandamos o cliente pra página de fatura do Asaas
// (invoiceUrl), onde ele escolhe Cartão de Crédito ou Cartão de Débito.
// Com billingType CREDIT_CARD, a fatura mostra só as opções de cartão,
// sem boleto nem Pix. A confirmação é feita pelo mesmo polling do Pix.
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

// Método não reconhecido
echo json_encode(['sucesso' => false, 'mensagem' => 'Método de pagamento inválido.']);
exit;
?>