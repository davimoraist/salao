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

    // Verifica campos vazios
    if ($nome === '' || $email === '' || $senha === '') {
        $_SESSION['criar_error'] = '❌ Preencha todos os campos.';
        $_SESSION['active_form'] = 'criar';
        header("Location: cliente.php");
        exit;
    }

    // Verifica se email já existe
    $stmt = $conn->prepare("SELECT idcliente FROM cliente WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $checkEmail = $stmt->get_result();

    if ($checkEmail && $checkEmail->num_rows > 0) {
        $_SESSION['criar_error'] = '❌ Este e-mail já está cadastrado.';
        $_SESSION['active_form'] = 'criar';
        header("Location: cliente.php");
        exit;
    }

    // Cria hash da senha
    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

    // Insere cliente
    $stmt = $conn->prepare("INSERT INTO cliente (nome, email, password, sit) VALUES (?, ?, ?, 1)");
    $stmt->bind_param("sss", $nome, $email, $senhaHash);

    if ($stmt->execute()) {
        // LOGIN AUTOMÁTICO APÓS CADASTRO
        session_regenerate_id(true);

        $_SESSION['idcliente'] = $conn->insert_id;
        $_SESSION['nome']      = $nome;
        $_SESSION['email']     = $email;

        header("Location: ./anamnes.php");
        exit;
    } else {
        $_SESSION['criar_error'] = '❌ Erro ao criar conta.';
        $_SESSION['active_form'] = 'criar';
        header("Location: cliente.php");
        exit;
    }
}

/* =========================
   LOGIN
========================= */
if (isset($_POST['login'])) {

    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    // Verifica campos vazios
    if ($email === '' || $senha === '') {
        $_SESSION['login_error'] = '❌ Preencha todos os campos.';
        $_SESSION['active_form'] = 'login';
        header("Location: cliente.php");
        exit;
    }

    // Verifica se email existe
    $stmt = $conn->prepare("SELECT * FROM cliente WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $cliente = $result->fetch_assoc();

        // Verifica senha
        if (password_verify($senha, $cliente['password'])) {
            session_regenerate_id(true);

            $_SESSION['idcliente'] = $cliente['idcliente'];
            $_SESSION['nome']      = $cliente['nome'];
            $_SESSION['email']     = $cliente['email'];

            header("Location: ./anamnes.php");
            exit;
        } else {
            // Senha incorreta
            $_SESSION['login_error'] = '❌ Senha incorreta.';
            $_SESSION['active_form'] = 'login';
            header("Location: cliente.php");
            exit;
        }
    } else {
        // Email não cadastrado
        $_SESSION['login_error'] = '❌ E-mail não cadastrado.';
        $_SESSION['active_form'] = 'login';
        header("Location: cliente.php");
        exit;
    }
}
?>
