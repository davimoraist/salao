 <?php
$host = "localhost";
$db   = "salao"; // Nome da base de dados
$user = "root";
$pass = "";
$charset = "utf8mb4";

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Lança exceções em caso de erro SQL
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Retorna arrays associativos por defeito
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Usa prepared statements reais do MySQL
];

try {
    // Criação da conexão PDO segura
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Regista o erro internamente e exibe uma mensagem genérica e segura ao utilizador
    error_log("Erro de ligação à base de dados: " . $e->getMessage());
    die("Ocorreu um erro ao ligar à base de dados. Por favor, tente novamente mais tarde.");
}
?><?php
$host = "localhost";
$db   = "salao"; // Nome da base de dados
$user = "root";
$pass = "";
$charset = "utf8mb4";

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Lança exceções em caso de erro SQL
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Retorna arrays associativos por defeito
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Usa prepared statements reais do MySQL
];

try {
    // Criação da conexão PDO segura
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Regista o erro internamente e exibe uma mensagem genérica e segura ao utilizador
    error_log("Erro de ligação à base de dados: " . $e->getMessage());
    die("Ocorreu um erro ao ligar à base de dados. Por favor, tente novamente mais tarde.");
}
?>