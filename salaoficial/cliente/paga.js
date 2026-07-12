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

// Ativa a tela de Débito (Corrigido o nome de deditopaga para debitopaga)
function debitopaga() {
    esconderTodosMetodos();
    document.getElementById('container-debito').style.display = 'block';
}