const clientes = document.querySelector('.clientes');
const btnTroca = document.getElementById('btnTroca');
const textoPainel = document.getElementById('textoPainel');
const textsalao = document.getElementById('textsalao');
const textcriar = document.getElementById('criar');
const animacao = document.getElementById('animacao');

function trocarPainel() {
    const ativo = clientes.classList.toggle('ativo');

    if (ativo) {
        btnTroca.textContent = 'Login';
        textoPainel.textContent = 'crie sua conta!';
        textsalao.innerHTML = `
            Um espaço feito para <br>
            cuidar da beleza e do <br>
            bem-estar da mulher
        `;
        textcriar.textContent = 'Já tem conta? Acesse sua conta';

        animacao.classList.add('cadastro');
        animacao.classList.remove('login');
    } else {
        btnTroca.textContent = 'Conta';
        textoPainel.textContent = 'seja bem-vindo!';
        textsalao.innerHTML = `
            Cuidando da beleza da <br>
            mulher com carinho e <br>
            excelência
        `;
        textcriar.textContent = 'Ainda não tem uma conta? Crie uma.';

        animacao.classList.add('login');
        animacao.classList.remove('cadastro');
    }
}
