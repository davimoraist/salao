 <?php
// ======================================================================
// Bloco PHP: Processa a API do Asaas antes de carregar qualquer HTML
// ======================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['acao']) && $_GET['acao'] === 'gerar_pix') {
    // Força a limpeza do buffer para garantir que nenhum aviso do XAMPP quebre o JSON
    if (ob_get_length()) ob_clean();
    header("Content-Type: application/json; charset=utf-8");

    // ⚠️ COLOQUE SUA CHAVE DO SANDBOX AQUI
    $apiKey='coloca seu api aqui'; 

    // Coleta os dados enviados pelo formulário via JavaScript (Fetch)
    $nomeInput  = filter_input(INPUT_POST, 'nome', FILTER_DEFAULT);
    $cpfInput   = filter_input(INPUT_POST, 'cpf', FILTER_DEFAULT);
    $emailInput = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $valorInput = filter_input(INPUT_POST, 'valor', FILTER_VALIDATE_FLOAT);

    // Validações básicas antes de enviar para a API
    if (!$valorInput || $valorInput <= 0) { $valorInput = 50.00; }
    if (!$nomeInput || !$cpfInput || !$emailInput) {
        http_response_code(400);
        echo json_encode(["status" => "erro", "motivo" => "Por favor, preencha Nome, CPF e E-mail corretamente."]);
        exit;
    }

    // O Asaas exige apenas números no CPF/CNPJ
    $cpfLimpo = preg_replace('/[^0-9]/', '', $cpfInput);

    /* -------------------------------------------------------
       PASSO 1: CADASTRAR OU LOCALIZAR O CLIENTE NO ASAAS
    ------------------------------------------------------- */
    $urlClientes = 'https://sandbox.asaas.com/api/v3/customers';
    $dadosCliente = [
        "name" => $nomeInput,
        "cpfCnpj" => $cpfLimpo,
        "email" => $emailInput
    ];

    $ch = curl_init($urlClientes);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_SSL_VERIFYPEER => false, // 🛠️ Ignora SSL no XAMPP
        CURLOPT_SSL_VERIFYHOST => false, // 🛠️ Ignora SSL no XAMPP
        CURLOPT_POSTFIELDS => json_encode($dadosCliente),
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "access_token: " . $apiKey,
            "User-Agent: mtnaildesigner-app" // 🛠️ CORREÇÃO DO SEU ERRO
        ],
    ]);

    $resClienteRaw = curl_exec($ch);
    curl_close($ch);
    $resCliente = json_decode($resClienteRaw, true);

    $clienteId = $resCliente['id'] ?? null;

    // Se o cliente já existir com esse CPF, o Asaas recusa criar. Buscamos o ID existente:
    if (!$clienteId && isset($resCliente['errors']) && $resCliente['errors'][0]['code'] === 'has_already_a_customer_with_this_cpfCnpj') {
        $chBusca = curl_init($urlClientes . "?cpfCnpj=" . $cpfLimpo);
        curl_setopt_array($chBusca, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => [
                "access_token: " . $apiKey,
                "User-Agent: mtnaildesigner-app" // 🛠️ CORREÇÃO DO SEU ERRO
            ],
        ]);
        $resBusca = json_decode(curl_exec($chBusca), true);
        curl_close($chBusca);
        
        if (!empty($resBusca['data'][0]['id'])) {
            $clienteId = $resBusca['data'][0]['id'];
        }
    }

    // Se mesmo assim não temos um ID de cliente, interrompe com o erro real da API
    if (!$clienteId) {
        http_response_code(400);
        echo json_encode(["status" => "erro", "motivo" => "Falha ao cadastrar o cliente no Asaas.", "dados" => $resCliente]);
        exit;
    }

    /* -------------------------------------------------------
       PASSO 2: CRIAR A COBRANÇA PIX VINCULADA AO CLIENTE
    ------------------------------------------------------- */
    $urlCobranca = 'https://sandbox.asaas.com/api/v3/payments';
    $dadosCobranca = [
        "customer" => $clienteId,
        "billingType" => "PIX",
        "value" => $valorInput,
        "dueDate" => date('Y-m-d'),
        "description" => "Agendamento de Serviço - mtnaildesigner"
    ];

    $ch = curl_init($urlCobranca);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_POSTFIELDS => json_encode($dadosCobranca),
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "access_token: " . $apiKey,
            "User-Agent: mtnaildesigner-app" // 🛠️ CORREÇÃO DO SEU ERRO
        ],
    ]);

    $resCobranca = json_decode(curl_exec($ch), true);
    curl_close($ch);

    $paymentId = $resCobranca['id'] ?? null;

    if (!$paymentId) {
        http_response_code(400);
        echo json_encode(["status" => "erro", "motivo" => "Falha ao criar cobrança local.", "dados" => $resCobranca]);
        exit;
    }

    /* -------------------------------------------------------
       PASSO 3: BUSCAR O QR CODE E PAYLOAD COPIA E COLA
    ------------------------------------------------------- */
    $ch = curl_init("https://sandbox.asaas.com/api/v3/payments/{$paymentId}/pixQrCode");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HTTPHEADER => [
            "access_token: " . $apiKey,
            "User-Agent: mtnaildesigner-app" // 🛠️ CORREÇÃO DO SEU ERRO
        ],
    ]);
    
    $resPix = curl_exec($ch);
    curl_close($ch);

    // Retorna a imagem codificada e o payload direto para o JavaScript
    echo $resPix;
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - mtnaildesigner</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .campo-grupo { margin-bottom: 12px; text-align: left; }
        .campo-grupo label { display: block; margin-bottom: 5px; font-weight: bold; }
        .campo-grupo input { padding: 8px; width: 100%; box-sizing: border-box; }
    </style>
</head>
<body>

    <div class="checkout">
        <h1>Escolha a Forma de Pagamento</h1>

        <div class="botoes">
            <button onclick="mostrarPagamento('pix')">PIX</button>
            <button onclick="mostrarPagamento('credito')">Crédito</button>
            <button onclick="mostrarPagamento('debito')">Débito</button>
        </div>

        <div id="pix" class="pagamento" style="display: none;">
            <h2>Pagamento via PIX</h2>

            <div class="campo-grupo">
                <label for="cliente_nome">Nome Completo:</label>
                <input type="text" id="cliente_nome" placeholder="Ex: João Silva">
            </div>

            <div class="campo-grupo">
                <label for="cliente_cpf">CPF:</label>
                <input type="text" id="cliente_cpf" placeholder="000.000.000-00">
            </div>

            <div class="campo-grupo">
                <label for="cliente_email">E-mail:</label>
                <input type="email" id="cliente_email" placeholder="joao@email.com">
            </div>

            <div class="campo-grupo">
                <label for="valor_teste">Valor do Teste (R$):</label>
                <input type="number" id="valor_teste" value="50.00" step="0.01" min="1.00" style="text-align: center;">
            </div>

            <div id="carregando-pix" style="display: none; font-weight: bold; margin: 15px 0;">Gerando QR Code...</div>

            <div id="dados-qrcode" style="display: none; text-align: center;">
                <img id="qrCodeImg" src="" alt="QR Code PIX" style="max-width: 200px; margin: 10px 0; border: 1px solid #ccc; padding: 5px;"><br>

                <label for="pixCopiaCola" style="display:block; margin-top:10px;">Pix Copia e Cola:</label>
                <input type="text" id="pixCopiaCola" readonly style="width: 100%; margin-bottom: 10px; padding: 8px; text-align: center;">
            </div>

            <button id="btn-gerar-pix" onclick="gerarPixNoAsaas()">Gerar QR Code PIX</button>
        </div>

        <div id="credito" class="pagamento" style="display: none;">
            <h2>Cartão de Crédito</h2>
            <input type="text" placeholder="Número do Cartão">
            <button>Finalizar Compra</button>
        </div>

        <div id="debito" class="pagamento" style="display: none;">
            <h2>Cartão de Débito</h2>
            <input type="text" placeholder="Número do Cartão">
            <button>Finalizar Compra</button>
        </div>
    </div>

    <script>
    function mostrarPagamento(tipo) {
        let pagamentos = document.querySelectorAll('.pagamento');
        pagamentos.forEach(item => { item.style.display = 'none'; });
        document.getElementById(tipo).style.display = 'block';
    }

    async function gerarPixNoAsaas() {
        const btnGerar = document.getElementById('btn-gerar-pix');
        const divCarregando = document.getElementById('carregando-pix');
        const divDados = document.getElementById('dados-qrcode');
        
        // Coleta valores do formulário HTML
        const valorTeste = document.getElementById('valor_teste').value;
        const nomeReal = document.getElementById('cliente_nome').value;
        const cpfReal = document.getElementById('cliente_cpf').value;
        const emailReal = document.getElementById('cliente_email').value;

        if (!nomeReal || !cpfReal || !emailReal) {
            alert("Por favor, preencha todos os campos do cliente (Nome, CPF e E-mail).");
            return;
        }

        btnGerar.style.display = 'none';
        divCarregando.style.display = 'block';
        divDados.style.display = 'none';

        try {
            const formData = new FormData();
            formData.append('valor', valorTeste);
            formData.append('nome', nomeReal);
            formData.append('cpf', cpfReal);
            formData.append('email', emailReal);

            // Envia para a própria página atual (?acao=gerar_pix) que processa o PHP no topo
            const response = await fetch('?acao=gerar_pix', { 
                method: 'POST',
                body: formData
            });
            const textoBruto = await response.text();

            let dadosPix;
            try {
                dadosPix = JSON.parse(textoBruto);
            } catch (e) {
                alert("Erro de resposta inválida do servidor: " + textoBruto);
                resetarInterface(btnGerar, divCarregando);
                return;
            }

            if (!response.ok || dadosPix.status === "erro") {
                let msg = dadosPix.motivo || "Erro desconhecido";
                if (dadosPix.dados && dadosPix.dados.errors) {
                    msg += ": " + dadosPix.dados.errors.map(e => e.description).join(", ");
                }
                alert("Erro: " + msg);
                resetarInterface(btnGerar, divCarregando);
                return;
            }

            if (dadosPix.encodedImage && dadosPix.payload) {
                document.getElementById('qrCodeImg').src = `data:image/png;base64,${dadosPix.encodedImage}`;
                document.getElementById('pixCopiaCola').value = dadosPix.payload;

                divCarregando.style.display = 'none';
                divDados.style.display = 'block';
            } else {
                alert("O Asaas não enviou a imagem do QR Code de volta.");
                resetarInterface(btnGerar, divCarregando);
            }
        } catch (error) {
            console.error("Erro na requisição:", error);
            alert("Erro ao conectar no servidor local.");
            resetarInterface(btnGerar, divCarregando);
        }
    }

    function resetarInterface(btn, carregando) {
        btn.style.display = 'block';
        carregando.style.display = 'none';
    }
    </script>
</body>
</html>