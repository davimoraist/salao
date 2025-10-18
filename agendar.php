 <?php
$localhost = "localhost";
$usuario = "root"; // corrigido de $usar
$senha = "";       // corrigido de $passw
$banco = "salao";

try {
    // Conexão com o banco de dados usando PDO
    $pdo = new PDO("mysql:host=$localhost;dbname=$banco", $usuario, $senha);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Consulta à tabela (verifique o nome correto da sua tabela)
    $sql = $pdo->query("SELECT * FROM usuarios"); // substituir "usuarios" pelo nome real

    echo "Número de registros: " . $sql->rowCount();

} catch (PDOException $e) {
    echo "Erro ao conectar ou consultar: " . $e->getMessage();
}
?>
