 <?php 
// Inicia a sessão de forma segura se ainda não tiver sido iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "conecte.php";

// Bloqueia o acesso se o usuário não estiver devidamente autenticado
if (!isset($_SESSION['id'])) {
    header("Location: cliente.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    /* =======================================================
       TRAVA DE SEGURANÇA 1: VALIDAÇÃO DE TOKEN CSRF
    ======================================================= */
    if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Acesso inválido ou sessão expirada.");
    }

    $id_cliente = $_SESSION['id'];

    // Sanitiza e valida o array de serviços recebidos
    $servicos_brutos = $_POST['servocosalao'] ?? [];
    $servicos = [];
    foreach ($servicos_brutos as $servico_item) {
        $limpo = htmlspecialchars(trim($servico_item), ENT_QUOTES, 'UTF-8');
        if (!empty($limpo)) {
            $servicos[] = $limpo;
        }
    }

    // Se nenhum serviço válido foi selecionado, interrompe
    if (empty($servicos)) {
        die("Selecione pelo menos um serviço válido.");
    }

    $servicosTexto = implode(", ", $servicos);

    /* =======================================================
       TRAVA DE SEGURANÇA 2: PREPARED STATEMENTS DINÂMICO (ANTI-SQL INJECTION)
    ======================================================= */
    // Monta os marcadores de interrogação (?) dinamicamente conforme a quantidade de serviços
    $placeholders = implode(',', array_fill(0, count($servicos), '?'));
    $sql_preco = "SELECT SUM(preco) as total FROM servicos WHERE nome IN ($placeholders)";
    
    $stmt_preco = $conn->prepare($sql_preco);
    
    // Vincula dinamicamente os tipos dos parâmetros (ex: "sss" se forem 3 serviços)
    $tipos = str_repeat('s', count($servicos));
    $stmt_preco->bind_param($tipos, ...$servicos);
    $stmt_preco->execute();
    $result_preco = $stmt_preco->get_result()->fetch_assoc();
    
    // Garante que o preço total nunca seja nulo ou manipulado
    $precoTotal = isset($result_preco['total']) ? (float)$result_preco['total'] : 0.00;
    $stmt_preco->close();

    // Recebe e sanitiza os dados de data e hora do POST
    $data = htmlspecialchars(trim($_POST['data'] ?? ''), ENT_QUOTES, 'UTF-8');
    $hora = htmlspecialchars(trim($_POST['hora'] ?? ''), ENT_QUOTES, 'UTF-8');

    if (empty($data) || empty($hora)) {
        die("Data e hora são obrigatórias.");
    }

    // VERIFICA SE HORÁRIO JÁ EXISTE (Mantido estruturalmente)
    $verifica = $conn->prepare("SELECT id FROM agendamentos WHERE data_agendamento = ? AND hora_agendamento = ?");
    $verifica->bind_param("ss", $data, $hora);
    $verifica->execute();
    $verifica->store_result();

    if ($verifica->num_rows > 0) {
        $verifica->close();
        die("Horário já reservado!");
    }
    $verifica->close();

    /* =======================================================
       TRAVA DE SEGURANÇA 3: INSERÇÃO REESCRITA COM SINTAXE PADRÃO SEGURA
    ======================================================= */
    $sql = "INSERT INTO agendamentos 
    (id_cliente, servico, preco_servico, data_agendamento, hora_agendamento) 
    VALUES (?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    // Vinculação estrita dos tipos correspondentes da tabela: id_cliente (i), servico (s), preco_servico (d), data_agendamento (s), hora_agendamento (s)
    $stmt->bind_param("isdss", $id_cliente, $servicosTexto, $precoTotal, $data, $hora);

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        header("Location: pagamendo.php");
        exit;
    } else {
        // Registra o erro técnico em ambiente interno de logs e mascara a saída pública do usuário
        error_log("Erro crítico ao salvar agendamento: " . $stmt->error);
        echo "Ocorreu um erro ao processar seu agendamento. Tente novamente mais tarde.";
    }

    $stmt->close();
    $conn->close();
}
?>