 <?php
session_start();
require_once "conecte.php"; // deve definir $conn (mysqli) já conectado

// Oculta avisos visuais do PHP para evitar quebrar a resposta JSON do JavaScript
ini_set('display_errors', 0);
error_reporting(E_ALL);

header("Content-Type: application/json; charset=utf-8");

// Configuração do Asaas
define('ASAAS_API_URL', 'https://sandbox.asaas.com/api/v3'); // Mude para https://www.asaas.com/api/v3 em produção
$apiKey = 'coloca seu api aqui'; // Insira sua API Key do Asaas aqui

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

// ====== FUNÇÃO QUE GRAVA O AGENDAMENTO NO BANCO ======
function salvarAgendamentoNoBanco($conn) {
    if (!isset($_SESSION['agendamento_temporario'])) {
        return false;
    }

    $dados = $_SESSION['agendamento_temporario'];

    $sql = "INSERT INTO agendamentos 
            (id_cliente, servico, preco_servico, data_agendamento, hora_agendamento, status, criado_em, id_servicos) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
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
    $stmt->close();

    if ($sucesso) {
        unset($_SESSION['agendamento_temporario']);
    }

    return $sucesso;
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
        $gravou = salvarAgendamentoNoBanco($conn);
        echo json_encode(['pago' => true, 'salvo_no_banco' => $gravou]);
    } else {
        echo json_encode(['pago' => false, 'status' => $status]);
    }
    exit;
}

// ====== FLUXO DE CRIAÇÃO DE COBRANÇA (POST vindo de enviarPagamento no JS) ======

$metodo       = filter_input(INPUT_POST, 'metodo', FILTER_DEFAULT) ?? 'PIX';
$nomeCliente  = filter_input(INPUT_POST, 'nome', FILTER_DEFAULT);
$emailCliente = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$cpfCliente   = filter_input(INPUT_POST, 'cpf', FILTER_DEFAULT);
$cepCliente   = filter_input(INPUT_POST, 'cep', FILTER_DEFAULT);
$valor        = filter_input(INPUT_POST, 'valor', FILTER_VALIDATE_FLOAT);

if (!$nomeCliente)  $nomeCliente  = "Cliente Ficticio " . rand(10, 99);
if (!$emailCliente) $emailCliente = "cliente" . rand(100, 999) . "@exemplo.com";

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
        'name'       => $nomeCliente,
        'email'      => $emailCliente,
        'cpfCnpj'    => $cpfLimpo,
        'postalCode' => preg_replace('/[^0-9]/', '', (string)$cepCliente)
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
    }

    // Buscar QR Code e Código Copia e Cola
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
        echo json_encode([
            'sucesso'    => true,
            'tipo'       => 'PIX',
            'payment_id' => $paymentId,
            'qr_code'    => $resPix['encodedImage'],
            'copia_cola' => $resPix['payload']
        ]);
    } else {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao obter o QR Code do Asaas.']);
    }
    exit;
}

// --- 3. PROCESSAR CRÉDITO OU DÉBITO ---
if ($metodo === 'CREDIT_CARD' || $metodo === 'DEBIT_CARD') {

    $cartaoNome   = filter_input(INPUT_POST, 'cartao_nome', FILTER_DEFAULT);
    $cartaoNumero = preg_replace('/\s+/', '', (string) filter_input(INPUT_POST, 'cartao_numero', FILTER_DEFAULT));
    $cartaoMes    = filter_input(INPUT_POST, 'cartao_mes', FILTER_DEFAULT);
    $cartaoAno    = filter_input(INPUT_POST, 'cartao_ano', FILTER_DEFAULT);
    $cartaoCcv    = filter_input(INPUT_POST, 'cartao_ccv', FILTER_DEFAULT);

    if (!$cartaoNome || !$cartaoNumero || !$cartaoMes || !$cartaoAno || !$cartaoCcv) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Preencha todos os dados do cartão.']);
        exit;
    }

    $payloadCartao = [
        'customer'             => $customerId,
        'billingType'          => 'CREDIT_CARD',
        'value'                => $valor,
        'dueDate'              => date('Y-m-d'),
        'description'          => 'Sinal de Agendamento',
        'creditCard' => [
            'holderName'  => $cartaoNome,
            'number'      => $cartaoNumero,
            'expiryMonth' => $cartaoMes,
            'expiryYear'  => $cartaoAno,
            'ccv'         => $cartaoCcv
        ],
        'creditCardHolderInfo' => [
            'name'          => $nomeCliente,
            'email'         => $emailCliente,
            'cpfCnpj'       => $cpfLimpo,
            'postalCode'    => preg_replace('/[^0-9]/', '', (string)$cepCliente),
            'addressNumber' => '0'
        ],
        'remoteIp' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
    ];

    $ch = curl_init(ASAAS_API_URL . '/payments');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_POSTFIELDS     => json_encode($payloadCartao),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'User-Agent: SistemaAgendamento',
            'access_token: ' . $apiKey
        ]
    ]);

    $resCartao = json_decode(curl_exec($ch), true);
    curl_close($ch);

    $status = $resCartao['status'] ?? '';

    if (in_array($status, ['RECEIVED', 'CONFIRMED'])) {
        $gravou = salvarAgendamentoNoBanco($conn);

        echo json_encode([
            'sucesso'       => true,
            'tipo'          => $metodo,
            'payment_id'    => $resCartao['id'] ?? null,
            'salvo_no_banco'=> $gravou
        ]);
    } else {
        $erroMsg = $resCartao['errors'][0]['description'] ?? 'Pagamento recusado pela operadora.';
        echo json_encode(['sucesso' => false, 'mensagem' => $erroMsg]);
    }
    exit;
}

// Método não reconhecido
echo json_encode(['sucesso' => false, 'mensagem' => 'Método de pagamento inválido.']);
exit;
?>