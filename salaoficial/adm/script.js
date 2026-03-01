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

    // 🔵 Função criar card
    function criarServico(id = "", nome = "", preco = "") {

        const box = document.createElement("div");
        box.classList.add("servico-box");

        box.innerHTML = `
            <input type="text" value="${nome}" class="nome-input" placeholder="Nome do Serviço">
            <input type="number" value="${preco}" class="preco-input" placeholder="Preço">

            <button class="editar-btn">Salvar</button>
            <button class="delete-btn">Excluir</button>
        `;

        // 🔹 SALVAR (novo ou editar)
        box.querySelector(".editar-btn").addEventListener("click", () => {

            const novoNome = box.querySelector(".nome-input").value;
            const novoPreco = box.querySelector(".preco-input").value;

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
                carregarServicos(); // atualiza lista
            });

        });

        // 🔴 EXCLUIR
        box.querySelector(".delete-btn").addEventListener("click", () => {

            if (id === "") {
                box.remove();
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
                carregarServicos(); // atualiza lista
            });

        });

        return box;
    }

    // 🔄 Carregar serviços do banco
    function carregarServicos() {

        fetch("listar_servicos.php")
        .then(res => res.json())
        .then(dados => {

            area.innerHTML = "";

            dados.forEach(servico => {
                const card = criarServico(servico.id, servico.nome, servico.preco);
                area.appendChild(card);
            });

        });

    }

    // ➕ Botão +
    mais.addEventListener("click", () => {
        const novo = criarServico();
        area.appendChild(novo);
    });

    // Carrega ao abrir página
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