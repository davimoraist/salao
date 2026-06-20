 <?php
session_start();

$erros = [
    'login' => $_SESSION['login_error'] ?? '',
    'criar' => $_SESSION['criar_error'] ?? ''
];

$activeForm = $_SESSION['active_form'] ?? 'login';

unset($_SESSION['login_error']);
unset($_SESSION['criar_error']);
unset($_SESSION['active_form']);

function showError($error){
    return !empty($error)
        ? "<p class='error'>$error</p>"
        : "";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>mtnaildesigner.com</title>
    <link rel="stylesheet" href="css/clientes.css">
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0">
</head>
<body>

<div class="auth-container">

    <div class="logo-area">
        <h1>MT Nail Designer</h1>
        <p>Beleza e elegância ao seu alcance</p>
    </div>

    <div class="card-auth">

        <div class="tabs">
            <button
                type="button"
                class="tab <?= $activeForm === 'login' ? 'active' : '' ?>"
                id="btnLogin">
                Entrar
            </button>

            <button
                type="button"
                class="tab <?= $activeForm === 'criar' ? 'active' : '' ?>"
                id="btnCadastro">
                Cadastrar
            </button>
        </div>

        <form
            action="criarconta.php"
            method="POST"
            id="loginForm"
            class="form-auth <?= $activeForm === 'criar' ? 'hidden' : '' ?>">

            <?= showError($erros['login']); ?>

            <div class="input-box">
                <input type="email" name="email" placeholder="E-mail" autocomplete="off" required> 
                <span class="material-symbols-outlined">mail</span>
            </div>

            <div class="input-box">
                <input
                    type="password"
                    name="senha"
                    placeholder="Senha"
                    autocomplete="new-password"
                    required>
                <span class="material-symbols-outlined">visibility</span>
            </div>

            <button
                type="submit"
                name="login"
                class="btn-principal">
                Entrar
            </button>

        </form>

        <form 
            action="criarconta.php"
            method="POST"
            id="cadastroForm"
            class="form-auth <?= $activeForm === 'login' ? 'hidden' : '' ?>">

            <?= showError($erros['criar']); ?>

            <div class="input-box">
                <input
                    type="text"
                    name="nome"
                    placeholder="Nome completo"
                    required>
                <span class="material-symbols-outlined">person</span>
            </div>

            <div class="input-box">
                <input type="email" name="email" placeholder="E-mail" autocomplete="off" required>
                <span class="material-symbols-outlined">mail</span>
            </div>

            <div class="input-box">
                <input
                    type="password"
                    name="senha"
                    placeholder="Senha"
                    autocomplete="new-password"
                    required>
                <span class="material-symbols-outlined">visibility</span>
            </div>

            <label class="termos">
                <input
                    type="checkbox"
                    name="termo"
                    required>
               <a href="termos.html">Aceito os Termos de Uso</a>  
            </label>

            <button
                type="submit"
                name="criar"
                class="btn-principal">
                Criar conta
            </button>

        </form>

    </div>

</div>

<script src="contra.js"></script>
</body>
</html>