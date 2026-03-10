let hoje = new Date();
let inicio = 0;
let dataSelecionada = null;

function gerarDatas(){
    const container = document.getElementById("datas");
    container.innerHTML = "";

    let count = 0;
    let i = inicio;

    while(count < 3){

        let novaData = new Date();
        novaData.setDate(hoje.getDate() + i);

        if(novaData.getDay() !== 0){

            let dia = String(novaData.getDate()).padStart(2,'0');
            let mes = String(novaData.getMonth()+1).padStart(2,'0');

            let div = document.createElement("div");
            div.className = "data-box";
            div.innerText = `${dia}/${mes}`;

            div.onclick = () => selecionarData(div, novaData);

            if(count === 0){
                div.classList.add("ativa");
                selecionarData(div, novaData);
            }

            container.appendChild(div);
            count++;
        }

        i++;
    }
}

function selecionarData(elemento, data){

    document.querySelectorAll(".data-box").forEach(el=>el.classList.remove("ativa"));
    elemento.classList.add("ativa");

    dataSelecionada = data;

    let ano = data.getFullYear();
    let mes = String(data.getMonth()+1).padStart(2,'0');
    let dia = String(data.getDate()).padStart(2,'0');

    document.getElementById("dataSelecionada").value = `${ano}-${mes}-${dia}`;

    gerarHorarios();
}

function selecionarHora(hora, elemento){

    document.getElementById("horaSelecionada").value = hora;

    document.querySelectorAll(".hora").forEach(el=>el.classList.remove("ativa"));

    elemento.classList.add("ativa");
}

function mover(direcao){

    inicio += direcao;

    if(inicio < 0){
        inicio = 0;
    }

    gerarDatas();
}

function gerarHorarios(){

    const container = document.getElementById("horarios");
    container.innerHTML = "";

    let ano = dataSelecionada.getFullYear();
    let mes = String(dataSelecionada.getMonth()+1).padStart(2,'0');
    let dia = String(dataSelecionada.getDate()).padStart(2,'0');

    let dataFormatada = `${ano}-${mes}-${dia}`;

    fetch("buscar_horarios.php?data=" + dataFormatada)
    .then(response => response.json())
    .then(horariosOcupados => {

        let horarios = [];

        for(let h=7; h<12; h++){
            horarios.push(h + ":00");
        }

        for(let h=13; h<=18; h++){
            horarios.push(h + ":00");
        }

        let agora = new Date();

        horarios.forEach(hora=>{

            let [h,m] = hora.split(":");

            let horaData = new Date(dataSelecionada);
            horaData.setHours(h,m);

            if(dataSelecionada.toDateString() === agora.toDateString()){
                if(horaData < agora){
                    return;
                }
            }

            let horaBanco = hora + ":00";

            let div = document.createElement("div");
            div.className = "hora";
            div.innerText = hora;

            if(horariosOcupados.includes(horaBanco)){

                div.classList.add("ocupado");
                div.innerText = hora + " (ocupado)";

            }else{

                div.onclick = () => selecionarHora(hora, div);

            }

            container.appendChild(div);

        });

    });

}

gerarDatas();


// ATUALIZA AUTOMATICAMENTE OS HORÁRIOS
setInterval(() => {

    if(dataSelecionada){
        gerarHorarios();
    }

}, 3000);