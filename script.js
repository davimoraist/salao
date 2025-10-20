const form = document.getElementById('agendamentoForm');
const mensagem = document.getElementById('mensagem');
const inputData = document.getElementById('data');
const telefoneInput = document.getElementById('telefone');

// Define data mínima como hoje às 7:00
function setDataMinima() {
  const hoje = new Date();
  if (hoje.getHours() < 7) hoje.setHours(7, 0, 0, 0);
  else if (hoje.getHours() >= 18) {
    hoje.setDate(hoje.getDate() + 1);
    hoje.setHours(7, 0, 0, 0);
  }
  const ano = hoje.getFullYear();
  const mes = String(hoje.getMonth() + 1).padStart(2, '0');
  const dia = String(hoje.getDate()).padStart(2, '0');
  const hora = String(hoje.getHours()).padStart(2, '0');
  const min = String(hoje.getMinutes()).padStart(2, '0');
  inputData.min = `${ano}-${mes}-${dia}T${hora}:${min}`;
}
setDataMinima();

// LocalStorage para armazenar agendamentos
function getAgendamentos() {
  return JSON.parse(localStorage.getItem('agendamentos') || '[]');
}

function saveAgendamento(agendamento) {
  const agendamentos = getAgendamentos();
  agendamentos.push(agendamento);
  localStorage.setItem('agendamentos', JSON.stringify(agendamentos));
}

// Validação de data/hora
inputData.addEventListener('change', function () {
  mensagem.textContent = "";
  const dataSelecionada = new Date(inputData.value);
  const diaSemana = dataSelecionada.getDay();
  const hora = dataSelecionada.getHours();

  if (diaSemana === 0) {
    mensagem.textContent = "Domingo não permitido. Escolha outro dia.";
    inputData.value = "";
    return;
  }
  if (hora < 7 || hora >= 18 || hora === 12) {
    mensagem.textContent = "Horário inválido (7h-12h ou 13h-18h).";
    inputData.value = "";
    return;
  }
});

// Envio do formulário
form.addEventListener('submit', function (e) {
  e.preventDefault();
  mensagem.textContent = "";

  const nome = document.getElementById('nome').value.trim();
  const telefone = telefoneInput.value.trim();
  const alergia = document.getElementById('alergia').value.trim();
  const data = document.getElementById('data').value;

  // Pega todos os serviços marcados
  const servicosSelecionados = Array.from(document.querySelectorAll('input[name="servico"]:checked'))
    .map(el => el.value)
    .join(', ');

  if (!servicosSelecionados) {
    mensagem.textContent = "Escolha pelo menos um serviço.";
    return;
  }

  // Validação do telefone
  const telefoneRegex = /^\(\d{2}\)\s?\d{4,5}-\d{4}$/;
  if (!telefoneRegex.test(telefone)) {
    mensagem.textContent = "Telefone inválido. Use o formato (61) 9575-6256.";
    return;
  }

  // Verifica se o horário já foi agendado
  const agendamentos = getAgendamentos();
  const existe = agendamentos.some(a => a.data === data);
  if (existe) {
    mensagem.textContent = "❌ Horário indisponível! Escolha outro horário.";
    return;
  }

  // Salva o agendamento no LocalStorage
  saveAgendamento({ nome, telefone, data, servicos: servicosSelecionados, alergia });

  // WhatsApp
  const numeroWhats = "556195756256";
  const texto = encodeURIComponent(
    `Novo agendamento:\nNome: ${nome}\nTelefone: ${telefone}\nServiço(s): ${servicosSelecionados}\nData e hora: ${data}` +
    (alergia ? `\nAlergia: ${alergia}` : '')
  );
  const urlWhats = `https://wa.me/${numeroWhats}?text=${texto}`;
  window.open(urlWhats, '_blank');

  mensagem.style.color = "green";
  mensagem.textContent = `✅ Agendamento de ${nome} realizado! Clique no WhatsApp para enviar.`;
  form.reset();
  setDataMinima();
});
