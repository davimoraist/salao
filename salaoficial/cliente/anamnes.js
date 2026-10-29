var tele = document.getElementById("tel");
var cpf = document.getElementById("cpf");

tele.addEventListener('input', () => {
    let telefone = tele.value.length;

    // adiciona ") " depois do DDD
    if (telefone === 2){
        tele.value = '(' + tele.value + ') ';
    } 
    // adiciona "-" depois dos próximos números
    else if (telefone === 9){
        tele.value += '-';
    }
});


cpf.addEventListener('input', () => {
    let cpfnome = cpf.value.length;
    
    if (cpfnome === 3){
        cpf.value = cpf.value + '.';
    } 
    else if (cpfnome === 7){
        cpf.value = cpf.value + '.';
    }
    else if (cpfnome === 11){
        cpf.value = cpf.value + '-';
    }
});


document.addEventListener("DOMContentLoaded", function() {

    const radioOutros = document.getElementById("outros_conheceu");
    const campoOutros = document.getElementById("campo_outros");

    const radios = document.querySelectorAll('input[name="como_conheceu"]');

    radios.forEach(radio => {
        radio.addEventListener("change", function() {

            if (radioOutros.checked) {
                campoOutros.style.display = "block";
            } else {
                campoOutros.style.display = "none";
            }

        });
    });

});


document.addEventListener("DOMContentLoaded", function () {
    const form = document.querySelector(".form-box");
    const inputCpf = document.getElementById("cpf");
    const inputIdade = document.getElementById("idade");

    // Función para validar algoritmicamente el CPF
    function validarCPF(cpf) {
        cpf = cpf.replace(/\D/g, "");
        if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) return false;

        let soma = 0;
        let resto;

        for (let i = 1; i <= 9; i++) {
            soma += parseInt(cpf.substring(i - 1, i)) * (11 - i);
        }
        resto = (soma * 10) % 11;
        if (resto === 10 || resto === 11) resto = 0;
        if (resto !== parseInt(cpf.substring(9, 10))) return false;

        soma = 0;
        for (let i = 1; i <= 10; i++) {
            soma += parseInt(cpf.substring(i - 1, i)) * (12 - i);
        }
        resto = (soma * 10) % 11;
        if (resto === 10 || resto === 11) resto = 0;
        if (resto !== parseInt(cpf.substring(10, 11))) return false;

        return true;
    }

    // Máscara automática para CPF no formato 000.000.000-00
    if (inputCpf) {
        inputCpf.addEventListener("input", function (e) {
            let v = e.target.value.replace(/\D/g, "");
            v = v.replace(/(\d{3})(\d)/, "$1.$2");
            v = v.replace(/(\d{3})(\d)/, "$1.$2");
            v = v.replace(/(\d{3})(\d{1,2})$/, "$1-$2");
            e.target.value = v.substring(0, 14);
        });
    }

    // Intercepta o envio do formulário para validar
    if (form) {
        form.addEventListener("submit", function (e) {
            // 1. Validação do CPF
            if (inputCpf) {
                const cpfVal = inputCpf.value.trim();
                if (!validarCPF(cpfVal)) {
                    e.preventDefault();
                    alert("❌ CPF inválido! Por favor, insira um CPF válido.");
                    inputCpf.focus();
                    return false;
                }
            }

            // 2. Validação da Idade (+18 anos)
            if (inputIdade && inputIdade.value) {
                const dataNascimento = new Date(inputIdade.value);
                const hoje = new Date();

                let idade = hoje.getFullYear() - dataNascimento.getFullYear();
                const m = hoje.getMonth() - dataNascimento.getMonth();

                if (m < 0 || (m === 0 && hoje.getDate() < dataNascimento.getDate())) {
                    idade--;
                }

                if (idade < 18) {
                    e.preventDefault();
                    alert("❌ Cadastro permitido apenas para maiores de 18 anos!");
                    inputIdade.focus();
                    return false;
                }
            }
        });
    }
});