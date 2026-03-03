  <?php
require_once("login.php");

header('Content-Type: application/json');

$sql = $pdo->query("SELECT * FROM servicos ORDER BY id DESC");
$dados = $sql->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($dados);
exit;
?>

