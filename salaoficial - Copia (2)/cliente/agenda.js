let hoje = new Date();
let inicio = 0;
let dataSelecionada = null;
let pedidoHorarios = 0; // evita que uma resposta antiga sobrescreva a mais nova

function paraISO(data) {
    const ano = data.getFullYear();
    const mes = String(data.getMonth() + 1).padStart(2, '0');
    const dia = String(data.getDate()).padStart(2, '0');
    return `${ano}-${mes}-${dia}`;
}

function dataDoDia(i) {
    const d = new Date();
    d.setDate(hoje.getDate() + i);
    return d;
}

// diaBloqueado() vem do agenda.php (períodos de emergência / ausência da Maria)
function estaBloqueado(data) {
    return typeof window.diaBloqueado === "function" && window.diaBloqueado(paraISO(data));
}

// Primeiro dia (a partir de hoje) que não é domingo e não está bloqueado
function primeiroDiaLivre() {
    for (let i = 0; i < 366; i++) {
        const d = dataDoDia(i);
        if (d.getDay() !== 0 && !estaBloqueado(d)) {
            return i;
        }
    }
    return 0;
}

// Primeiro dia livre DEPOIS do índice informado (-1 se não houver)
function proximoDiaLivreApos(indice) {
    for (let k = indice + 1; k <= indice + 366; k++) {
        const d = dataDoDia(k);
        if (d.getDay() !== 0 && !estaBloqueado(d)) {
            return k;
        }
    }
    return -1;
}

// Alerta: o salão não atende nesse dia, com atalho para a próxima data livre
function avisarDiaFechado(indice) {

    const proximo = proximoDiaLivreApos(indice);
    const texto = "O salão não tem horários neste dia. Escolha outra data.";

    if (typeof Swal === "undefined") {
        alert(texto);
        return;
    }

    Swal.fire({
        icon: "info",
        title: "Salão fechado neste dia",
        text: texto,
        showCancelButton: proximo !== -1,
        confirmButtonText: proximo !== -1 ? "Ir para a próxima data livre" : "Ok",
        cancelButtonText: "Fechar",
        confirmButtonColor: "#734440"
    }).then(resultado => {
        if (resultado.isConfirmed && proximo !== -1) {
            inicio = proximo;
            gerarDatas();
        }
    });
}

function mostrarMensagem(container, texto) {
    container.innerHTML = "";
    const p = document.createElement("p");
    p.innerText = texto;
    p.style.cssText = "text-align:center;padding:14px;color:#734440;font-size:15px;";
    container.appendChild(p);
}

function gerarDatas() {

    const container = document.getElementById("datas");
    container.innerHTML = "";

    let count = 0;
    let i = inicio;
    let selecionou = false;
    let ultimoIndice = inicio;

    while (count < 3) {

        const novaData = dataDoDia(i);

        if (novaData.getDay() !== 0) {

            ultimoIndice = i;
            const indice = i;

            const dia = String(novaData.getDate()).padStart(2, '0');
            const mes = String(novaData.getMonth() + 1).padStart(2, '0');

            const div = document.createElement("div");
            div.className = "data-box";
            div.innerText = `${dia}/${mes}`;

            const bloqueado = estaBloqueado(novaData);

            if (bloqueado) {
                // dia fechado: aparece apagado e, ao clicar, mostra o alerta
                div.classList.add("bloqueado");
                div.title = "Salão fechado neste dia";
                div.style.opacity = "0.4";
                div.style.textDecoration = "line-through";
                div.style.cursor = "not-allowed";
                div.onclick = () => avisarDiaFechado(indice);
            } else {
                div.onclick = () => selecionarData(div, novaData);
            }

            container.appendChild(div);

            // seleciona automaticamente o primeiro dia LIVRE entre os 3 mostrados
            if (!bloqueado && !selecionou) {
                selecionou = true;
                selecionarData(div, novaData);
            }

            count++;
        }

        i++;
    }

    // os 3 dias mostrados estão fechados: nada selecionado
    if (!selecionou) {
        dataSelecionada = null;
        pedidoHorarios++; // cancela qualquer busca de horários em andamento
        document.getElementById("dataSelecionada").value = "";
        document.getElementById("horaSelecionada").value = "";
        mostrarMensagem(
            document.getElementById("horarios"),
            "O salão não atende nestes dias. Use a seta ▶ para ver outras datas."
        );
        avisarDiaFechado(ultimoIndice);
    }
}

function selecionarData(elemento, data) {

    document.querySelectorAll(".data-box").forEach(el => {
        el.classList.remove("ativa");
    });

    elemento.classList.add("ativa");

    dataSelecionada = data;

    document.getElementById("dataSelecionada").value = paraISO(data);

    gerarHorarios();
}

function selecionarHora(hora, elemento) {

    document.getElementById("horaSelecionada").value = hora;

    document.querySelectorAll(".hora").forEach(el => {
        el.classList.remove("ativa");
    });

    elemento.classList.add("ativa");
}

function mover(direcao) {

    inicio += direcao;

    if (inicio < 0) {
        inicio = 0;
    }

    gerarDatas();
}

function gerarHorarios() {

    const container = document.getElementById("horarios");
    container.innerHTML = "";

    // ao trocar de dia, descarta a hora do dia anterior
    document.getElementById("horaSelecionada").value = "";

    const meuPedido = ++pedidoHorarios;

    if (!dataSelecionada || estaBloqueado(dataSelecionada)) {
        mostrarMensagem(container, "O salão não atende neste dia.");
        return;
    }

    fetch("buscar_horarios.php?data=" + paraISO(dataSelecionada))
        .then(response => {
            if (!response.ok) {
                throw new Error("HTTP " + response.status);
            }
            return response.json();
        })
        .then(horariosOcupados => {

            if (meuPedido !== pedidoHorarios) return; // resposta atrasada, ignora

            if (!Array.isArray(horariosOcupados)) {
                throw new Error("resposta inesperada de buscar_horarios.php");
            }

            // normaliza formato da hora
            horariosOcupados = horariosOcupados.map(h => String(h).substring(0, 5));

            let horarios = [];

            for (let h = 7; h < 12; h++) {
                horarios.push(String(h).padStart(2, '0') + ":00");
            }

            for (let h = 13; h <= 18; h++) {
                horarios.push(String(h).padStart(2, '0') + ":00");
            }

            let agora = new Date();

            horarios.forEach(hora => {

                let [h, m] = hora.split(":");

                let horaData = new Date(dataSelecionada);
                horaData.setHours(Number(h), Number(m));

                if (dataSelecionada.toDateString() === agora.toDateString()) {
                    if (horaData < agora) {
                        return;
                    }
                }

                let div = document.createElement("div");
                div.className = "hora";
                div.innerText = hora;

                if (horariosOcupados.includes(hora)) {

                    div.classList.add("ocupado");
                    div.style.pointerEvents = "none";

                } else {

                    div.onclick = () => selecionarHora(hora, div);

                }

                container.appendChild(div);

            });

            if (!container.children.length) {
                mostrarMensagem(container, "Não há horários disponíveis neste dia.");
            }

        })
        .catch(erro => {
            if (meuPedido !== pedidoHorarios) return;
            console.error("Erro ao carregar horários:", erro);
            mostrarMensagem(container, "Não foi possível carregar os horários. Atualize a página.");
        });

}

// começa no primeiro dia livre (pula domingos e dias bloqueados)
inicio = primeiroDiaLivre();
gerarDatas();