 <?php
session_start();
require_once __DIR__ . "/conecte.php";

header('Content-Type: application/json; charset=utf-8');

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'erro' => 'Erro de conexão com o banco.']);
    exit;
}

function respostaJson(array $dados, int $statusHttp = 200): void {
    http_response_code($statusHttp);
    echo json_encode($dados);
    exit;
}

if (!isset($_SESSION['id'])) {
    respostaJson(['sucesso' => false, 'erro' => 'sessao_expirada'], 401);
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    respostaJson(['sucesso' => false, 'erro' => 'metodo_invalido'], 405);
}

$id_cliente = (int)$_SESSION['id'];
$id_agendamento = (int)($_POST['id_agendamento'] ?? 0);

if ($id_agendamento <= 0) {
    respostaJson(['sucesso' => false, 'erro' => 'ID de agendamento inválido.'], 400);
}

try {
    // Confere se o agendamento existe, pertence a este cliente e ainda pode ser cancelado (PDO)
    $stmt = $pdo->prepare("
        SELECT id, status
        FROM agendamentos
        WHERE id = ? AND id_cliente = ?
        LIMIT 1
    ");
    $stmt->execute([$id_agendamento, $id_cliente]);
    $agendamento = $stmt->fetch();

    if (!$agendamento) {
        respostaJson(['sucesso' => false, 'erro' => 'Agendamento não encontrado.'], 404);
    }

    if (strtolower($agendamento['status']) === 'cancelado') {
        respostaJson(['sucesso' => false, 'erro' => 'Este agendamento já está cancelado.'], 400);
    }

    // Atualiza o status em vez de apagar a linha, para manter o histórico e estatísticas intactas
    $stmtUpdate = $pdo->prepare("
        UPDATE agendamentos
        SET status = 'Cancelado'
        WHERE id = ? AND id_cliente = ?
    ");
    $stmtUpdate->execute([$id_agendamento, $id_cliente]);

    respostaJson(['sucesso' => true]);

} catch (\PDOException $e) {
    error_log("Erro ao cancelar agendamento em cancelar_agendamento.php: " . $e->getMessage());
    respostaJson(['sucesso' => false, 'erro' => 'Erro ao gravar o cancelamento no banco.'], 500);
}
?>