 

const clientes = document.querySelector('.clientes');
const btnTroca = document.getElementById('btnTroca');
const textoPainel = document.getElementById('textoPainel');
const textsalao = document.getElementById('textsalao');
const textcriar = document.getElementById('criar');


function trocarPainel() {
    clientes.classList.toggle('ativo');

    if (clientes.classList.contains('ativo')) {
        btnTroca.textContent = 'Login';
        textoPainel.textContent = 'crie sua conta!';
       // textsalao.textContent = 'Um espaço feito para cuidar da beleza e do bem-estar da mulher.'
       textsalao.innerHTML = "Um espaço feito para <br> cuidar da beleza e do <br> bem-estar da mulher"
       textcriar.textContent = 'Já tem cadastro? Acesse sua conta'
    } else {
        btnTroca.textContent = 'Conta';
        textoPainel.textContent = 'seja bem-vindo!';
        textsalao.innerHTML = "Cuidando da beleza da<br>mulher com carinho e<br>excelência";
        textcriar.textContent = 'Ainda não tem uma conta? Crie uma.'
    }
}
