document.addEventListener('DOMContentLoaded', () => {
    // Código de inicialização caso necessário no futuro
});

/**
 * Função para cancelar o serviço com confirmação e feedback visual
 * @param {number|string} idAgendamento - ID único do agendamento a ser cancelado
 */
function cancelar(idAgendamento) {
    if (typeof Swal === 'undefined') {
        if (confirm("Deseja cancelar este agendamento?")) {
            executarCancelamento(idAgendamento);
        }
        return;
    }

    Swal.fire({
        title: 'Cancelar Serviço',
        text: 'Deseja cancelar este serviço? Ao confirmar, o valor pago a título de reserva (sinal) não será reembolsado.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#4CAF50',
        confirmButtonText: 'Sim, cancelar',
        cancelButtonText: 'Manter agendamento'
    }).then((result) => {
        if (result.isConfirmed) {
            executarCancelamento(idAgendamento);
        }
    });
}

/**
 * Envia a requisição de cancelamento para o servidor PHP
 * @param {number|string} idAgendamento - ID do agendamento
 */
function executarCancelamento(idAgendamento) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Cancelando...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
    }

    const formData = new FormData();
    if (idAgendamento) {
        formData.append('id_agendamento', idAgendamento);
    }

    fetch('cancelar_agendamento.php', {
        method: 'POST',
        credentials: 'same-origin',
        body: formData
    })
        .then(async res => {
            const texto = await res.text();
            try {
                return JSON.parse(texto);
            } catch (e) {
                console.error('Resposta do servidor não é um JSON válido:', texto);
                throw new Error('Servidor retornou uma resposta inesperada.');
            }
        })
        .then(resultado => {
            if (resultado.sucesso) {
                const acaoAposSucesso = () => {
                    if (idAgendamento) {
                        const card = document.getElementById('agendamento-' + idAgendamento);
                        if (card) {
                            card.remove();
                            return;
                        }
                    }
                    location.reload();
                };

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Cancelado!',
                        text: 'Seu agendamento foi cancelado com sucesso.',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(acaoAposSucesso);
                } else {
                    alert('Agendamento cancelado com sucesso!');
                    acaoAposSucesso();
                }
            } else {
                const mensagemErro = resultado.erro || 'Não foi possível cancelar o agendamento.';
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: mensagemErro,
                        confirmButtonColor: '#d33'
                    });
                } else {
                    alert(mensagemErro);
                }
            }
        })
        .catch(err => {
            console.error('Erro ao cancelar agendamento:', err);
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Falha de conexão',
                    text: 'Não foi possível comunicar com o servidor.'
                });
            } else {
                alert('Erro ao comunicar com o servidor.');
            }
        });
}