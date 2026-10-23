 <?php
// Devolve (JSON) os horários JÁ OCUPADOS de um dia: buscar_horarios.php?data=AAAA-MM-DD
// Agendamentos cancelados NÃO ocupam o horário.
require_once "conecte.php";

header('Content-Type: application/json; charset=utf-8');

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
$stmt = $conn->prepare($sql);

if (!$stmt) {
    error_log("buscar_horarios.php: " . $conn->error);
    http_response_code(500);
    echo json_encode([]);
    exit;
}

$stmt->bind_param("s", $data);
$stmt->execute();
$result = $stmt->get_result();

$horarios = [];
while ($row = $result->fetch_assoc()) {
    $horarios[] = $row['hora_agendamento'];
}

echo json_encode($horarios);

$stmt->close();
$conn->close();
?>