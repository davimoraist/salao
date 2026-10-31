<?php
session_start();
require_once __DIR__ . "/conecte.php";

header('Content-Type: application/json; charset=utf-8');

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

// Confere se o agendamento existe, pertence a este cliente e ainda
// pode ser cancelado (evita cancelar algo que já foi cancelado ou
// que é de outro cliente).
$stmt = $conn->prepare("
    SELECT id, status
    FROM agendamentos
    WHERE id = ? AND id_cliente = ?
    LIMIT 1
");
$stmt->bind_param("ii", $id_agendamento, $id_cliente);
$stmt->execute();
$resultado = $stmt->get_result();
$agendamento = $resultado->fetch_assoc();
$stmt->close();

if (!$agendamento) {
    respostaJson(['sucesso' => false, 'erro' => 'Agendamento não encontrado.'], 404);
}

if (strtolower($agendamento['status']) === 'cancelado') {
    respostaJson(['sucesso' => false, 'erro' => 'Este agendamento já está cancelado.'], 400);
}

// Atualiza o status em vez de apagar a linha, pra manter o
// histórico e as estatísticas de pagamento intactas.
$stmt = $conn->prepare("
    UPDATE agendamentos
    SET status = 'Cancelado'
    WHERE id = ? AND id_cliente = ?
");
$stmt->bind_param("ii", $id_agendamento, $id_cliente);

if (!$stmt->execute()) {
    error_log("Erro ao cancelar agendamento em cancelar_agendamento.php: " . $stmt->error);
    $stmt->close();
    respostaJson(['sucesso' => false, 'erro' => 'Erro ao gravar o cancelamento no banco.'], 500);
}

$stmt->close();

respostaJson(['sucesso' => true]);
?>