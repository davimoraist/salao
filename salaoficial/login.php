 <?php
session_start();

$localhost = "localhost";
$user = "root";
$passw = "";
$banco = "salao";

try {
    $pdo = new PDO(
        "mysql:host=$localhost;dbname=$banco;charset=utf8",
        $user,
        $passw
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}
?>