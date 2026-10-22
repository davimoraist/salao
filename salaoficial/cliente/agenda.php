 <?php
// Inicia a sessão de forma segura se ainda não tiver sido iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/conecte.php"; // sua conexão mysqli
require_once __DIR__ . "/bloqueios.php";

// Verifica login
if (!isset($_SESSION['id'])) {
    header("Location: cliente.php");
    exit;
}

// Garante a existência e a persistência do Token CSRF para validar o envio do formulário
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Pega os serviços do banco INCLUINDO o ID
$sql = "SELECT id, nome, preco FROM servico ORDER BY id DESC";
$result = $conn->query($sql);

// Períodos em que o salão está fechado (só as datas, nunca o motivo)
$bloqueios = listarBloqueiosFuturos($conn);

$dados = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $dados[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>mtnaildesigner.com</title>
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="css/agenda.css">
</head>
<body>
    <form action="angedadado.php" method="post" class="form-box" id="form-agendamento">

        <h1>Agendamento</h1>
        <div class="linha"></div>

        <?php if (!empty($bloqueios)): ?>
            <div class="aviso-bloqueio" style="background:#fff5f5;border:1px solid #feb2b2;color:#742a2a;padding:12px 14px;border-radius:8px;margin:12px 0;font-size:14px;">
                O salão não estará atendendo em:
                <?php foreach ($bloqueios as $b): ?>
                    <br><strong><?= date('d/m/Y', strtotime($b['inicio'])) ?><?= $b['inicio'] !== $b['fim'] ? ' a ' . date('d/m/Y', strtotime($b['fim'])) : '' ?></strong>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="none">
            <h2>Nome: <?php echo htmlspecialchars($_SESSION['nome'] ?? '', ENT_QUOTES, 'UTF-8'); ?></h2>
        </div>

        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

        <input type="hidden" name="data" id="dataSelecionada">
        <input type="hidden" name="hora" id="horaSelecionada">

        <h2 class="titulo">Escolha a data</h2>
        <div class="carrossel">
            <div class="seta" onclick="mover(-1)">◀</div>
            <div class="datas" id="datas"></div>
            <div class="seta" onclick="mover(1)">▶</div>
        </div>

        <h2 class="titulo">Horários disponíveis</h2>
        <div class="horarios" id="horarios"></div>
    
        <div class="servico">
            <h2 class="titulo">Serviço</h2>
        </div>

        <?php if (empty($dados)): ?>
            <p>Nenhum serviço cadastrado.</p>
        <?php else: ?>
            <?php foreach ($dados as $servico): ?>
                <div class="servico-box">
                    <label>
                        <!-- Agora passa o ID no value em vez do nome -->
                        <input type="checkbox" name="servocosalao[]" value="<?= (int)$servico['id']; ?>"
                               data-nome="<?= htmlspecialchars($servico['nome'], ENT_QUOTES, 'UTF-8'); ?>"
                               data-preco="<?= number_format((float)$servico['preco'], 2, '.', ''); ?>">
                        <?= htmlspecialchars($servico['nome'], ENT_QUOTES, 'UTF-8'); ?>
                    </label>
                    <p>R$ <?= number_format($servico['preco'], 2, ',', '.'); ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="pagamento">
            <button type="submit" name="pagamento">Pagamento</button>
        </div>

    </form>
    <script>
        // Períodos em que o salão está fechado (formato AAAA-MM-DD)
        window.DIAS_BLOQUEADOS = <?= json_encode($bloqueios) ?>;
        window.diaBloqueado = function (dataISO) {
            return window.DIAS_BLOQUEADOS.some(function (b) {
                return dataISO >= b.inicio && dataISO <= b.fim;
            });
        };
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Alerta antes de ir para a página de pagamento
        document.getElementById('form-agendamento').addEventListener('submit', function (e) {
            e.preventDefault();
            const form = this;

            const aviso = texto => Swal.fire({ icon: 'warning', text: texto, confirmButtonColor: '#734440' });
            const esc = s => String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
            const real = v => 'R$ ' + v.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');

            const data = document.getElementById('dataSelecionada').value;
            const hora = document.getElementById('horaSelecionada').value;
            const marcados = Array.from(form.querySelectorAll('input[name="servocosalao[]"]:checked'));

            if (!data) return aviso('Escolha uma data.');
            if (!hora) return aviso('Escolha um horário.');
            if (marcados.length === 0) return aviso('Escolha pelo menos um serviço.');

            const nomes = marcados.map(m => esc(m.dataset.nome)).join(', ');
            const total = marcados.reduce((soma, m) => soma + parseFloat(m.dataset.preco), 0);
            const sinal = Math.round(total * 30) / 100; // 30%, igual ao cálculo do servidor
            const dataBR = data.split('-').reverse().join('/');

            Swal.fire({
                icon: 'info',
                title: 'Ir para o pagamento?',
                html: 'Você será levada para a <strong>página de pagamento</strong>.<br><br>' +
                      '<strong>' + nomes + '</strong><br>' +
                      dataBR + ' às ' + esc(hora) + '<br>' +
                      'Total: ' + real(total) + ' &middot; Sinal (30%): ' + real(sinal) + '<br><br>' +
                      '<small>O horário só fica garantido depois que o pagamento for confirmado. Não feche a página até terminar.</small>',
                showCancelButton: true,
                confirmButtonText: 'Continuar para o pagamento',
                cancelButtonText: 'Voltar',
                confirmButtonColor: '#734440'
            }).then(res => {
                if (res.isConfirmed) {
                    const botao = form.querySelector('button[type="submit"]');
                    if (botao) botao.disabled = true; // evita clique duplo
                    form.submit();
                }
            });
        });
    </script>
    <script src="agenda.js"></script>
</body>
</html>