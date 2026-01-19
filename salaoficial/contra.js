 const clientes = document.querySelector('.clientes');
const btnTroca = document.getElementById('btnTroca');

function trocarPainel() {
    clientes.classList.toggle('ativo');

    if (clientes.classList.contains('ativo')) {
        btnTroca.textContent = 'Login';
    } else {
        btnTroca.textContent = 'Cadastro';
    }
}
