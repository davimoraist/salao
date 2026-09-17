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
            window.location.href = 'agenda.php';
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
                if (data.tipo === 'PIX') {
                    const imgQr = document.getElementById('img-qrcode');
                    const inputCopia = document.getElementById('input-copia-cola');
                    const areaQr = document.getElementById('area-qr-code');

                    if (imgQr) imgQr.src = 'data:image/png;base64,' + data.qr_code;
                    if (inputCopia) inputCopia.value = data.copia_cola;
                    if (areaQr) areaQr.style.display = 'block';

                    if (btnAtual) btnAtual.style.display = 'none';

                    verificarStatusPix(data.payment_id);
                } else {
                    // Cartão de crédito/débito: o asaas_config.php já
                    // gravou o agendamento no banco nesse mesmo request
                    // (dentro do bloco CREDIT_CARD/DEBIT_CARD) e devolveu
                    // salvo_no_banco na resposta.
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