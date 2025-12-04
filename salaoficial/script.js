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

  const area = document.getElementById("servicos-area");
    const mais = document.getElementById("fab");

    mais.addEventListener("click", () => {

        // cria box do serviço
        const box = document.createElement("div");
        box.classList.add("servico-box");

        box.innerHTML = `
            <label>Nome do Serviço:</label>
            <input type="text" placeholder="Ex: Corte Feminino" />

            <label>Preço:</label>
            <input type="number" placeholder="Ex: R$ 45,00" />

            <button class="delete-btn">Excluir</button>
            <button class="enviar-btn">Enviar</button>
        `;

        // função excluir
        box.querySelector(".delete-btn").addEventListener("click", () => {
            box.remove();
        });

        // adiciona o card na área
        area.appendChild(box);
        
    });
