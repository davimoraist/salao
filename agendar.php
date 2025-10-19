 <?php
$localhost = "localhost";
$usuario = "root";
$senha = "";
$banco = "salao";

try {
    // Conexão com o banco de dados usando PDO
    $pdo = new PDO("mysql:host=$localhost;dbname=$banco", $usuario, $senha);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Consulta à tabela correta
    $sql = $pdo->query("SELECT * FROM salaomt");

    echo "Número de registros: " . $sql->rowCount();

} catch (PDOException $e) {
    echo "Erro ao conectar ou consultar: " . $e->getMessage();
}
?>
