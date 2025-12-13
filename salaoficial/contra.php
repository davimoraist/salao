 <?php

if (
    isset($_POST['email']) && !empty($_POST['email']) &&
    isset($_POST['senha']) && !empty($_POST['senha'])
) {

    $email = addslashes($_POST['email']);
    $senha = addslashes($_POST['senha']);

    echo "Email: " . $email . "<br>";
    echo "Senha: " . $senha;

} else {
    header("Location: index.php");
    exit;
}

?>
