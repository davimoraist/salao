const form = document.getElementById('agendamentoForm');
const mensagem = document.getElementById('mensagem');
const inputData = document.getElementById('data');
const telefoneInput = document.getElementById('telefone');

// ─────────────── 1) DATA MÍNIMA ───────────────
function setDataMinima() {
  const hoje = new Date();
  if (hoje.getHours() < 7) hoje.setHours(7, 0);
  else if (hoje.getHours() >= 18) {
    hoje.setDate(hoje.getDate() + 1);
    hoje.setHours(7, 0);
  }

  const ano = hoje.getFullYear();
  const mes = String(hoje.getMonth() + 1).padStart(2, '0');
  const dia = String(hoje.getDate()).padStart(2, '0');
  const hora = String(hoje.getHours()).padStart(2, '0');
  const min = String(hoje.getMinutes()).padStart(2, '0');

  inputData.min = `${ano}-${mes}-${dia}T${hora}:${min}`;
}
setDataMinima();

// ─────────────── 2) BANCO (LOCALSTORAGE) ───────────────
function getAgendamentos() {
  return JSON.parse(localStorage.getItem('agendamentos') || '[]');
}

function saveAgendamento(agendamento) {
  const agendamentos = getAgendamentos();
  agendamentos.push(agendamento);
  localStorage.setItem('agendamentos', JSON.stringify(agendamentos));
}

// ─────────────── 3) LIMPAR AGENDAMENTOS ANTIGOS ───────────────
function limparAgendamentosAntigos() {
  const agora = new Date();
  let agendamentos = getAgendamentos();

  agendamentos = agendamentos.filter(a => new Date(a.data) > agora);

  localStorage.setItem('agendamentos', JSON.stringify(agendamentos));
}
limparAgendamentosAntigos();

// ─────────────── 4) RESTRIÇÕES DE DATA/HORA ───────────────
inputData.addEventListener('change', function () {
  mensagem.textContent = "";
  const dataSelecionada = new Date(inputData.value);
  const diaSemana = dataSelecionada.getDay();
  const hora = dataSelecionada.getHours();

  if (diaSemana === 0) {
    mensagem.textContent = "Domingo não permitido.";
    inputData.value = "";
    return;
  }

  if (hora < 7 || hora >= 18 || hora === 12) {
    mensagem.textContent = "Horário inválido (7h-12h e 13h-18h).";
    inputData.value = "";
    return;
  }
});

// ─────────────── 5) GERAR HORÁRIOS DISPONÍVEIS ───────────────
function gerarHorarios() {
  const horarios = [];

  for (let h = 7; h < 12; h++) horarios.push(`${String(h).padStart(2, '0')}:00`);
  for (let h = 13; h < 18; h++) horarios.push(`${String(h).padStart(2, '0')}:00`);

  return horarios;
}

// MOSTRAR HORÁRIOS COM CORES
function atualizarHorarios() {
  const container = document.getElementById('listaHorarios');
  if (!container) return;

  container.innerHTML = "";

  const agendamentos = getAgendamentos();
  const horarios = gerarHorarios();

  horarios.forEach(horario => {
    const hoje = new Date();
    const ano = hoje.getFullYear();
    const mes = String(hoje.getMonth() + 1).padStart(2, '0');
    const dia = String(hoje.getDate()).padStart(2, '0');

    const dataCompleta = `${ano}-${mes}-${dia}T${horario}`;

    const ocupado = agendamentos.some(a => a.data === dataCompleta);

    const div = document.createElement('div');
    div.textContent = horario;

    if (ocupado) {
      div.style.background = "red";
      div.style.color = "white";
    } else {
      div.style.background = "green";
      div.style.color = "white";
    }

    div.style.padding = "6px";
    div.style.margin = "4px 0";
    div.style.borderRadius = "6px";
    div.style.fontWeight = "bold";

    container.appendChild(div);
  });
}

atualizarHorarios();

// ─────────────── 6) ENVIO DO FORMULÁRIO ───────────────
form.addEventListener('submit', function (e) {
  e.preventDefault();
  mensagem.textContent = "";

  const nome = document.getElementById('nome').value.trim();
  const telefone = telefoneInput.value.trim();
  const alergia = document.getElementById('alergia').value.trim();
  const data = inputData.value;

  const servicosSelecionados = Array.from(
    document.querySelectorAll('input[name="servico"]:checked')
  )
    .map(el => el.value)
    .join(', ');

  if (!servicosSelecionados) {
    mensagem.textContent = "Escolha pelo menos um serviço.";
    return;
  }

  const telefoneRegex = /^\(\d{2}\)\s?\d{4,5}-\d{4}$/;
  if (!telefoneRegex.test(telefone)) {
    mensagem.textContent = "Telefone inválido. Use: (61) 9575-6256";
    return;
  }

  const agendamentos = getAgendamentos();
  const existe = agendamentos.some(a => a.data === data);
  if (existe) {
    mensagem.textContent = "❌ Horário já agendado!";
    return;
  }

  saveAgendamento({ nome, telefone, data, servicos: servicosSelecionados, alergia });

  const numeroWhats = "556195756256";
  const texto = encodeURIComponent(
    `Novo agendamento:\nNome: ${nome}\nTelefone: ${telefone}\nServiço(s): ${servicosSelecionados}\nData e hora: ${data}` +
    (alergia ? `\nAlergia: ${alergia}` : '')
  );

  window.open(`https://wa.me/${numeroWhats}?text=${texto}`, '_blank');

  mensagem.style.color = "green";
  mensagem.textContent = `✅ Agendamento de ${nome} realizado!`;

  form.reset();
  setDataMinima();
  atualizarHorarios();
});
