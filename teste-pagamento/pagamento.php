<?php
header('Content-Type: application/json');
require_once 'conexao.php';

// Recebe o JSON do JavaScript
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['nome']) || empty($input['cpf']) || empty($input['valor'])) {
    echo json_encode(['success' => false, 'error' => 'Preencha todos os campos corretamente.']);
    exit;
}

// CONFIGURAÇÃO DO ASAAS SANDBOX
$apiKey = 'SUA_CHAVE_DO_ASAAS_SANDBOX_AQUI'; 

$nome  = $input['nome'];
$cpf   = $input['cpf'];
$valor = $input['valor'];

// ==========================================
// PASSO 1: CADASTRAR CLIENTE NO ASAAS
// ==========================================
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://sandbox.asaas.com/api/v3/customers");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($ch, CURLOPT_POST, TRUE);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    "name" => $nome,
    "cpfCnpj" => $cpf
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "access_token: $apiKey"
]);

$response = curl_exec($ch);
$customer = json_decode($response, true);

if (isset($customer['errors'])) {
    echo json_encode(['success' => false, 'error' => 'Asaas Cliente: ' . $customer['errors'][0]['description']]);
    exit;
}

$customerId = $customer['id'];

// ==========================================
// PASSO 2: GERAR A COBRANÇA PIX
// ==========================================
curl_setopt($ch, CURLOPT_URL, "https://sandbox.asaas.com/api/v3/payments");
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    "customer" => $customer['id'],
    "billingType" => "PIX",
    "value" => $valor,
    "dueDate" => date('Y-m-d', strtotime('+2 days')) // Vence em 2 dias
]));

$response = curl_exec($ch);
$payment = json_decode($response, true);

if (isset($payment['errors'])) {
    echo json_encode(['success' => false, 'error' => 'Asaas Cobrança: ' . $payment['errors'][0]['description']]);
    exit;
}

$paymentId = $payment['id']; // Esse ID identifica a conta no Asaas

// ==========================================
// PASSO 3: SALVAR NO BANCO DE DADOS (MYSQL)
// ==========================================
$stmt = $conexao->prepare("INSERT INTO pagamentos (nome_cliente, cpf, valor, asaas_payment_id, status) VALUES (?, ?, ?, ?, 'PENDENTE')");
$stmt->bind_param("ssds", $nome, $cpf, $valor, $paymentId);

if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'error' => 'Erro ao salvar no banco local.']);
    exit;
}
$stmt->close();

// ==========================================
// PASSO 4: BUSCAR O CÓDIGO PIX COPIA E COLA
// ==========================================
curl_setopt($ch, CURLOPT_URL, "https://sandbox.asaas.com/api/v3/payments/$paymentId/pixQrCode");
curl_setopt($ch, CURLOPT_POST, FALSE); // Muda a requisição para GET

$response = curl_exec($ch);
$pixData = json_decode($response, true);
curl_close($ch);

if (isset($pixData['payload'])) {
    // Tudo certo! Retorna o código para a tela
    echo json_encode(['success' => true, 'payload' => $pixData['payload']]);
} else {
    echo json_encode(['success' => false, 'error' => 'Não foi possível gerar o código Pix, mas foi salvo no banco.']);
}

$conexao->close();