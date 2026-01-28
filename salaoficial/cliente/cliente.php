  <!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>

    <link rel="stylesheet" href="css/clientes.css">
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <!-- Material Symbols -->/
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
</head>  
<body>

    <div class="cliente">
        <!-- LOGIN -->
        <div class="conta" id="login">
            <form action="conecte.php" method="POST" autocomplete="off">
                <h1>Conecte-se</h1>

                <div class="input-box">
                    <input type="email" placeholder="E-mail">
                    <span class="material-symbols-outlined">mail</span>
                </div>

                <div class="input-box">
                    <input type="password" placeholder="Senha">
                    <span class="material-symbols-outlined">lock</span>
                </div>

                
                <button type="button">Entrar</button>
            </form>
        </div>

        <!-- CRIAR CONTA -->
        <div class="conta" id="criar">
            <form action="criarconta.php" method="POST" autocomplete="off">
                <h1>Criar uma conta</h1>

                <div class="input-box">
                    <input type="text" placeholder="Nome completo">
                    <span class="material-symbols-outlined">person</span>
                </div>

                <div class="input-box">
                    <input type="email" placeholder="E-mail">
                    <span class="material-symbols-outlined">mail</span>
                </div>

                <div class="input-box">
                    <input type="password" placeholder="Senha">
                    <span class="material-symbols-outlined">lock</span>
                </div>

                <button type="button">Criar conta</button>
            </form>
        </div>

        <!-- ANIMAÇÃO -->
        <div class="animacao">
            <div class="panel panel-left">
                <h1>Seja bem-vindo!</h1>
                <p>Ainda não tem uma conta? Crie uma.</p>
                <button class="entrar" id="conecte">Cria um conta</button>
            </div>

            <div class="panel panel-right">
                <h1>Crie sua conta!</h1>
                 <p>Já tem conta? Acesse sua conta</p>
                <button class="entrar" id="cadastro">conecte-se</button>
                 <div class="termos">
                     <p><input type="checkbox" name="termo" id="termos"><a href="#">termos de Uso</a></p>
                     <a href="#">Política de Privacidade</a>
                 </div>
            </div>
        </div>
    </div>

    <script src="contra.js"></script>
</body>
</html>
