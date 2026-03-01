   function mostrarTela(tela) {

    // esconder todas as telas
    document.querySelectorAll('.tela').forEach(div => {
        div.classList.remove('ativa');
    });

    // mostrar tela clicada
    document.getElementById(tela).classList.add('ativa');

    // remover active dos botões
    document.querySelectorAll('.menu button').forEach(btn => {
        btn.classList.remove('active');
    });

    // adicionar active no botão clicado
    event.target.classList.add('active');
} 
document.addEventListener("DOMContentLoaded", function () {

    const area = document.getElementById("servicos-area");
    const mais = document.getElementById("fab");

    // 🔵 Criar Card
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

        // Se já existe no banco → começa bloqueado
        if (id !== "") {
            nomeInput.readOnly = true;
            precoInput.readOnly = true;
            editarBtn.innerText = "Editar";
        }

        let modo = id === "" ? "novo" : "bloqueado";

        // 🔹 SALVAR / EDITAR
        editarBtn.addEventListener("click", () => {

            const novoNome = nomeInput.value.trim();
            const novoPreco = precoInput.value.trim();

            if (novoNome === "" || novoPreco === "") {
                alert("Preencha todos os campos!");
                return;
            }

            // 🟢 NOVO SERVIÇO
            if (modo === "novo") {

                fetch("salvar_servico.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: `nome=${encodeURIComponent(novoNome)}&preco=${encodeURIComponent(novoPreco)}`
                })
                .then(res => res.text())
                .then(msg => {
                    alert(msg);
                    carregarServicos();
                });

            }

            // 🔵 LIBERAR PARA EDITAR
            else if (modo === "bloqueado") {

                nomeInput.readOnly = false;
                precoInput.readOnly = false;

                editarBtn.innerText = "Salvar Alterações";
                modo = "editando";
            }

            // 🟡 SALVAR ALTERAÇÃO
            else if (modo === "editando") {

                fetch("editar_servico.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
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

        // 🔴 EXCLUIR
        deleteBtn.addEventListener("click", () => {

            if (id === "") {
                box.remove();
                return;
            }

            if (!confirm("Tem certeza que deseja excluir?")) {
                return;
            }

            fetch("excluir_servico.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
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

    // 🔄 Carregar serviços
    function carregarServicos() {

        fetch("salvar_servico.php")
        .then(res => res.json())
        .then(dados => {

            area.innerHTML = "";

            dados.forEach(servico => {
                const card = criarServico(servico.id, servico.nome, servico.preco);
                area.appendChild(card);
            });

        })
        .catch(erro => {
            console.error("Erro ao carregar:", erro);
        });

    }

    // ➕ Botão +
    mais.addEventListener("click", () => {
        const novo = criarServico();
        area.appendChild(novo);
    });

    // Carrega ao abrir
    carregarServicos();

});

function verMais(index) {
    const linha = document.getElementById("detalhes-" + index);

    if (linha.style.display === "none") {
        linha.style.display = "table-row";
    } else {
        linha.style.display = "none";
    }
}
 

function atualizarTabelaClientes() {
    // 1. Identificar quais linhas de detalhes estão abertas (para não fechá-las)
    let detalhesAbertos = [];
    document.querySelectorAll('[id^="detalhes-"]').forEach(linha => {
        if (linha.style.display !== 'none') {
            detalhesAbertos.push(linha.id);
        }
    });

    // 2. Busca o HTML atualizado do arquivo PHP
    fetch('get_clientes.php')
        .then(response => response.text())
        .then(htmlDasLinhas => {
            // 3. Atualiza APENAS o corpo da tabela
            document.getElementById('corpo-tabela-clientes').innerHTML = htmlDasLinhas;

            // 4. Reabre as linhas de detalhes que estavam abertas
            detalhesAbertos.forEach(id => {
                let linha = document.getElementById(id);
                if (linha) {
                    linha.style.display = ''; 
                }
            });
        })
        .catch(error => console.error('Erro ao buscar clientes:', error));
}

// 5. Configura para atualizar a cada 5 segundos (5000 milissegundos)
setInterval(atualizarTabelaClientes, 5000);

// Executa a primeira vez ao carregar a página
atualizarTabelaClientes();