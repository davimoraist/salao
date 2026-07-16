// Função para esconder todas as formas de pagamento do lado direito
function esconderTodosMetodos() {
    document.getElementById('container-pix').style.display = 'none';
    document.getElementById('container-credito').style.display = 'none';
    document.getElementById('container-debito').style.display = 'none';
}

// Ativa a tela do PIX
function pixgera() {
    esconderTodosMetodos();
    document.getElementById('container-pix').style.display = 'block';
}

// Ativa a tela de Crédito
function creditopaga() {
    esconderTodosMetodos();
    document.getElementById('container-credito').style.display = 'block';
}

// Ativa a tela de Débito
function debitopaga() {
    esconderTodosMetodos();
    document.getElementById('container-debito').style.display = 'block';
}

// EXECUTA O CÁLCULO ASSIM QUE A PÁGINA CARREGAR
window.onload = function () {
    // 1. Captura o elemento do valor total e o do sinal
    var elementoTotal = document.getElementById("total");
    var elementoSinal = document.getElementById("valor-sinal");

    // 2. Só faz o cálculo se os dois elementos existirem na página atual
    if (elementoTotal && elementoSinal) {

        // Pega o texto (ex: "168,00" ou "R$ 168,00") e limpa para virar número (ex: 168.00)
        var textoTotal = elementoTotal.innerText;
        var valor = parseFloat(
            textoTotal
                .replace('R$', '')      // Remove o "R$" caso exista
                .replace(/\./g, '')     // Remove pontos de milhares
                .replace(',', '.')      // Troca a vírgula por ponto decimal
                .trim()                 // Tira os espaços sobressalentes
        );

        // Se a conversão deu certo e é um número válido
        if (!isNaN(valor)) {
            // 3. Define a porcentagem (30%)
            var por = 30;
            var calcula = por / 100; // Vira 0.3

            // 4. Faz a multiplicação
            var precoSinal = calcula * valor; // Ex: 168.00 * 0.3 = 50.4

            // 5. Formata o valor de volta para dinheiro (R$ 50,40)
            var precoFormatado = precoSinal.toLocaleString('pt-BR', {
                style: 'currency',
                currency: 'BRL'
            });

            // 6. MOSTRA NA TELA!
            elementoSinal.innerText = precoFormatado;
        }
    }
};