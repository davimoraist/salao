// ====== CONTROLE DE EXIBIÇÃO DE TELAS DE PAGAMENTO ======

function esconderTodosMetodos() {
    const pix = document.getElementById('container-pix');
    const credito = document.getElementById('container-credito');
    const debito = document.getElementById('container-debito');

    if (pix) pix.style.display = 'none';
    if (credito) credito.style.display = 'none';
    if (debito) debito.style.display = 'none';
}

function pixgera() {
    esconderTodosMetodos();
    const pix = document.getElementById('container-pix');
    if (pix) pix.style.display = 'block';
}

function creditopaga() {
    esconderTodosMetodos();
    const credito = document.getElementById('container-credito');
    if (credito) credito.style.display = 'block';
}

function debitopaga() {
    esconderTodosMetodos();
    const debito = document.getElementById('container-debito');
    if (debito) debito.style.display = 'block';
}

// ====== PROCESSAMENTO DE PAGAMENTO COM ASAAS ======

function enviarPagamento(metodo) {
    // Função auxiliar para capturar valor com segurança sem quebrar o código
    const getVal = (id) => {
        const el = document.getElementById(id);
        return el ? el.value.trim() : '';
    };

    const nome = getVal('cliente_nome');
    const email = getVal('cliente_email');
    const cpf = getVal('cliente_cpf');
    const cep = getVal('cliente_cep');
    const valor = getVal('valor_sinal');

    const formData = new FormData();
    formData.append('metodo', metodo);
    formData.append('nome', nome);
    formData.append('email', email);
    formData.append('cpf', cpf);
    formData.append('cep', cep);
    formData.append('valor', valor);

    // Captura dados do cartão apenas se for Crédito ou Débito
    if (metodo === 'CREDIT_CARD') {
        formData.append('cartao_nome', getVal('credito_nome'));
        formData.append('cartao_numero', getVal('credito_numero'));
        formData.append('cartao_mes', getVal('credito_mes'));
        formData.append('cartao_ano', getVal('credito_ano'));
        formData.append('cartao_ccv', getVal('credito_ccv'));
    } else if (metodo === 'DEBIT_CARD') {
        formData.append('cartao_nome', getVal('debito_nome'));
        formData.append('cartao_numero', getVal('debito_numero'));
        formData.append('cartao_mes', getVal('debito_mes'));
        formData.append('cartao_ano', getVal('debito_ano'));
        formData.append('cartao_ccv', getVal('debito_ccv'));
    }

    // Identifica o botão clicado para dar feedback visual
    let btnAtual = null;
    if (metodo === 'PIX') btnAtual = document.getElementById('btn-gerar-pix');
    if (metodo === 'CREDIT_CARD') btnAtual = document.getElementById('btn-pagar-credito');
    if (metodo === 'DEBIT_CARD') btnAtual = document.getElementById('btn-pagar-debito');

    if (btnAtual) {
        btnAtual.disabled = true;
        btnAtual.dataset.textoOriginal = btnAtual.innerText;
        btnAtual.innerText = 'Processando...';
    }

    // Envio para o PHP
    fetch('asaas_config.php', {
        method: 'POST',
        body: formData
    })
        .then(async response => {
            const text = await response.text();
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Resposta bruta do servidor (não JSON):', text);
                throw new Error('Servidor retornou uma resposta inválida.');
            }
        })
        .then(data => {
            if (data.sucesso) {
                if (data.tipo === 'PIX') {
                    const imgQr = document.getElementById('img-qrcode');
                    const inputCopia = document.getElementById('input-copia-cola');
                    const areaQr = document.getElementById('area-qr-code');

                    if (imgQr) imgQr.src = 'data:image/png;base64,' + data.qr_code;
                    if (inputCopia) inputCopia.value = data.copia_cola;
                    if (areaQr) areaQr.style.display = 'block';

                    if (btnAtual) btnAtual.style.display = 'none';
                } else {
                    alert('Pagamento aprovado com sucesso!');
                    window.location.href = 'sucesso.php';
                }
            } else {
                alert(data.mensagem || 'Erro ao processar o pagamento.');
                if (btnAtual) {
                    btnAtual.disabled = false;
                    btnAtual.innerText = btnAtual.dataset.textoOriginal;
                }
            }
        })
        .catch(err => {
            console.error('Erro de execução:', err);
            alert('Erro ao processar requisição. Verifique o console do navegador (F12).');
            if (btnAtual) {
                btnAtual.disabled = false;
                btnAtual.innerText = btnAtual.dataset.textoOriginal || 'Tentar Novamente';
            }
        });
}

function copiarPix() {
    const inputCopia = document.getElementById('input-copia-cola');
    if (inputCopia) {
        inputCopia.select();
        inputCopia.setSelectionRange(0, 99999);
        navigator.clipboard.writeText(inputCopia.value);
        alert('Código Pix copiado!');
    }
}