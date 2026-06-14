// =========================
// TROCAR LOGIN/CADASTRO
// =========================

const btnLogin = document.getElementById("btnLogin");
const btnCadastro = document.getElementById("btnCadastro");

const loginForm = document.getElementById("loginForm");
const cadastroForm = document.getElementById("cadastroForm");

btnLogin.addEventListener("click", () => {

    btnLogin.classList.add("active");
    btnCadastro.classList.remove("active");

    loginForm.classList.remove("hidden");
    cadastroForm.classList.add("hidden");

});

btnCadastro.addEventListener("click", () => {

    btnCadastro.classList.add("active");
    btnLogin.classList.remove("active");

    cadastroForm.classList.remove("hidden");
    loginForm.classList.add("hidden");

});

// =========================
// MOSTRAR / OCULTAR SENHA
// =========================

document.querySelectorAll(".material-symbols-outlined").forEach(botao => {

    botao.addEventListener("click", () => {

        const input = botao.parentElement.querySelector("input");

        if (input.type === "password") {

            input.type = "text";
            botao.textContent = "visibility_off";

        } else {

            input.type = "password";
            botao.textContent = "visibility";

        }

    });

});