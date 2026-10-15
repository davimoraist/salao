 <?php
require_once "conecte.php";

$data = $_GET['data'];

$sql = "SELECT hora_agendamento FROM agendamentos WHERE data_agendamento = ?";
$stmt = $conn->prepare($sql);
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