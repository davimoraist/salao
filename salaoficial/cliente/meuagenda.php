 <?php
session_start();
require_once __DIR__ . "/conecte.php";

if (!isset($pdo) || !($pdo instanceof PDO)) {
    die("Erro: conexão com o banco não encontrada.");
}

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit;
}

$id_cliente = $_SESSION['id'];
$sql = "SELECT a.id, a.servico, a.data_agendamento, a.hora_agendamento, a.status,
               a.finalizado_em, p.valor_pago, p.valor_restante
        FROM agendamentos a
        INNER JOIN pagamentos p ON a.id = p.id_agendamento
        WHERE a.id_cliente = ?
        ORDER BY a.data_agendamento DESC, a.hora_agendamento DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id_cliente]);
$agendamentos = $stmt->fetchAll();

$proximos = [];
$historico = [];

foreach ($agendamentos as $ag) {
    if (strtolower($ag['status']) === 'confirmado') {
        $proximos[] = $ag;
    } else {
        $historico[] = $ag;
    }
}

function formatarData($data) {
    return date('d/m/Y', strtotime($data));
}
function formatarValor($valor) {
    return 'R$ ' . number_format($valor, 2, ',', '.');
}
function formatarDataHora($dataHora) {
    return date('d/m/Y \à\s H:i', strtotime($dataHora));
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Agendamentos | MT Nail Designer</title>
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="css/meuagenda.css">
</head>

<body>

    <main class="container">
        <div class="meus-agendamentos">
            <h1>Meus Agendamentos</h1>

            <h2>Próximos</h2>
            <?php if (empty($proximos)): ?>
                <p>Você não tem agendamentos futuros.</p>
            <?php else: ?>
                <?php foreach ($proximos as $ag): ?>
                    <div class="card-agendamento" id="agendamento-<?= (int)$ag['id'] ?>">
                        <p><strong>Serviço:</strong> <?= htmlspecialchars($ag['servico'], ENT_QUOTES, 'UTF-8') ?></p>
                        <p><strong>Data:</strong> <?= formatarData($ag['data_agendamento']) ?></p>
                        <p><strong>Horário:</strong> <?= htmlspecialchars($ag['hora_agendamento'], ENT_QUOTES, 'UTF-8') ?></p>
                        <p><strong>Entrada paga:</strong>
                            <span class="valor-pago"><?= formatarValor($ag['valor_pago']) ?></span>
                        </p>
                        <p><strong>Falta pagar:</strong>
                            <span class="valor-falta"><?= formatarValor($ag['valor_restante']) ?></span>
                        </p>
                        <button type="button" class="Cancelar" onclick="cancelar(<?= (int)$ag['id'] ?>)">
                            Cancelar o Serviço
                        </button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <h2>Histórico</h2>
            <?php if (empty($historico)): ?>
                <p>Nenhum agendamento no histórico ainda.</p>
            <?php else: ?>
                <?php foreach ($historico as $ag): ?>
                    <div class="card-agendamento historico">
                        <p><strong>Serviço:</strong> <?= htmlspecialchars($ag['servico'], ENT_QUOTES, 'UTF-8') ?></p>
                        <p><strong>Data:</strong> <?= formatarData($ag['data_agendamento']) ?></p>
                        <p><strong>Status:</strong> <?= htmlspecialchars(ucfirst($ag['status']), ENT_QUOTES, 'UTF-8') ?></p>
                        <?php if (strtolower($ag['status']) === 'concluido' && !empty($ag['finalizado_em'])): ?>
                            <p><strong>Finalizado em:</strong> <?= formatarDataHora($ag['finalizado_em']) ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="meuagenda.js"></script>
</body>

</html>