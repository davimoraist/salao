// ===================== VARIÁVEIS GLOBAIS =====================
let clienteParaAbrir = null;

// ===================== TROCAR TELAS =====================
function mostrarTela(tela, id = null) {
    clienteParaAbrir = id;

    document.querySelectorAll('.tela').forEach(div => div.classList.remove('ativa'));

    const alvo = document.getElementById(tela);
    if (alvo) alvo.classList.add('ativa');

    document.querySelectorAll('.menu button').forEach(btn => {
        btn.classList.remove('active');
        let acao = btn.getAttribute('onclick') || "";
        if (acao.includes("'" + tela + "'")) {
            btn.classList.add('active');
        }
    });

    if (id) verMais(id);
}

// ===================== VER MAIS CLIENTES =====================
window.verMais = function (id) {
    const detalhes = document.getElementById("detalhes-" + id);
    if (detalhes) {
        detalhes.style.display =
            (detalhes.style.display === "none" || detalhes.style.display === "")
                ? "table-row"
                : "none";
    }
};

// ===================== VER HISTÓRICO (LADO DIREITO) =====================
window.verhistorico = function (id) {
    // CORREÇÃO: Adicionado ?id= para que o PHP reconheça o parâmetro
    fetch("get.historico.php?id=" + id)
        .then(res => {
            if (!res.ok) throw new Error("Erro ao carregar arquivo");
            return res.text();
        })
        .then(html => {
            const painel = document.getElementById("conteudo-historico");
            if (painel) {
                painel.innerHTML = html;
            }
        })
        .catch(err => {
            console.error("Erro ao buscar histórico:", err);
            const painel = document.getElementById("conteudo-historico");
            if (painel) painel.innerHTML = "Erro ao carregar dados.";
        });
};

// ===================== ATUALIZAR TABELAS =====================
function atualizarTabelaClientes() {
    let idAberto = null;
    document.querySelectorAll('[id^="detalhes-"]').forEach(linha => {
        if (linha.style.display !== 'none') {
            idAberto = linha.id.replace('detalhes-', '');
        }
    });

    fetch("get_clientes.php")
        .then(res => res.text())
        .then(html => {
            const corpo = document.getElementById("corpo-tabela-clientes");
            if (corpo) {
                corpo.innerHTML = html;
                let idParaAbrir = clienteParaAbrir || idAberto;
                if (idParaAbrir) {
                    verMais(idParaAbrir);
                    clienteParaAbrir = null;
                }
            }
        })
        .catch(err => console.error("Erro clientes:", err));
}

function atualizarTabelaHistorico() {
    // Esta função atualiza a LISTA da esquerda
    fetch("get.historico.php")
        .then(res => res.text())
        .then(html => {
            const corpo = document.getElementById("corpo-tabela-historico");
            if (corpo) {
                corpo.innerHTML = html;
            }
        })
        .catch(err => console.error("Erro histórico:", err));
}

// ===================== SCRIPT PRINCIPAL =====================
document.addEventListener("DOMContentLoaded", function () {

    const areaServicos = document.getElementById("servicos-area");
    const btnNovoServico = document.getElementById("fab");

    // ===================== FUNÇÕES DE SERVIÇO =====================
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

        // Máscara de preço
        precoInput.addEventListener("input", function () {
            let valor = precoInput.value.replace(/\D/g, "");
            if (!valor) return precoInput.value = "";
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
            const n = nomeInput.value.trim();
            const p = precoInput.value.replace("R$ ", "").replace(/\./g, "").replace(",", ".");

            if (!n || !p) return alert("Preencha tudo!");

            if (modo === "novo") {
                fetch("editar_servico.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: `nome=${encodeURIComponent(n)}&preco=${encodeURIComponent(p)}`
                })
                    .then(r => r.text())
                    .then(msg => { alert(msg); carregarServicos(); });
            } else if (modo === "bloqueado") {
                nomeInput.readOnly = false;
                precoInput.readOnly = false;
                editarBtn.innerText = "Salvar";
                modo = "editando";
            } else {
                fetch("editar_servico.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: `id=${id}&nome=${encodeURIComponent(n)}&preco=${encodeURIComponent(p)}`
                })
                    .then(r => r.text())
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
            if (!id) return box.remove();
            if (!confirm("Excluir serviço?")) return;
            fetch("excluir_servico.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `id=${id}`
            })
                .then(r => r.text())
                .then(msg => { alert(msg); carregarServicos(); });
        });

        return box;
    }

    function carregarServicos() {
        fetch("salvar_servico.php")
            .then(r => r.json())
            .then(lista => {
                if (!areaServicos) return;
                areaServicos.innerHTML = "";
                lista.forEach(s => areaServicos.appendChild(criarServico(s.id, s.nome, s.preco)));
            });
    }

    if (btnNovoServico) {
        btnNovoServico.onclick = () => areaServicos.appendChild(criarServico());
    }

    
    window.addEventListener("load", function () {

        const botaoHamburguer = document.getElementById("btnMenu");
        const caixaMenu = document.querySelector(".menu");

        if (botaoHamburguer && caixaMenu) {

            // 1. Abre e fecha o menu ao clicar no ☰
            botaoHamburguer.onclick = function (event) {
                caixaMenu.classList.toggle("ativo");
                event.stopPropagation();
            };

            // 2. CORRIGIDO: Pega todos os botões dentro do menu e fecha ao clicar
            const botoesDoMenu = caixaMenu.querySelectorAll("button");
            botoesDoMenu.forEach(function (botao) {
                botao.addEventListener("click", function () {
                    caixaMenu.classList.remove("ativo");
                });
            });

            // 3. Se clicar no resto da página, fecha o menu também
            document.onclick = function (event) {
                if (!caixaMenu.contains(event.target) && event.target !== botaoHamburguer) {
                    caixaMenu.classList.remove("ativo");
                }
            };
        }
    });

    // ===================== INICIAR SISTEMA =====================
    carregarServicos();
    atualizarTabelaClientes();
    atualizarTabelaHistorico();

    // Atualização automática a cada 5 segundos
    setInterval(atualizarTabelaClientes, 5000);
    setInterval(atualizarTabelaHistorico, 5000);
});