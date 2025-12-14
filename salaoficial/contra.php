 <?php

require_once 'login.php';
require_once 'usuario.php';

if (
    isset($_POST['email']) && !empty($_POST['email']) &&
    isset($_POST['senha']) && !empty($_POST['senha'])
) {

    $usuario = new usuario();

    $email = $_POST['email'];
    $senha = $_POST['senha'];

    echo "Conectado com sucesso!<br>";

    // teste
    $usuario->login($email, $senha);

} else {
    header("Location: index.php");
    exit;
}
?>