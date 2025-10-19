 <?php
$localhost = "localhost";
$usuario = "root";
$senha = "";
$banco = "salao";


$conexao = new mysqli($localhost, $usuario, $senha, $banco);

if($conexao->connect_errno){
     echo "erro";
}
else{
    echo "centro";
}
?>
