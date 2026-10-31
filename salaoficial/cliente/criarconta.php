 <?php
ob_start();
session_start();

/* =========================
   CONEXÃO COM O BANCO (PDO)
========================= */
require_once __DIR__ . "/conecte.php";

if (!isset($pdo)) {
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

    /* =========================
       VALIDAÇÃO DO RECAPTCHA
    ========================= */
    $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';
    $secretKey = '6Lc1AQouAAAAAP4_EJT1hB7Pp0kDnjE6IsoYsk94'; // Sua chave secreta

    if (empty($recaptchaResponse)) {
        $formOrigem = isset($_POST['criar']) ? 'criar' : 'login';
        $_SESSION[$formOrigem . '_error'] = '❌ Por favor, confirme que você não é um robô.';
        $_SESSION['active_form'] = $formOrigem;
        header("Location: cliente.php");
        exit;
    }

    // Validação usando cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://www.google.com/recaptcha/api/siteverify");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'secret' => $secretKey,
        'response' => $recaptchaResponse
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $respostaGoogle = curl_exec($ch);
    curl_close($ch);

    $resultado = json_decode($respostaGoogle);

    if (!$resultado || !$resultado->success) {
        $formOrigem = isset($_POST['criar']) ? 'criar' : 'login';
        $_SESSION[$formOrigem . '_error'] = '❌ Falha na verificação do reCAPTCHA. Tente novamente.';
        $_SESSION['active_form'] = $formOrigem;
        header("Location: cliente.php");
        exit;
    }
}

/* =========================
   CRIAR CONTA (PDO)
========================= */
if (isset($_POST['criar'])) {

    $nome  = trim($_POST['nome'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $senha = $_POST['senha'] ?? '';

    if ($nome === '' || !$email || $senha === '') {
        $_SESSION['criar_error'] = '❌ Preencha todos os campos com dados válidos.';
        $_SESSION['active_form'] = 'criar';
        header("Location: cliente.php");
        exit;
    }

    // Verifica se email já existe usando PDO
    $stmt = $pdo->prepare("SELECT id FROM cliente WHERE email = ?");
    $stmt->execute([$email]);
    $checkEmail = $stmt->fetch();

    if ($checkEmail) {
        $_SESSION['criar_error'] = '❌ Este e-mail já está cadastrado.';
        $_SESSION['active_form'] = 'criar';
        header("Location: cliente.php");
        exit;
    }

    // Cria hash da senha com Bcrypt/Argon2 nativo
    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

    // Insere cliente com PDO Prepared Statement
    $stmt = $pdo->prepare("INSERT INTO cliente (nome, email, password, sit) VALUES (?, ?, ?, 1)");
    
    if ($stmt->execute([$nome, $email, $senhaHash])) {
        session_regenerate_id(true);

        $_SESSION['id']    = $pdo->lastInsertId();
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
   LOGIN (PDO)
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

    $stmt = $pdo->prepare("SELECT * FROM cliente WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $cliente = $stmt->fetch();

    $loginGenericError = '❌ E-mail ou senha incorretos.';

    if ($cliente) {
        if (password_verify($senha, $cliente['password'])) {
            session_regenerate_id(true);

            $_SESSION['id'] = $cliente['id'];
            $_SESSION['nome'] = $cliente['nome'];
            $_SESSION['email'] = $cliente['email'];

            // VERIFICA SE OUTROS DADOS FORAM PREENCHIDOS (PDO)
            $stmt2 = $pdo->prepare("SELECT cpf FROM pessoais WHERE id_cliente = ? LIMIT 1");
            $stmt2->execute([$cliente['id']]);
            $dados = $stmt2->fetch();

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