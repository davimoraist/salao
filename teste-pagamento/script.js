// Função para alternar as abas de pagamento
function mostrarPagamento(tipo) {
    let pagamentos = document.querySelectorAll('.pagamento');

    pagamentos.forEach(item => {
        item.style.display = 'none';
    });

    document.getElementById(tipo).style.display = 'block';
}

// Função para buscar o Pix gerado pelo PHP
async function gerarPixNoAsaas() {
    const btnGerar = document.getElementById('btn-gerar-pix');
    const divCarregando = document.getElementById('carregando-pix');
    const divDados = document.getElementById('dados-qrcode');

    // Mostra o carregando e esconde o botão original
    btnGerar.style.display = 'none';
    divCarregando.style.display = 'block';

    try {
        // Envia a requisição para o arquivo PHP processar
        const response = await fetch('criar-pix.php', {
            method: 'POST'
        });

        // Caso o PHP retorne um status de erro (ex: 400 ou 500)
        if (!response.ok) {
            throw new Error('Erro na resposta do servidor interno PHP');
        }

        const dadosPix = await response.json();

        // Se o Asaas retornou as propriedades corretas do QR Code através do PHP
        if (dadosPix.encodedImage && dadosPix.payload) {

            // 1. Injeta a imagem Base64 recebida
            document.getElementById('qrCodeImg').src = `data:image/png;base64,${dadosPix.encodedImage}`;

            // 2. Coloca a string Copia e Cola no input
            document.getElementById('pixCopiaCola').value = dadosPix.payload;

            // Altera os blocos visuais na tela
            divCarregando.style.display = 'none';
            divDados.style.display = 'block';
        } else {
            alert("Erro ao processar as informações do Pix.");
            resetarInterface(btnGerar, divCarregando);
        }

    } catch (error) {
        console.error("Erro na comunicação:", error);
        alert("Não foi possível gerar o PIX. Verifique se o servidor Apache está ativo.");
        resetarInterface(btnGerar, divCarregando);
    }
}

function resetarInterface(btn, carregando) {
    btn.style.display = 'block';
    carregando.style.display = 'none';
}