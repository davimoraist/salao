 <?php 
require_once "conecte.php";
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $id_cliente = $_SESSION['id'];

    // Pega os serviços como array
    $servicos = $_POST['servocosalao'] ?? [];

    // Transforma em texto
    $servicosTexto = implode(", ", $servicos);

    $data = $_POST['data'];
    $hora = $_POST['hora'];

    $sql = "INSERT INTO agendamentos 
    (id_cliente, servico, data_agendamento, hora_agendamento) 
    VALUES (?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isss", $id_cliente, $servicosTexto, $data, $hora);

    if ($stmt->execute()) {
        header("Location: pagamendo.php");
        exit;
    } else {
        echo "Erro: " . $conn->error;
    }

    $stmt->close();
    $conn->close();
}
?>