 <?php
// Permite que qualquer página leia o JSON gerado
header("Content-Type: application/json; charset=utf-8");

/* =======================================================
   1. CONFIGURAÇÕES DO ASAAS SANDBOX
======================================================= */
$apiKey = '$aact_hmlg_000MzkwODA2MWY2OGM3MWRlMDU2NWM3MzJlNzZmNGZhZGY6Ojk3Y2VjMDgxLTAxM2EtNDhiNS05OWFhLWVkOTU0NDZiNGVjMDo6JGFhY2hfZGFlMTM4MGYtYzhlNC00YThiLThkYzMtNmFkNjdiYmM4M2Nj'; // 🔴 Coloque sua chave aqui dentro das aspas

/* =======================================================
   2. RECONHECIMENTO DOS DADOS DE ENTRADA
======================================================= */
$nomeCliente = filter_input(INPUT_POST, 'nome', FILTER_DEFAULT);
$cpfCliente  = filter_input(INPUT_POST, 'cpf', FILTER_DEFAULT);
$emailCliente = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$valorPix    = filter_input(INPUT_POST, 'valor', FILTER_VALIDATE_FLOAT);
 
/* =======================================================
   3. CADASTRO OU LOCALIZAÇÃO DO CLIENTE NO ASAAS
======================================================= */
$clienteId = obterOuCadastrarCliente($nomeCliente, $cpfCliente, $emailCliente, $apiKey);

if (!$clienteId) {
    http_response_code(400);
    echo json_encode([
        "status" => "erro", 
        "motivo" => "Não foi possível cadastrar ou localizar o cliente na API do Asaas."
    ]);
    exit;
}

/* =======================================================
   4. DADOS DA COBRANÇA E SOLICITAÇÃO DO PIX
======================================================= */
$urlCobranca = 'https://sandbox.asaas.com/api/v3/payments';

$dadosCobranca = [
    "customer" => $clienteId,
    "billingType" => "PIX",
    "value" => $valorPix, 
    "dueDate" => date('Y-m-d'),
    "description" => "Agendamento de Serviço - mtnaildesigner"
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $urlCobranca,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_SSL_VERIFYPEER => false, // 🛠️ DESATIVADO PARA XAMPP
    CURLOPT_SSL_VERIFYHOST => false, // 🛠️ DESATIVADO PARA XAMPP
    CURLOPT_POSTFIELDS => json_encode($dadosCobranca),
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "access_token: " . $apiKey,
        "User-Agent: mtnaildesigner-app" // 🛠️ RESOLVE O SEU ERRO ATUAL
    ],
]);

$response = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

if ($err) {
    http_response_code(500);
    echo json_encode(["error" => "Erro na comunicação com o Asaas ao gerar cobrança: " . $err]);
    exit;
}

$dadosPagamento = json_decode($response, true);

/* =======================================================
   5. BUSCA DO QR CODE DO PIX
======================================================= */
if (isset($dadosPagamento['id']) && !isset($dadosPagamento['errors'])) {
    $paymentId = $dadosPagamento['id'];
    
    $chPix = curl_init();
    curl_setopt_array($chPix, [
        CURLOPT_URL => "https://sandbox.asaas.com/api/v3/payments/{$paymentId}/pixQrCode",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_SSL_VERIFYPEER => false, // 🛠️ DESATIVADO PARA XAMPP
        CURLOPT_SSL_VERIFYHOST => false, // 🛠️ DESATIVADO PARA XAMPP
        CURLOPT_HTTPHEADER => [
            "access_token: " . $apiKey,
            "User-Agent: mtnaildesigner-app" // 🛠️ RESOLVE O SEU ERRO ATUAL
        ],
    ]);
    
    $responsePix = curl_exec($chPix);
    curl_close($chPix);
    
    echo $responsePix;
} else {
    http_response_code(400);
    echo json_encode([
        "status" => "erro",
        "motivo" => "Falha ao cadastrar o cliente no Asaas.", 
        "dados" => $dadosPagamento
    ]);
}

/* =======================================================
   6. FUNÇÕES AUXILIARES INDISPENSÁVEIS
======================================================= */

function obterOuCadastrarCliente($nome, $cpf, $email, $apiKey) {
    $cpfLimpo = preg_replace('/[^0-9]/', '', $cpf);
    $url = 'https://sandbox.asaas.com/api/v3/customers';
    
    $dadosCliente = [
        "name" => $nome,
        "cpfCnpj" => $cpfLimpo
    ];
    if ($email) { $dadosCliente["email"] = $email; }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_POSTFIELDS => json_encode($dadosCliente),
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "access_token: " . $apiKey,
            "User-Agent: mtnaildesigner-app" // 🛠️ RESOLVE O SEU ERRO ATUAL
        ],
    ]);

    $response = curl_exec($ch);
    curl_close($ch);
    $resultado = json_decode($response, true);

    if (isset($resultado['id'])) {
        return $resultado['id']; 
    }
    
    if (isset($resultado['errors']) && $resultado['errors'][0]['code'] === 'has_already_a_customer_with_this_cpfCnpj') {
        $chBusca = curl_init($url . "?cpfCnpj=" . $cpfLimpo);
        curl_setopt_array($chBusca, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => [
                "access_token: " . $apiKey,
                "User-Agent: mtnaildesigner-app" // 🛠️ RESOLVE O SEU ERRO ATUAL
            ],
        ]);
        $resBusca = json_decode(curl_exec($chBusca), true);
        curl_close($chBusca);
        
        if (!empty($resBusca['data'][0]['id'])) {
            return $resBusca['data'][0]['id'];
        }
    }
    
    return null;
}

function criarCpfFicticioForcado() {
    $n = array_map(function() { return rand(0, 9); }, range(1, 9));
    for ($t = 9; $t < 11; $t++) {
        for ($d = 0, $c = 0; $c < $t; $c++) $d += $n[$c] * (($t + 1) - $c);
        $d = ((10 * $d) % 11) % 10;
        $n[] = $d;
    }
    return implode('', $n);
}
?>