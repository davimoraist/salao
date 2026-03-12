 // ===================== TROCAR TELAS DO PAINEL =====================
function mostrarTela(tela, event) {
    document.querySelectorAll('.tela').forEach(div => div.classList.remove('ativa'));
    document.getElementById(tela).classList.add('ativa');

    document.querySelectorAll('.menu button').forEach(btn => btn.classList.remove('active'));
    if (event) event.target.classList.add('active');
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

        // ===================== BOTÃO EDITAR/SALVAR =====================
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

        // ===================== BOTÃO EXCLUIR =====================
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
            areaServicos.innerHTML = "";
            dados.forEach(servico => {
                const card = criarServico(servico.id, servico.nome, servico.preco);
                areaServicos.appendChild(card);
            });
        })
        .catch(erro => console.error("Erro ao carregar serviços:", erro));
    }

    // ===================== BOTÃO NOVO SERVIÇO =====================
    btnNovoServico.addEventListener("click", () => {
        const novo = criarServico();
        areaServicos.appendChild(novo);
    });

    // ===================== FUNÇÃO VER MAIS =====================
    window.verMais = function(id) {
        const detalhes = document.getElementById("detalhes-" + id);
        if (detalhes) {
            detalhes.style.display = (detalhes.style.display === "none" || detalhes.style.display === "") ? "table-row" : "none";
        }
    };

    // ===================== CARREGAR CLIENTES =====================
    function atualizarTabelaClientes() {
        // Salvar quais detalhes estavam abertos
        let detalhesAbertos = [];
        document.querySelectorAll('[id^="detalhes-"]').forEach(linha => {
            if (linha.style.display !== 'none') detalhesAbertos.push(linha.id);
        });

        fetch("get_clientes.php")
        .then(res => res.text())
        .then(html => {
            const corpo = document.getElementById("corpo-tabela-clientes");
            if (corpo) corpo.innerHTML = html;

            // Restaurar linhas abertas
            detalhesAbertos.forEach(id => {
                const linha = document.getElementById(id);
                if (linha) linha.style.display = "";
            });
        })
        .catch(err => console.error("Erro ao buscar clientes:", err));
    }

    // Atualizar clientes a cada 5 segundos
    setInterval(atualizarTabelaClientes, 5000);

    // ===================== EXECUÇÃO INICIAL =====================
    carregarServicos();
    atualizarTabelaClientes();

});