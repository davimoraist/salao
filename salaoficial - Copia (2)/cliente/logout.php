<?php
session_start();

// Destrói todas as variáveis de sessão
$_SESSION = array();

// Se for preciso, apaga também o cookie da sessão
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destrói a sessão por completo
session_destroy();

// Redireciona a cliente para a página de login/entrada
header("Location: cliente.php");
exit;
?>