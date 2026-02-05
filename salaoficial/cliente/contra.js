  const cliente = document.querySelector('.cliente');
const cadastro = document.querySelector('#conecte');
const conecte = document.querySelector('#cadastro');

cadastro.addEventListener('click', () => {
    cliente.classList.add('activo');
});

conecte.addEventListener('click', () => {
    cliente.classList.remove('activo');
});


 document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.toggle-senha').forEach(icon => {
        icon.addEventListener('click', () => {
            const input = icon.previousElementSibling;

            if (!input) return;

            if (input.type === 'password') {
                input.type = 'text';
                icon.textContent = 'visibility_off';
            } else {
                input.type = 'password';
                icon.textContent = 'visibility';
            }
        });
    });
});
