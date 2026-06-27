 <?php 
// Inicia a sessão de forma segura se ainda não tiver sido iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/conecte.php";

if (!isset($conn)) {
    die("Erro: conexão com o banco não encontrada.");
}

/* =======================================================
   TRAVA DE SEGURANÇA 1: VALIDAÇÃO DE SESSÃO E DE TOKEN CSRF
======================================================= */
// Impede requisições de usuários não autenticados
if (empty($_SESSION['id'])) {
    $_SESSION['criar_error'] = '❌ Sessão expirada ou usuário não autenticado.';
    header("Location: cliente.php");
    exit;
}

// Bloqueia ataques CSRF (Falsificação de Requisição)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['criar_error'] = '❌ Requisição inválida ou expirada.';
        header("Location: anamnes.php");
        exit;
    }
}

/* =======================================================
   TRAVA DE SEGURANÇA 2: SANITIZAÇÃO DE DADOS (ANTI-XSS)
======================================================= */
// Receber dados e aplicar filtros estritos para neutralizar tags HTML/Scripts perigosas
$endereco      = htmlspecialchars(trim($_POST['endereco'] ?? ''), ENT_QUOTES, 'UTF-8');
$tel           = htmlspecialchars(trim($_POST['tel'] ?? ''), ENT_QUOTES, 'UTF-8');
$cpf           = htmlspecialchars(trim($_POST['cpf'] ?? ''), ENT_QUOTES, 'UTF-8');
$idade         = htmlspecialchars(trim($_POST['idade'] ?? ''), ENT_QUOTES, 'UTF-8');
$como_conheceu = htmlspecialchars(trim($_POST['como_conheceu'] ?? ''), ENT_QUOTES, 'UTF-8');

// Se a opção selecionada for "Outros", sanitiza também o campo de complemento
if ($como_conheceu === 'Outros' && !empty($_POST['complemento'])) {
    $como_conheceu = htmlspecialchars(trim($_POST['complemento']), ENT_QUOTES, 'UTF-8');
}

// Validação de campos vazios
if ($endereco === '' || $tel === '' || $cpf === '' || $idade === '' || $como_conheceu === '') {
    $_SESSION['criar_error'] = '❌ Preencha todos os campos.';
    header("Location: anamnes.php");
    exit;
}

// Verificar CPF duplicado usando Prepared Statements
$stmt = $conn->prepare("SELECT id_cliente FROM pessoais WHERE cpf = ?");
$stmt->bind_param("s", $cpf);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $_SESSION['criar_error'] = '❌ CPF já cadastrado.';
    $stmt->close();
    header("Location: anamnes.php");
    exit;
}
$stmt->close();

/* =======================================================
   INSERÇÃO SEGURA NO BANCO DE DADOS
======================================================= */
$id_cliente = $_SESSION['id'];

$stmt = $conn->prepare("INSERT INTO pessoais 
    (id_cliente, endereco, telefone, cpf, data_nascimento, como_conheceu) 
    VALUES (?, ?, ?, ?, ?, ?)");

$stmt->bind_param("isssss", $id_cliente, $endereco, $tel, $cpf, $idade, $como_conheceu);

if ($stmt->execute()) {
    $_SESSION['criar_sucesso'] = '✅ Cadastro realizado com sucesso!';
} else {
    // TRAVA DE SEGURANÇA 3: Erros reais do banco de dados salvos apenas em log interno, nunca na tela
    error_log("Erro no banco de dados: " . $stmt->error);
    $_SESSION['criar_error'] = '❌ Erro ao cadastrar os dados. Tente novamente mais tarde.';
}

$stmt->close();
$conn->close();

header("Location: anamnes.php");
exit;
?>