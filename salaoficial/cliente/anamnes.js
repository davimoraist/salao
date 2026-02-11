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
