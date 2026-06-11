<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout de Teste</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
            width: 100%;
            max-width: 450px;
        }

        h2 {
            color: #1a1a1a;
            margin-bottom: 20px;
            text-align: center;
            font-size: 24px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 6px;
            color: #4a4a4a;
            font-weight: 600;
            font-size: 14px;
        }

        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
            transition: border 0.2s;
        }

        input:focus {
            border-color: #0066ff;
            outline: none;
        }

        button {
            width: 100%;
            padding: 14px;
            background-color: #0066ff;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 15px;
        }

        button:hover {
            background-color: #0052cc;
        }

        button:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }

        #resultado {
            margin-top: 20px;
            padding: 15px;
            border-radius: 6px;
            display: none;
            text-align: center;
        }

        .sucesso {
            background-color: #e6f4ea;
            color: #137333;
            border: 1px solid #dadce0;
        }

        .erro {
            background-color: #fce8e6;
            color: #c5221f;
            border: 1px solid #dadce0;
        }

        textarea {
            width: 100%;
            height: 70px;
            margin-top: 10px;
            padding: 8px;
            font-family: monospace;
            font-size: 12px;
            border-radius: 4px;
            border: 1px solid #ccc;
            resize: none;
        }
    </style>
</head>

<body>

    <div class="card">
        <h2>Finalizar Compra (Pix Fictício)</h2>
        <form id="formCheckout" action="pagamento.php">
            <div class="form-group">
                <label for="nome">Nome Completo</label>
                <input type="text" id="nome" required placeholder="Ex: José da Silva">
            </div>
            <div class="form-group">
                <label for="cpf">CPF (Apenas números)</label>
                <input type="text" id="cpf" required placeholder="Ex: 12345678901" maxlength="11">
            </div>
            <div class="form-group">
                <label for="valor">Valor (R$)</label>
                <input type="number" id="valor" step="0.01" required placeholder="0.00">
            </div>
            <button type="submit" id="btnPagar">Gerar Pix de Teste</button>
        </form>

        <div id="resultado"></div>
    </div>

    <script>
        document.getElementById('formCheckout').addEventListener('submit', async (e) => {
            e.preventDefault();

            const btn = document.getElementById('btnPagar');
            const resultadoDiv = document.getElementById('resultado');

            btn.innerText = "Comunicando com Asaas...";
            btn.disabled = true;
            resultadoDiv.style.display = "none";

            const dados = {
                nome: document.getElementById('nome').value,
                cpf: document.getElementById('cpf').value,
                valor: document.getElementById('valor').value
            };

            try {
                const resposta = await fetch('pagamento.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(dados)
                });

                const json = await resposta.json();
                resultadoDiv.style.display = "block";

                if (json.success) {
                    resultadoDiv.className = "sucesso";
                    resultadoDiv.innerHTML = `
                    <p style="font-weight:bold; margin-bottom:5px;">✓ Pix Gerado e Salvo no Banco!</p>
                    <small>Sua cobrança está como PENDENTE no banco de dados.</small>
                    <textarea readonly onclick="this.select()">${json.payload}</textarea>
                    <p style="font-size:12px; margin-top:8px; color:#555;">Copie o código acima para simular o pagamento.</p>
                `;
                } else {
                    resultadoDiv.className = "erro";
                    resultadoDiv.innerHTML = `<strong>Erro:</strong> ${json.error}`;
                }
            } catch (erro) {
                resultadoDiv.style.display = "block";
                resultadoDiv.className = "erro";
                resultadoDiv.innerHTML = "Erro ao conectar com o arquivo PHP.";
            } finally {
                btn.innerText = "Gerar Pix de Teste";
                btn.disabled = false;
            }
        });
    </script>

</body>

</html>