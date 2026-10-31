 <?php
// Inicia a sessão de forma segura se ainda não tiver sido iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/conecte.php";

if (!isset($pdo) || !($pdo instanceof PDO)) {
    die("Erro: conexão com o banco não encontrada.");
}

/* =======================================================
   TRAVA DE SEGURANÇA 1: VALIDAÇÃO DE SESSÃO E DE TOKEN CSRF
======================================================= */
if (empty($_SESSION['id'])) {
    $_SESSION['criar_error'] = '❌ Sessão expirada ou usuário não autenticado.';
    header("Location: cliente.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['criar_error'] = '❌ Requisição inválida ou expirada.';
        header("Location: pessoal.php");
        exit;
    }
} else {
    header("Location: pessoal.php");
    exit;
}

/* =======================================================
   FUNÇÕES DE VALIDAÇÃO
======================================================= */
function apenasDigitos(string $valor): string
{
    return preg_replace('/\D+/', '', $valor) ?? '';
}

function temSequenciaRepetida(string $texto, int $minimo = 8): bool
{
    return (bool) preg_match('/(\d)\1{' . ($minimo - 1) . ',}/', $texto);
}

function cpfValido(string $cpf): bool
{
    $cpf = apenasDigitos($cpf);

    if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
        return false;
    }

    for ($t = 9; $t < 11; $t++) {
        $soma = 0;
        for ($i = 0; $i < $t; $i++) {
            $soma += (int) $cpf[$i] * (($t + 1) - $i);
        }
        if ((int) $cpf[$t] !== (((10 * $soma) % 11) % 10)) {
            return false;
        }
    }
    return true;
}

/* =======================================================
   TRAVA DE SEGURANÇA 2: SANITIZAÇÃO E VALIDAÇÃO DOS DADOS
======================================================= */
$endereco      = trim((string) ($_POST['endereco'] ?? ''));
$tel           = trim((string) ($_POST['tel'] ?? ''));
$cpf           = trim((string) ($_POST['cpf'] ?? ''));
$idade         = trim((string) ($_POST['idade'] ?? '')); // Data de nascimento (AAAA-MM-DD)
$como_conheceu = trim((string) ($_POST['como_conheceu'] ?? ''));
$complemento   = trim((string) ($_POST['complemento'] ?? ''));

// Se a opção selecionada for "Outros", usa o complemento
if ($como_conheceu === 'Outros' && $complemento !== '') {
    $como_conheceu = $complemento;
}

// Validação de campos vazios
if ($endereco === '' || $tel === '' || $cpf === '' || $idade === '' || $como_conheceu === '') {
    $_SESSION['criar_error'] = '❌ Preencha todos os campos obrigatórios.';
    header("Location: pessoal.php");
    exit;
}

// Validação do Telefone (10 ou 11 dígitos, sem sequências repetidas)
$telDigitos = apenasDigitos($tel);
if (strlen($telDigitos) < 10 || strlen($telDigitos) > 11 || temSequenciaRepetida($telDigitos)) {
    $_SESSION['criar_error'] = '❌ Telefone inválido. Verifique o número informado.';
    header("Location: pessoal.php");
    exit;
}

// Validação Matemática do CPF
if (!cpfValido($cpf)) {
    $_SESSION['criar_error'] = '❌ CPF inválido. Insira um CPF real.';
    header("Location: pessoal.php");
    exit;
}
$cpfDigitos = apenasDigitos($cpf);

// Validação da Idade (Obrigatório ter pelo menos 18 anos)
$dataNasc = DateTimeImmutable::createFromFormat('!Y-m-d', $idade);
$dataOk = $dataNasc && $dataNasc->format('Y-m-d') === $idade;

if (!$dataOk) {
    $_SESSION['criar_error'] = '❌ Data de nascimento inválida.';
    header("Location: pessoal.php");
    exit;
} else {
    $hoje = new DateTimeImmutable('today');
    $maiorDe18 = $hoje->modify('-18 years');

    if ($dataNasc > $maiorDe18) {
        $_SESSION['criar_error'] = '❌ Deve ter pelo menos 18 anos para realizar cadastros e pagamentos.';
        header("Location: pessoal.php");
        exit;
    } elseif ($dataNasc < $hoje->modify('-120 years')) {
        $_SESSION['criar_error'] = '❌ Data de nascimento inválida.';
        header("Location: pessoal.php");
        exit;
    }
}

/* =======================================================
   VERIFICAÇÃO E GRAVAÇÃO NA BASE DE DADOS (PDO)
======================================================= */
$id_cliente = (int) $_SESSION['id'];

try {
    // Verifica se o CPF já pertence a outro cliente usando PDO
    $stmt = $pdo->prepare(
        "SELECT id_cliente FROM pessoais
          WHERE REPLACE(REPLACE(cpf, '.', ''), '-', '') = ?
            AND id_cliente <> ?
          LIMIT 1"
    );
    $stmt->execute([$cpfDigitos, $id_cliente]);
    $cpfEmUso = $stmt->fetch() ? true : false;

    if ($cpfEmUso) {
        $_SESSION['criar_error'] = '❌ Este CPF já está cadastrado por outro usuário.';
        header("Location: pessoal.php");
        exit;
    }

    // Verifica se o cliente já tem registo criado para atualizar ou inserir
    $stmt = $pdo->prepare("SELECT id_cliente FROM pessoais WHERE id_cliente = ? LIMIT 1");
    $stmt->execute([$id_cliente]);
    $jaExiste = $stmt->fetch() ? true : false;

    if ($jaExiste) {
        $stmt = $pdo->prepare(
            "UPDATE pessoais
                SET endereco = ?, telefone = ?, cpf = ?, data_nascimento = ?, como_conheceu = ?
              WHERE id_cliente = ?"
        );
        $stmt->execute([$endereco, $telDigitos, $cpfDigitos, $idade, $como_conheceu, $id_cliente]);
    } else {
        $stmt = $pdo->prepare(
            "INSERT INTO pessoais (id_cliente, endereco, telefone, cpf, data_nascimento, como_conheceu)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$id_cliente, $endereco, $telDigitos, $cpfDigitos, $idade, $como_conheceu]);
    }

    $_SESSION['criar_sucesso'] = '✅ Cadastro realizado com sucesso!';
    header("Location: anamnes.php"); // Avança para a página de anamnese após o sucesso!
    exit;

} catch (\PDOException $e) {
    error_log("Erro no banco de dados (dadocliente.php): " . $e->getMessage());

    // Código 23000 ou mensagem de duplicidade (Erro 1062 do MySQL)
    if ($e->getCode() == 23000 || str_contains($e->getMessage(), '1062')) {
        $_SESSION['criar_error'] = '❌ CPF já cadastrado.';
    } else {
        $_SESSION['criar_error'] = '❌ Erro ao cadastrar os dados. Tente novamente mais tarde.';
    }
    
    header("Location: pessoal.php");
    exit;
}
?>