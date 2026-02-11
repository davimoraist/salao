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

        if(novaData.getDay() !== 0){ // Bloqueia domingo
            let dia = String(novaData.getDate()).padStart(2,'0');
            let mes = String(novaData.getMonth()+1).padStart(2,'0');

            let div = document.createElement("div");
            div.className = "data-box";
            div.innerText = `${dia}/${mes}`;

            div.onclick = () => selecionarData(div, novaData);

            if(count === 0){
                div.classList.add("ativa");
                dataSelecionada = novaData;
                gerarHorarios();
            }

            container.appendChild(div);
            count++;
        }

        i++;
    }
}

function mover(direcao){
    inicio += direcao;
    if(inicio < 0) inicio = 0;
    gerarDatas();
}

function selecionarData(elemento, data){
    document.querySelectorAll(".data-box").forEach(el=>el.classList.remove("ativa"));
    elemento.classList.add("ativa");
    dataSelecionada = data;
    gerarHorarios();
}

function gerarHorarios(){
    const container = document.getElementById("horarios");
    container.innerHTML = "";

    let horarios = [];

    for(let h=7; h<12; h++){
        horarios.push(h+":00");
    }

    for(let h=13; h<=18; h++){
        horarios.push(h+":00");
    }

    let agora = new Date();

    horarios.forEach(hora=>{
        let [h,m] = hora.split(":");
        let horaData = new Date(dataSelecionada);
        horaData.setHours(h,m);

        if(dataSelecionada.toDateString() === agora.toDateString()){
            if(horaData < agora) return; // Não mostra horário passado
        }

        let div = document.createElement("div");
        div.className="hora";
        div.innerText=hora;

        div.onclick = ()=> enviarWhats(hora);

        container.appendChild(div);
    });
}
 
gerarDatas();