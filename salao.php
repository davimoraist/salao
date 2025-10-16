 <?php
include('conexao.php');

$nome = $_POST['nome'];
$data = $_POST['data'];
$hora = $_POST['hora'];
$servico = $_POST['servico'];
$obs = $_POST['observacao'];

// Montar o comando SQL
$sql = "INSERT INTO agendamentos (nome_cliente, data, hora, servico, observacao)
        VALUES ('$nome', '$data', '$hora', '$servico', '$obs')";

// Executar e verificar
if ($conexao->query($sql) === TRUE) {
    echo "<script>alert('✅ Agendamento realizado com sucesso!');
          window.location.href='index.html';</script>";
} else {
    echo "Erro: " . $conexao->error;
}

$conexao->close();
?>
