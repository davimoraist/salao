// ====== CONTROLE DE EXIBIÇÃO DE TELAS DE PAGAMENTO ======

function esconderTodosMetodos() {
    if (document.getElementById('container-pix')) {
        document.getElementById('container-pix').style.display = 'none';
        document.getElementById('container-credito').style.display = 'none';
        document.getElementById('container-debito').style.display = 'none';
    }
}

function pixgera() {
    esconderTodosMetodos();
    document.getElementById('container-pix').style.display = 'block';
}

function creditopaga() {
    esconderTodosMetodos();
    document.getElementById('container-credito').style.display = 'block';
}

function debitopaga() {
    esconderTodosMetodos();
    document.getElementById('container-debito').style.display = 'block';
}