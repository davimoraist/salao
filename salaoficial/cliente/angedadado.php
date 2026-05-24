 <?php 
require_once "conecte.php";
session_start();
 
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $id_cliente = $_SESSION['id'];

    $servicos = $_POST['servocosalao'] ?? [];
    $servicosTexto = implode(", ", $servicos);

    $precoTotal = $conn->query("SELECT SUM(preco) as total FROM servicos WHERE nome IN ('" . implode("','", $servicos) . "')")->fetch_assoc()['total'];

    $data = $_POST['data'];
    $hora = $_POST['hora'];

    // VERIFICA SE HORÁRIO JÁ EXISTE
    $verifica = $conn->prepare("SELECT id FROM agendamentos WHERE data_agendamento = ? AND hora_agendamento = ?");
    $verifica->bind_param("ss", $data, $hora);
    $verifica->execute();
    $verifica->store_result();

    if($verifica->num_rows > 0){
       die("Horário já reservado!");
    }

    // SALVAR
    $sql = "INSERT INTO agendamentos 
    (id_cliente, servico, preco_servico, data_agendamento, hora_agendamento) 
    VALUES (?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isdss", $id_cliente,  $servicosTexto, $precoTotal, $data, $hora);

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