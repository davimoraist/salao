<?php
// Permite que qualquer página leia o JSON gerado
header("Content-Type: application/json");

// 1. CONFIGURAÇÕES DO ASAAS SANDBOX
$apiKey = 'SUA_CHAVE_API_DO_SANDBOX_AQUI'; 
$clienteId = 'ID_DE_UM_CLIENTE_REAL_DO_PAINEL_AQUI'; // Começa com cus_...

$urlCobranca = 'https://sandbox.asaas.com/api/v3/payments';

// 2. DADOS DA COBRANÇA
$dadosCobranca = [
    "customer" => $clienteId,
    "billingType" => "PIX",
    "value" => 50.00,                 // Valor do teste (R$ 50,00)
    "dueDate" => date('Y-m-d'),       // Vencimento para o dia de hoje
    "description" => "Pedido #101 - Checkout Pix"
];

// 3. REQUISIÇÃO PARA CRIAR A COBRANÇA
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $urlCobranca,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => json_encode($dadosCobranca),
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "access_token: " . $apiKey
    ],
]);

$response = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

if ($err) {
    http_response_code(500);
    echo json_encode(["error" => "Erro na comunicação com o Asaas: " . $err]);
    exit;
}

$dadosPagamento = json_decode($response, true);

// 4. SE A COBRANÇA FOI CRIADA, BUSCA O QR CODE
if (isset($dadosPagamento['id'])) {
    $paymentId = $dadosPagamento['id'];
    
    $chPix = curl_init();
    curl_setopt_array($chPix, [
        CURLOPT_URL => "https://sandbox.asaas.com/api/v3/payments/{$paymentId}/pixQrCode",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => [
            "access_token: " . $apiKey
        ],
    ]);
    
    $responsePix = curl_exec($chPix);
    curl_close($chPix);
    
    // Devolve o JSON do QR Code direto para o JavaScript
    echo $responsePix;
} else {
    http_response_code(400);
    echo json_encode([
        "error" => "Não foi possível gerar a cobrança", 
        "detalhes" => $dadosPagamento
    ]);
}