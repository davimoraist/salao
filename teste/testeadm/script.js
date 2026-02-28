// script.js

function atualizarTabela() {
    // Busca apenas o conteúdo novo do arquivo PHP secundário
    fetch('get_tabela.php')
        .then(response => response.text())
        .then(data => {
            // Insere o HTML recebido dentro do container da tabela
            document.getElementById('container-tabela').innerHTML = data;
        })
        .catch(error => console.error('Erro ao atualizar:', error));
}

// Executa ao carregar a página
window.onload = function() {
    atualizarTabela(); // Carrega imediatamente
    setInterval(atualizarTabela, 5000); // Atualiza a cada 5 segundos
};