 <?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "salao"; // CONFIRA O NOME DO BANCO

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}
