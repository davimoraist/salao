const clientes = document.querySelector('.clientes');

function irParaCadastro(){
    clientes.classList.add('ativo');
}

function irParaLogin(){
    clientes.classList.remove('ativo');
}