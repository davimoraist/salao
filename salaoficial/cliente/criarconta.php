 <?php

ob_start();
session_start();

/* =========================
   CONEXÃO COM O BANCO
========================= */
require_once __DIR__ . "/conecte.php";

if (!isset($conn)) {
    die("Erro: conexão com o banco não encontrada.");
}

/* =========================
   TRAVA DE SEGURANÇA: CSRF
========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['login_error'] = '❌ Requisição inválida ou expirada.';
        header("Location: cliente.php");
        exit;
    }
}

/* =========================
   CRIAR CONTA
========================= */
if (isset($_POST['criar'])) {

    $nome  = trim($_POST['nome'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $senha = $_POST['senha'] ?? '';

    // Verifica campos vazios e validação do formato de e-mail no back-end
    if ($nome === '' || !$email || $senha === '') {
        $_SESSION['criar_error'] = '❌ Preencha todos os campos com dados válidos.';
        $_SESSION['active_form'] = 'criar';
        header("Location: cliente.php");
        exit;
    }

    // Verifica se email já existe
    $stmt = $conn->prepare("SELECT id FROM cliente WHERE email = ?");
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
        // LOGIN AUTOMÁTICO APÓS CADASTRO (Muda o ID da sessão por segurança)
        session_regenerate_id(true);

        $_SESSION['id']    = $conn->insert_id;
        $_SESSION['nome']  = $nome;
        $_SESSION['email'] = $email;

        header("Location: ./pessoal.php");
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
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['email']) && isset($_POST['senha']) && !isset($_POST['criar'])) {

    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $senha = $_POST['senha'] ?? '';

    if (!$email || $senha === '') {
        $_SESSION['login_error'] = '❌ E-mail ou senha incorretos.';
        $_SESSION['active_form'] = 'login';
        header("Location: cliente.php");
        exit;
    }

    $stmt = $conn->prepare("SELECT * FROM cliente WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    // Mensagem genérica unificada para mitigar ataques de enumeração de e-mails
    $loginGenericError = '❌ E-mail ou senha incorretos.';

    if ($result && $result->num_rows === 1) {
        $cliente = $result->fetch_assoc();

        if (password_verify($senha, $cliente['password'])) {
            session_regenerate_id(true);

            $_SESSION['id'] = $cliente['id'];
            $_SESSION['nome'] = $cliente['nome'];
            $_SESSION['email'] = $cliente['email'];

            // VERIFICA SE OUTROS DADOS FORAM PREENCHIDOS
            $stmt2 = $conn->prepare("SELECT cpf FROM pessoais WHERE id_cliente = ? LIMIT 1");
            $stmt2->bind_param("i", $cliente['id']);
            $stmt2->execute();
            $res2 = $stmt2->get_result();
            $dados = $res2->fetch_assoc();

            if (!empty($dados['cpf'])) {
                header("Location: panel.php");
                exit;
            } else {
                header("Location: pessoal.php");
                exit;
            }

        } else {
            $_SESSION['login_error'] = $loginGenericError;
            $_SESSION['active_form'] = 'login';
            header("Location: cliente.php");
            exit;
        }

    } else {
        $_SESSION['login_error'] = $loginGenericError;
        $_SESSION['active_form'] = 'login';
        header("Location: cliente.php");
        exit;
    }
}

?>