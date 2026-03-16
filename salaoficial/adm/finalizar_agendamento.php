<?php
// finalizar_agendamento.php
$host = "localhost";
$db = "salao";
$user = "root";
$pass = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}

// Verifica se o ID foi enviado pela URL
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Prepara o comando para mudar o status
    // Isso faz o agendamento sumir da agenda (que só mostra 'pendente')
    $sql = $pdo->prepare("UPDATE agendamentos SET status = 'concluido' WHERE id = ?");
    
    if ($sql->execute([$id])) {
        // Se deu certo, volta para a tela administrativa
        header("Location: adm.php?sucesso=1");
    } else {
        echo "Erro ao finalizar agendamento.";
    }
} else {
    echo "ID não fornecido.";
}


?>