 <?php 
session_start();
require_once 'login.php';
require_once 'conta.clienta.php';

if (
    isset($_POST['nome'])  && !empty($_POST['nome']) &&
    isset($_POST['email']) && !empty($_POST['email']) &&
    isset($_POST['senha']) && !empty($_POST['senha'])
) {

    $usuario = new Usuario();

    $name     = $_POST['nome'];   // 👉 name
    $email    = $_POST['email'];
    $password = $_POST['senha'];  // depois protegemos

    if ($usuario->cadastro($name, $email, $password)) {
        echo "SALVO COM SUCESSO";
    } else {
        echo "NÃO SALVOU";
    }
    exit;

} else {
    echo "DADOS NÃO RECEBIDOS";
    exit;
}
?>
