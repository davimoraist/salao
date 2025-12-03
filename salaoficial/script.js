 function mostrarTela(tela) {

    // esconder todas as telas
    document.querySelectorAll('.tela').forEach(div => {
        div.classList.remove('ativa');
    });

    // mostrar tela clicada
    document.getElementById(tela).classList.add('ativa');

    // remover active dos botões
    document.querySelectorAll('.menu button').forEach(btn => {
        btn.classList.remove('active');
    });

    // adicionar active no botão clicado
    event.target.classList.add('active');
}
