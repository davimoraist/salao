 <?php
require_once "confima.php";

$sql = "SELECT nome, email FROM testecliente ORDER BY idusor DESC";
$result = $conn->query($sql);

echo "<table cellpadding='10'>";
echo "<tr><th>Nome</th><th>Email</th></tr>";

if($result->num_rows > 0){
    while($row = $result->fetch_assoc()){
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['nome']) . "</td>";
        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='2'>Nenhum dado encontrado</td></tr>";
}
echo "</table>";
?>