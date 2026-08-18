 <?php
// Oculta avisos do PHP que quebram a resposta JSON do JavaScript
ini_set('display_errors', 0);
error_reporting(0);

header("Content-Type: application/json; charset=utf-8");

// Configuração do Asaas
define('ASAAS_API_URL', 'https://sandbox.asaas.com/api/v3'); // Mude para https://www.asaas.com/api/v3 em produção
$apiKey = '$aact_hmlg_000MzkwODA2MWY2OGM3MWRlMDU2NWM3MzJlNzZmNGZhZGY6Ojk3Y2VjMDgxLTAxM2EtNDhiNS05OWFhLWVkOTU0NDZiNGVjMDo6JGFhY2hfZGFlMTM4MGYtYzhlNC00YThiLThkYzMtNmFkNjdiYmM4M2Nj'; // Insira sua API Key do Asaas aqui

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
    
    // Une todos os dígitos e retorna a string do CPF
    $todosDigitos = array_merge($n, [$d1, $d2]);
    return implode('', $todosDigitos);
}

// Captura os dados do POST
$metodo      = filter_input(INPUT_POST, 'metodo', FILTER_DEFAULT) ?? 'PIX';
$nomeCliente = filter_input(INPUT_POST, 'nome', FILTER_DEFAULT);
$emailCliente= filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$cpfCliente  = filter_input(INPUT_POST, 'cpf', FILTER_DEFAULT);
$cepCliente  = filter_input(INPUT_POST, 'cep', FILTER_DEFAULT);
$valor       = filter_input(INPUT_POST, 'valor', FILTER_VALIDATE_FLOAT);

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
    CURLOPT_SSL_VERIFYPEER => false, // Evita falhas de certificado no localhost
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