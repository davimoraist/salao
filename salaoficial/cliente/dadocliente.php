 <?php 
session_start();
require_once __DIR__ . "/conecte.php";

// Receber dados
$endereco = trim($_POST['endereco'] ?? '');
$tel = trim($_POST['tel'] ?? '');
$cpf = trim($_POST['cpf'] ?? '');
$idade = trim($_POST['idade'] ?? '');
$como_conheceu = trim($_POST['como_conheceu'] ?? '');

// Validação
if ($endereco === '' || $tel === '' || $cpf === '' || $idade === '' || $como_conheceu === '') {
    $_SESSION['criar_error'] = '❌ Preencha todos os campos.';
    header("Location: agenda.php");
    exit;
}

// Verificar CPF duplicado
$stmt = $conn->prepare("SELECT id FROM pessoais WHERE cpf = ?");
$stmt->bind_param("s", $cpf);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $_SESSION['criar_error'] = '❌ CPF já cadastrado.';
    header("Location: agenda.php");
    exit;
}

$stmt->close();

// Inserir
$stmt = $conn->prepare("INSERT INTO pessoais 
    (endereco, 	telefone, cpf, data_nascimento, como_conheceu) 
    VALUES (?, ?, ?, ?, ?)");

$stmt->bind_param("sssss", $endereco, $tel, $cpf, $idade, $como_conheceu);

if ($stmt->execute()) {
    $_SESSION['criar_sucesso'] = '✅ Cadastro realizado com sucesso!';
} else {
    $_SESSION['criar_error'] = '❌ Erro ao cadastrar: ' . $stmt->error;
}

$stmt->close();
$conn->close();

header("Location: agenda.php");
exit;
?>
