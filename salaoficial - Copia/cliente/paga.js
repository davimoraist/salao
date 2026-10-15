let intervalChecagem = null;

// ====== VERIFICAÇÃO DE STATUS DO PIX ======

function verificarStatusPix(paymentId) {
    if (intervalChecagem) clearInterval(intervalChecagem);

    intervalChecagem = setInterval(() => {
        fetch(`asaas_config.php?acao=checar_status&payment_id=${paymentId}`)
            .then(res => res.json())
            .then(data => {
                if (data.pago) {
                    clearInterval(intervalChecagem);

                    // O próprio asaas_config.php já salvou o agendamento
                    // no banco (dentro de salvarAgendamentoNoBanco) e nos
                    // diz aqui se deu certo — não é preciso chamar mais
                    // nada, e chamar de novo daria erro (a sessão
                    // temporária já foi apagada por ele).
                    if (data.salvo_no_banco) {
                        exibirSucessoModal();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Atenção',
                            text: 'Pagamento aprovado, mas houve um erro ao gravar o agendamento. Entre em contato com o salão para confirmar seu horário.'
                        });
                    }
                }
            })
            .catch(err => console.error('Erro na verificação de status:', err));
    }, 5000);
}

// ====== SALVAMENTO NO BANCO E EXIBIÇÃO DE MODAL ======

function exibirSucessoModal() {
    Swal.fire({
        icon: 'success',
        title: 'Agendamento Confirmado!',
        text: 'Seu pagamento foi aprovado e o horário está garantido.',
        confirmButtonText: 'Ver meus agendamentos',
        confirmButtonColor: '#28a745',
        allowOutsideClick: false
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'meuagenda.php';
        }
    });
}

// (a função de finalização automática foi removida — o asaas_config.php
// já grava o agendamento no banco por conta própria, tanto no Pix quanto
// no cartão, e devolve isso via `salvo_no_banco` na própria resposta)

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
    const getVal = (id) => {
        const el = document.getElementById(id);
        return el ? el.value.trim() : '';
    };

    const nome = getVal('cliente_nome');
    const cpf = getVal('cliente_cpf');
    const valor = getVal('valor_sinal');

    if (!nome) {
        Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Preencha seu nome completo.' });
        return;
    }

    const formData = new FormData();
    formData.append('metodo', metodo);
    formData.append('nome', nome);
    formData.append('cpf', cpf);
    formData.append('valor', valor);

    let btnAtual = null;
    if (metodo === 'PIX') btnAtual = document.getElementById('btn-gerar-pix');
    if (metodo === 'CREDIT_CARD') btnAtual = document.getElementById('btn-pagar-credito');
    if (metodo === 'DEBIT_CARD') btnAtual = document.getElementById('btn-pagar-debito');

    if (btnAtual) {
        btnAtual.disabled = true;
        btnAtual.dataset.textoOriginal = btnAtual.innerText;
        btnAtual.innerText = 'Processando...';
    }

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
                throw new Error('Servidor retornou um erro do PHP.');
            }
        })
        .then(data => {
            if (data.sucesso) {
                if (data.tipo === 'CARD_REDIRECT') {
                    // Crédito/débito: abre a página de pagamento do Asaas e
                    // continua verificando em segundo plano (igual ao Pix).
                    const ehDebito = data.metodo === 'DEBIT_CARD';
                    Swal.fire({
                        icon: 'info',
                        title: ehDebito ? 'Pagamento no débito' : 'Pagamento no crédito',
                        html: ehDebito
                            ? 'Na página do Asaas, <strong>clique em "Cartão de Débito"</strong> antes de preencher os dados (ela abre em Crédito). Depois de pagar, volte para esta página.'
                            : 'Na página do Asaas, deixe selecionado <strong>"Cartão de Crédito"</strong> e preencha os dados. Depois de pagar, volte para esta página.',
                        confirmButtonText: 'Ir para o pagamento',
                        allowOutsideClick: false
                    }).then(() => {
                        window.open(data.invoice_url, '_blank');
                    });

                    if (btnAtual) {
                        btnAtual.disabled = false;
                        btnAtual.innerText = btnAtual.dataset.textoOriginal;
                    }

                    verificarStatusPix(data.payment_id);
                } else if (data.tipo === 'PIX') {
                    const imgQr = document.getElementById('img-qrcode');
                    const inputCopia = document.getElementById('input-copia-cola');
                    const areaQr = document.getElementById('area-qr-code');

                    if (data.gerar_qr_localmente) {
                        // A imagem do Asaas falhou (comum no Sandbox) —
                        // gera o QR code no navegador a partir do
                        // código "copia e cola", usando a QRious.
                        if (imgQr && typeof QRious !== 'undefined') {
                            new QRious({
                                element: imgQr,
                                value: data.copia_cola,
                                size: 220
                            });
                        }
                    } else if (imgQr) {
                        imgQr.src = 'data:image/png;base64,' + data.qr_code;
                    }

                    if (inputCopia) inputCopia.value = data.copia_cola;
                    if (areaQr) areaQr.style.display = 'block';

                    if (btnAtual) btnAtual.style.display = 'none';

                    verificarStatusPix(data.payment_id);
                }
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Atenção',
                    text: data.mensagem || 'Erro ao processar o pagamento.'
                });
                if (btnAtual) {
                    btnAtual.disabled = false;
                    btnAtual.innerText = btnAtual.dataset.textoOriginal;
                }
            }
        })
        .catch(err => {
            console.error('Erro de execução:', err);
            Swal.fire({
                icon: 'error',
                title: 'Erro de Conexão',
                text: 'Ocorreu um erro no servidor. Verifique o console do navegador.'
            });
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

        Swal.fire({
            icon: 'success',
            title: 'Copiado!',
            text: 'Código Pix copiado para a área de transferência.',
            timer: 2000,
            showConfirmButton: false
        });
    }
}