document.addEventListener('DOMContentLoaded', () => {
    carregarAgendamento();
});

/**
 * Procura os dados do agendamento e pagamento do cliente no servidor
 */
function carregarAgendamento() {
    fetch('obter_agendamento.php', {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        }
    })
        .then(async response => {
            const texto = await response.text();
            try {
                return JSON.parse(texto);
            } catch (e) {
                console.error('Resposta do servidor não é um JSON válido:', texto);
                throw new Error('Erro ao processar resposta do servidor.');
            }
        })
        .then(data => {
            if (data.sucesso) {
                // Preenche os campos do HTML com os dados devolvidos
                document.getElementById('nome').textContent = data.agendamento.cliente_nome || 'N/D';
                document.getElementById('servico').textContent = data.agendamento.servico || 'N/D';
                document.getElementById('data').textContent = data.agendamento.data_formatada || data.agendamento.data;
                document.getElementById('hora').textContent = data.agendamento.hora;

                // Formatação dos valores monetários
                const entrada = parseFloat(data.agendamento.valor_sinal || 0);
                const total = parseFloat(data.agendamento.preco_servico || 0);
                const falta = total - entrada;

                document.getElementById('entrada').textContent = `R$ ${entrada.toFixed(2).replace('.', ',')}`;
                document.getElementById('falta').textContent = `R$ ${falta.toFixed(2).replace('.', ',')}`;
            } else {
                document.getElementById('nome').textContent = 'Nenhum agendamento encontrado';
                document.getElementById('servico').textContent = '-';
                document.getElementById('data').textContent = '-';
                document.getElementById('hora').textContent = '-';
            }
        })
        .catch(error => {
            console.error('Erro ao carregar agendamento:', error);
        });
}

/**
 * Função para cancelar o serviço com validação e aviso sobre o sinal
 */
function cancelar() {
    // Garante que o SweetAlert2 está disponível
    if (typeof Swal === 'undefined') {
        if (confirm("Deseja cancelar este serviço? Ao confirmar, o valor pago a título de reserva (sinal) não será reembolsado.")) {
            executarCancelamento();
        }
        return;
    }

    Swal.fire({
        title: 'Cancelar Serviço',
        text: 'Deseja cancelar este serviço? Ao confirmar, o valor pago a título de reserva (sinal) não será reembolsado, conforme a política de agendamento. ',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#4CAF50',
        confirmButtonText: 'Sim, cancelar',
        cancelButtonText: 'Manter agendamento'
    }).then((result) => {
        if (result.isConfirmed) {
            executarCancelamento();
        }
    });
}

/**
 * Envia a requisição de cancelamento para o servidor PHP
 */
function executarCancelamento() {
    fetch('cancelar_agendamento.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        }
    })
        .then(async response => {
            const texto = await response.text();
            try {
                return JSON.parse(texto);
            } catch (e) {
                throw new Error('Erro na resposta do servidor.');
            }
        })
        .then(data => {
            if (data.sucesso) {
                Swal.fire({
                    icon: 'success',
                    title: 'Cancelado!',
                    text: 'O seu agendamento foi cancelado com sucesso.',
                    confirmButtonColor: '#28a745'
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: data.erro || 'Não foi possível cancelar o agendamento.',
                    confirmButtonColor: '#d33'
                });
            }
        })
        .catch(error => {
            console.error('Erro ao processar cancelamento:', error);
            Swal.fire({
                icon: 'error',
                title: 'Falha de conexão',
                text: 'Ocorreu um erro ao comunicar com o servidor.'
            });
        });
}