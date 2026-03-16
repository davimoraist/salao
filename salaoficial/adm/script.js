 // ===================== VARIÁVEIS GLOBAIS =====================
let clienteParaAbrir = null; 

// ===================== TROCAR TELAS DO PAINEL =====================
function mostrarTela(tela, id = null) {
    clienteParaAbrir = id; // Guarda o ID se houver um

    // 1. Troca a tela
    document.querySelectorAll('.tela').forEach(div => div.classList.remove('ativa'));
    const alvo = document.getElementById(tela);
    if(alvo) alvo.classList.add('ativa');

    // 2. Marca o menu lateral
    document.querySelectorAll('.menu button').forEach(btn => {
        btn.classList.remove('active');
        let acao = btn.getAttribute('onclick') || "";
        if (acao.includes("'" + tela + "'")) {
            btn.classList.add('active');
        }
    });

    // 3. Se o ID já existir na tabela atual, abre na hora
    if (id) {
        verMais(id);
    }
}

// ===================== FUNÇÃO VER MAIS (DETALHES DA LINHA) =====================
window.verMais = function(id) {
    const detalhes = document.getElementById("detalhes-" + id);
    if (detalhes) {
        detalhes.style.display = (detalhes.style.display === "none" || detalhes.style.display === "") ? "table-row" : "none";
    }
};

// ===================== CARREGAR CLIENTES (AJAX) =====================
 function atualizarTabelaClientes() {
    // 1. ANTES de atualizar, vamos ver qual detalhe está aberto agora
    let idAbertoNoMomento = null;
    document.querySelectorAll('[id^="detalhes-"]').forEach(linha => {
        if (linha.style.display !== 'none') {
            // Pega apenas o número do ID (ex: detalhes-5 vira 5)
            idAbertoNoMomento = linha.id.replace('detalhes-', '');
        }
    });

    fetch("get_clientes.php")
    .then(res => res.text())
    .then(html => {
        const corpo = document.getElementById("corpo-tabela-clientes");
        if (corpo) {
            corpo.innerHTML = html;

            // 2. Se tinha alguém aberto ou se viemos da agenda, abre agora
            let idParaAbrir = clienteParaAbrir || idAbertoNoMomento;

            if (idParaAbrir) {
                verMais(idParaAbrir);
                clienteParaAbrir = null; // Limpa a variável da agenda
            }
        }
    })
    .catch(err => console.error("Erro ao buscar clientes:", err));
}
// ===================== SCRIPT PRINCIPAL =====================
document.addEventListener("DOMContentLoaded", function() {

    const areaServicos = document.getElementById("servicos-area");
    const btnNovoServico = document.getElementById("fab");

    // ===================== CRIAR SERVIÇO =====================
    function criarServico(id = "", nome = "", preco = "") {
        const box = document.createElement("div");
        box.classList.add("servico-box");

        box.innerHTML = `
            <input type="text" value="${nome}" class="nome-input" placeholder="Nome do Serviço">
            <input type="text" value="${preco}" class="preco-input" placeholder="Preço">
            <button class="editar-btn">Salvar</button>
            <button class="delete-btn">Excluir</button>
        `;

        const nomeInput = box.querySelector(".nome-input");
        const precoInput = box.querySelector(".preco-input");
        const editarBtn = box.querySelector(".editar-btn");
        const deleteBtn = box.querySelector(".delete-btn");

        // MÁSCARA DE PREÇO
        precoInput.addEventListener("input", function() {
            let valor = precoInput.value.replace(/\D/g, "");
            if (valor === "") { precoInput.value = ""; return; }
            valor = (parseInt(valor) / 100).toFixed(2);
            valor = valor.replace(".", ",").replace(/\B(?=(\d{3})+(?!\d))/g, ".");
            precoInput.value = "R$ " + valor;
        });

        if (id !== "") {
            nomeInput.readOnly = true;
            precoInput.readOnly = true;
            editarBtn.innerText = "Editar";
        }

        let modo = id === "" ? "novo" : "bloqueado";

        editarBtn.addEventListener("click", () => {
            const novoNome = nomeInput.value.trim();
            const novoPreco = precoInput.value.replace("R$ ", "").replace(/\./g, "").replace(",", ".").trim();

            if (novoNome === "" || novoPreco === "") { alert("Preencha todos os campos!"); return; }

            if (modo === "novo") {
                fetch("editar_servico.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: `nome=${encodeURIComponent(novoNome)}&preco=${encodeURIComponent(novoPreco)}`
                })
                .then(res => res.text())
                .then(msg => {
                    alert(msg);
                    carregarServicos();
                });
            } else if (modo === "bloqueado") {
                nomeInput.readOnly = false;
                precoInput.readOnly = false;
                editarBtn.innerText = "Salvar Alterações";
                modo = "editando";
            } else if (modo === "editando") {
                fetch("editar_servico.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: `id=${id}&nome=${encodeURIComponent(novoNome)}&preco=${encodeURIComponent(novoPreco)}`
                })
                .then(res => res.text())
                .then(msg => {
                    alert(msg);
                    nomeInput.readOnly = true;
                    precoInput.readOnly = true;
                    editarBtn.innerText = "Editar";
                    modo = "bloqueado";
                });
            }
        });

        deleteBtn.addEventListener("click", () => {
            if (id === "") { box.remove(); return; }
            if (!confirm("Tem certeza que deseja excluir?")) return;

            fetch("excluir_servico.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `id=${id}`
            })
            .then(res => res.text())
            .then(msg => {
                alert(msg);
                carregarServicos();
            });
        });

        return box;
    }

    // ===================== CARREGAR SERVIÇOS =====================
    function carregarServicos() {
        fetch("salvar_servico.php")
        .then(res => res.json())
        .then(dados => {
            if (areaServicos) {
                areaServicos.innerHTML = "";
                dados.forEach(servico => {
                    const card = criarServico(servico.id, servico.nome, servico.preco);
                    areaServicos.appendChild(card);
                });
            }
        })
        .catch(erro => console.error("Erro ao carregar serviços:", erro));
    }

    if (btnNovoServico) {
        btnNovoServico.addEventListener("click", () => {
            const novo = criarServico();
            areaServicos.appendChild(novo);
        });
    }

    // Inicialização
    carregarServicos();
    atualizarTabelaClientes();
    setInterval(atualizarTabelaClientes, 5000);

});