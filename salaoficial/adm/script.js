 // TROCAR TELAS DO PAINEL
function mostrarTela(tela, event){

    document.querySelectorAll('.tela').forEach(div=>{
        div.classList.remove('ativa');
    });

    document.getElementById(tela).classList.add('ativa');

    document.querySelectorAll('.menu button').forEach(btn=>{
        btn.classList.remove('active');
    });

    if(event){
        event.target.classList.add('active');
    }

}

document.addEventListener("DOMContentLoaded",function(){

    const area = document.getElementById("servicos-area");
    const mais = document.getElementById("fab");


    function criarServico(id="",nome="",preco=""){

        const box=document.createElement("div");
        box.classList.add("servico-box");

        box.innerHTML=`
        <input type="text" value="${nome}" class="nome-input" placeholder="Nome do Serviço">
        <input type="text" value="${preco}" class="preco-input" placeholder="Preço">
        <button class="editar-btn">Salvar</button>
        <button class="delete-btn">Excluir</button>
        `;

        const nomeInput=box.querySelector(".nome-input");
        const precoInput=box.querySelector(".preco-input");
        const editarBtn=box.querySelector(".editar-btn");
        const deleteBtn=box.querySelector(".delete-btn");


        // MASCARA DE PREÇO
        precoInput.addEventListener("input",function(){

            let valor=precoInput.value.replace(/\D/g,"");

            if(valor===""){
                precoInput.value="";
                return;
            }

            valor=(parseInt(valor)/100).toFixed(2);
            valor=valor.replace(".",",");
            valor=valor.replace(/\B(?=(\d{3})+(?!\d))/g,".");

            precoInput.value="R$ "+valor;

        });


        if(id!==""){
            nomeInput.readOnly=true;
            precoInput.readOnly=true;
            editarBtn.innerText="Editar";
        }

        let modo=id===""?"novo":"bloqueado";


        editarBtn.addEventListener("click",()=>{

            const novoNome=nomeInput.value.trim();

            const novoPreco=precoInput.value
            .replace("R$ ","")
            .replace(/\./g,"")
            .replace(",",".")
            .trim();

            if(novoNome===""||novoPreco===""){
                alert("Preencha todos os campos!");
                return;
            }


            if(modo==="novo"){

                fetch("editar_servico.php",{
                    method:"POST",
                    headers:{
                        "Content-Type":"application/x-www-form-urlencoded"
                    },
                    body:`nome=${encodeURIComponent(novoNome)}&preco=${encodeURIComponent(novoPreco)}`
                })
                .then(res=>res.text())
                .then(msg=>{
                    alert(msg);
                    carregarServicos();
                });

            }


            else if(modo==="bloqueado"){

                nomeInput.readOnly=false;
                precoInput.readOnly=false;

                editarBtn.innerText="Salvar Alterações";

                modo="editando";

            }


            else if(modo==="editando"){

                fetch("editar_servico.php",{
                    method:"POST",
                    headers:{
                        "Content-Type":"application/x-www-form-urlencoded"
                    },
                    body:`id=${id}&nome=${encodeURIComponent(novoNome)}&preco=${encodeURIComponent(novoPreco)}`
                })
                .then(res=>res.text())
                .then(msg=>{

                    alert(msg);

                    nomeInput.readOnly=true;
                    precoInput.readOnly=true;

                    editarBtn.innerText="Editar";

                    modo="bloqueado";

                });

            }

        });



        deleteBtn.addEventListener("click",()=>{

            if(id===""){
                box.remove();
                return;
            }

            if(!confirm("Tem certeza que deseja excluir?")){
                return;
            }

            fetch("excluir_servico.php",{
                method:"POST",
                headers:{
                    "Content-Type":"application/x-www-form-urlencoded"
                },
                body:`id=${id}`
            })
            .then(res=>res.text())
            .then(msg=>{
                alert(msg);
                carregarServicos();
            });

        });

        return box;

    }



    function carregarServicos(){

        fetch("listar_servicos.php")
        .then(res=>res.json())
        .then(dados=>{

            area.innerHTML="";

            dados.forEach(servico=>{

                const card=criarServico(servico.id,servico.nome,servico.preco);

                area.appendChild(card);

            });

        })
        .catch(erro=>{
            console.error("Erro ao carregar:",erro);
        });

    }



    mais.addEventListener("click",()=>{

        const novo=criarServico();

        area.appendChild(novo);

    });


    carregarServicos();

});



function verMais(index){

    const linha=document.getElementById("detalhes-"+index);

    if(linha.style.display==="none"){
        linha.style.display="table-row";
    }else{
        linha.style.display="none";
    }

}



function atualizarTabelaClientes(){

    let detalhesAbertos=[];

    document.querySelectorAll('[id^="detalhes-"]').forEach(linha=>{

        if(linha.style.display!=='none'){
            detalhesAbertos.push(linha.id);
        }

    });

    fetch("get_clientes.php")
    .then(response=>response.text())
    .then(htmlDasLinhas=>{

        const corpo=document.getElementById("corpo-tabela-clientes");

        if(corpo){
            corpo.innerHTML=htmlDasLinhas;
        }

        detalhesAbertos.forEach(id=>{

            let linha=document.getElementById(id);

            if(linha){
                linha.style.display="";
            }

        });

    })
    .catch(error=>console.error("Erro ao buscar clientes:",error));

}



// ATUALIZA CLIENTES AUTOMATICAMENTE
setInterval(atualizarTabelaClientes,5000);


// PRIMEIRA EXECUÇÃO
document.addEventListener("DOMContentLoaded",function(){
    atualizarTabelaClientes();
});