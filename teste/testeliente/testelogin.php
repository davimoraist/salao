 <?php
session_start();
require_once "confima.php";

if(isset($_POST['Envia'])){

    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);

    if(empty($nome) || empty($email)){
        echo "Preencha todos os campos!";
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO testecliente (nome, email) VALUES (?, ?)");
    $stmt->bind_param("ss", $nome, $email);

    if($stmt->execute()){
        echo "Dados enviados com sucesso!";
    } else {
        echo "Erro ao enviar dados.";
    }

    $stmt->close();
    $conn->close();
}
?>