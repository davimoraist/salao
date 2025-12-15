 <!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <title>Login</title>
</head>

<body>
    <div class="login">
        <form action="contra.php" method="POST" autocomplete="off">

            <h1>Login ADM</h1>

            <label for="email">E-mail</label>
            <input 
                type="email" 
                name="email" 
                id="email" 
                autocomplete="off"
                required
            >

            <label for="senha">Senha</label>
            <input 
                type="password" 
                name="senha" 
                id="senha" 
                autocomplete="new-password"
                required
            >

            <button type="submit">Entrar</button>

        </form>
    </div>
</body>

</html>
