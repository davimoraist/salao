 <?php

$localhost = "localhost";
$user = "root";
$passw = "";
$banco = "salao";

try {
    $pdo = new PDO(
        "mysql:dbname=".$banco.";host=".$localhost,
        $user,
        $passw
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Conectado com sucesso!";

} catch (PDOException $e) {
    echo "ERRO: " . $e->getMessage();
    exit;
}

?>
