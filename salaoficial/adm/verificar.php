 <?php
require "login.php";

if (isset($_SESSION['idusuario']) && !empty($_SESSION['idusuario'])) {
    require_once 'usuario.php';
    $u = new Usuario();

     $listLgged = $u->loggod($_SESSION['idusuario']);
     $pessoa = $listLgged['nome'];


    
} else {
    header("Location: index.php");
    exit;
}
?>
