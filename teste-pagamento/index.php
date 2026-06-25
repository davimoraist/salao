 <?php
// ======================================================================
// Bloco PHP: Processa a API do Asaas antes de carregar qualquer HTML
// ======================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['acao']) && $_GET['acao'] === 'gerar_pix') {
    // Limpa qualquer espaço ou saída anterior para não corromper o JSON
    ob_clean();
    header("Content-Type: application/json; charset=utf-8");

    // ⚠️ INSIRA SUA CHAVE DO SANDBOX AQUI:
    $apiKey = 'SUA_CHAVE_API_DO_SANDBOX_AQUI'; 

    // Gerador de CPF válido para evitar o erro 400 de duplicidade
    function criarCpfFicticio() {
        $n = array_map(function() { return rand(0, 9); }, range(1, 9));
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) $d += $n[$c] * (($t + 1) - $c);
            $d = ((10 * $d) % 11) % 10;
            $n[] = $d;
        }
        return implode('', $n);
    }
    $cpf = criarCpfFicticio();

    // Passo 1: Criar Cliente Fictício
    $ch = curl_init('https://sandbox.asaas.com/api/v3/customers');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            "name" => "Cliente Teste Local " . rand(10, 99),
            "cpfCnpj" => $cpf
        ]),
        CURLOPT_HTTPHEADER => ["Content-Type: application/json", "access_token: " . $apiKey]
    ]);
    $resCliente = json_decode(curl_exec($ch), true);
    curl_close($ch);

    $clienteId = $resCliente['id'] ?? null;

    if (!$clienteId) {
        http_response_code(400);
        echo json_encode(["status" => "erro", "motivo" => "Falha ao criar cliente", "dados" => $resCliente]);
        exit;
    }

    // Passo 2: Criar Cobrança Pix
    $ch = curl_init('https://sandbox.asaas.com/api/v3/payments');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            "customer" => $clienteId,
            "billingType" => "PIX",
            "value" => 50.00,
            "dueDate" => date('Y-m-d'),
            "description" => "Pagamento de Teste"
        ]),
        CURLOPT_HTTPHEADER => ["Content-Type: application/json", "access_token: " . $apiKey]
    ]);
    $resCobranca = json_decode(curl_exec($ch), true);
    curl_close($ch);

    $paymentId = $resCobranca['id'] ?? null;

    if (!$paymentId) {
        http_response_code(400);
        echo json_encode(["status" => "erro", "motivo" => "Falha ao criar cobrança", "dados" => $resCobranca]);
        exit;
    }

    // Passo 3: Buscar QR Code e Payload do Pix
    $ch = curl_init("https://sandbox.asaas.com/api/v3/payments/{$paymentId}/pixQrCode");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ["access_token: " . $apiKey]
    ]);
    $resPix = curl_exec($ch);
    curl_close($ch);

    // Devolve o JSON bruto do QR Code do Asaas diretamente para o JavaScript
    echo $resPix;
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout</title>
    <link rel="stylesheet" href="style.css">
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
            <input type="text" placeholder="Nome no Cartão">
            <div class="linha">
                <input type="text" placeholder="MM/AA">
                <input type="text" placeholder="CVV">
            </div>
            <button>Finalizar Compra</button>
        </div>

        <div id="debito" class="pagamento" style="display: none;">
            <h2>Cartão de Débito</h2>
            <input type="text" placeholder="Número do Cartão">
            <input type="text" placeholder="Nome no Cartão">
            <div class="linha">
                <input type="text" placeholder="MM/AA">
                <input type="text" placeholder="CVV">
            </div>
            <button>Finalizar Compra</button>
        </div>
    </div>

    <script>
    function mostrarPagamento(tipo) {
        let pagamentos = document.querySelectorAll('.pagamento');
        pagamentos.forEach(item => { item.style.display = 'none'; });
        document.getElementById(tipo).style.display = 'block';
    }

    async function generarPixNoAsaas() {
        // Redireciona para o nome correto caso a função no onclick esteja errada
        gerarPixNoAsaas();
    }

    async function gerarPixNoAsaas() {
        const btnGerar = document.getElementById('btn-gerar-pix');
        const divCarregando = document.getElementById('carregando-pix');
        const divDados = document.getElementById('dados-qrcode');

        btnGerar.style.display = 'none';
        divCarregando.style.display = 'block';
        divDados.style.display = 'none';

        try {
            const response = await fetch('?acao=gerar_pix', { method: 'POST' });
            const textoBruto = await response.text();

            // Tenta converter a resposta para JSON de forma segura
            let dadosPix;
            try {
                dadosPix = JSON.parse(textoBruto);
            } catch (e) {
                alert("O servidor não retornou um JSON válido. Resposta recebida: " + textoBruto);
                resetarInterface(btnGerar, divCarregando);
                return;
            }

            // Se a requisição do Asaas deu erro nos passos 1 ou 2
            if (!response.ok || dadosPix.status === "erro") {
                let msg = dadosPix.motivo || "Erro desconhecido";
                if (dadosPix.dados && dadosPix.dados.errors) {
                    msg += ": " + dadosPix.dados.errors.map(e => e.description).join(", ");
                }
                alert("Erro: " + msg);
                resetarInterface(btnGerar, divCarregando);
                return;
            }

            // Injeta os dados na tela se o Asaas mandou a imagem
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
            alert("Não foi possível conectar ao servidor local Apache.");
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