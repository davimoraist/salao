<?php 
require_once "conecte.php";
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $id_cliente = $_SESSION['id'];

    $servicos = $_POST['servocosalao'] ?? [];
    $servicosTexto = implode(", ", $servicos);
    $preco_servico = $_POST['preco_servico'] ?? 0;

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
    $stmt->bind_param("isss", $id_cliente, $servicosTexto, $preco_servico, $data, $hora);

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