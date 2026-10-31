 <?php
// Devolve (JSON) os horários JÁ OCUPADOS de um dia: buscar_horarios.php?data=AAAA-MM-DD
// Agendamentos cancelados NÃO ocupam o horário.
require_once "conecte.php";

header('Content-Type: application/json; charset=utf-8');

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo json_encode([]);
    exit;
}

$data = $_GET['data'] ?? '';
$dataObj = DateTime::createFromFormat('Y-m-d', $data);

if (!$dataObj || $dataObj->format('Y-m-d') !== $data) {
    http_response_code(400);
    echo json_encode([]);
    exit;
}

$sql = "SELECT hora_agendamento
          FROM agendamentos
         WHERE data_agendamento = ?
           AND (status IS NULL OR status <> 'cancelado')";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$data]);
    $horarios = $stmt->fetchAll(PDO::FETCH_COLUMN); // Retorna diretamente um array com os valores da coluna 'hora_agendamento'

    echo json_encode($horarios);
} catch (\PDOException $e) {
    error_log("buscar_horarios.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([]);
}
?>