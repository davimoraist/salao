<?php
// Configurações do banco
$localhost = "localhost";
$usuario = "root";
$senha = "";
$banco = "salao";

try {
    // Conexão com o banco de dados
    $pdo = new PDO("mysql:host=$localhost;dbname=$banco;charset=utf8", $usuario, $senha);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Verifica se o formulário foi enviado
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Recebe os dados do formulário
        $nome = $_POST["nome"] ?? '';
        $telefone = $_POST["telefone"] ?? '';
        $data = $_POST["data"] ?? '';
        $alergia = $_POST["alergia"] ?? '';
        $servico = $_POST["servico"] ?? '';

        // Prepara e executa o comando SQL
        $sql = $pdo->prepare("INSERT INTO salaomt (nome, telefone, data_hora, alergia, servico)
                              VALUES (:nome, :telefone, :data_hora, :alergia, :servico)");

        $sql->bindParam(':nome', $nome);
        $sql->bindParam(':telefone', $telefone);
        $sql->bindParam(':data_hora', $data);
        $sql->bindParam(':alergia', $alergia);
        $sql->bindParam(':servico', $servico);

        $sql->execute();

        echo "<h2>✅ Agendamento realizado com sucesso!</h2>";
        echo "<a href='index.html'>Voltar</a>";
    }

} catch (PDOException $e) {
    echo "❌ Erro ao conectar ou salvar: " . $e->getMessage();
}
?>
