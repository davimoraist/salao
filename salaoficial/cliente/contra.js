  const cliente = document.querySelector('.cliente');
const cadastro = document.querySelector('#conecte');
const conecte = document.querySelector('#cadastro');

cadastro.addEventListener('click', () => {
    cliente.classList.add('activo');
});

conecte.addEventListener('click', () => {
    cliente.classList.remove('activo');
});
