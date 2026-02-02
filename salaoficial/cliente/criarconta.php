 <?php
session_start();

/* =========================
   CONEXÃO COM O BANCO
========================= */
require_once __DIR__ . "/conecte.php";

if (!isset($conn)) {
    die("Erro: conexão com o banco não encontrada.");
}

/* =========================
   CRIAR CONTA
========================= */
if (isset($_POST['criar'])) {

    $nome  = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if ($nome === '' || $email === '' || $senha === '') {
        $_SESSION['criar_error'] = 'Preencha todos os campos.';
        $_SESSION['active_form'] = 'criar';
        header("Location: cliente.php");
        exit();
    }

    // Criptografa senha
    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

    // Verifica se email já existe
    $checkEmail = $conn->query(
        "SELECT idcliente FROM cliente WHERE email = '$email'"
    );

    if ($checkEmail && $checkEmail->num_rows > 0) {
        $_SESSION['criar_error'] = 'Este e-mail já está cadastrado.';
        $_SESSION['active_form'] = 'criar';
        header("Location: cliente.php");
        exit();
    }

    // Insere cliente
    $insert = $conn->query(
        "INSERT INTO cliente (nome, email, password, sit)
         VALUES ('$nome', '$email', '$senhaHash', 1)"
    );

    if ($insert) {
        $_SESSION['active_form'] = 'login';
        header("Location: cliente.php");
        exit();
    } else {
        $_SESSION['criar_error'] = 'Erro ao criar conta.';
        $_SESSION['active_form'] = 'criar';
        header("Location: cliente.php");
        exit();
    }
}

/* =========================
   LOGIN
========================= */
if (isset($_POST['login'])) {

    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if ($email === '' || $senha === '') {
        $_SESSION['login_error'] = 'Preencha todos os campos.';
        $_SESSION['active_form'] = 'login';
        header("Location: cliente.php");
        exit();
    }

    $result = $conn->query(
        "SELECT * FROM cliente WHERE email = '$email' LIMIT 1"
    );

    if ($result && $result->num_rows === 1) {

        $cliente = $result->fetch_assoc();

        if (password_verify($senha, $cliente['password'])) {

            $_SESSION['idcliente'] = $cliente['idcliente'];
            $_SESSION['nome']      = $cliente['nome'];
            $_SESSION['email']     = $cliente['email'];

            // Redirecionamento
            header("Location: anamnese.php");
            exit();
        }
    }

    $_SESSION['login_error'] = 'E-mail ou senha incorretos.';
    $_SESSION['active_form'] = 'login';
    header("Location: cliente.php");
    exit();
}
