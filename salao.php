 <?php
header('Content-Type: application/json'); // Garante que a resposta seja JSON

// --- CONFIGURAÇÃO DO BANCO DE DADOS (PDO) ---
$host = "localhost";
$db = "salao"; // !!! TROQUE PELO NOME REAL DO SEU BANCO !!!
$user = "root";            // Usuário padrão (XAMPP/WAMP)
$pass = "";                // Senha padrão (geralmente vazia no ambiente local)
$charset = 'utf8mb4';

// Nomes da Tabela e Colunas (Ajuste se necessário, baseado no seu phpMyAdmin)
$tabela = "agendamento";       
$coluna_servico = "Servi";     // Coluna para o Serviço
$coluna_alergia = "Alergia";   // Coluna para a Alergia

// --- DADOS DO WHATSAPP ---
$numeroWhats = "556195756256"; 

try {
    // 1. Conexão com o Banco de Dados via PDO
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $opcoes = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, $user, $pass, $opcoes);

} catch (\PDOException $e) {
    // Retorna erro de conexão
    echo json_encode(['status' => 'error', 'message' => 'Erro de conexão PDO: ' . $e->getMessage()]);
    exit();
}

// 2. Recebe e Sanitiza os Dados
// Nota: O prepared statement do PDO já previne injeção SQL, mas a validação de entrada é sempre boa.
$nome      = trim($_POST['nome'] ?? '');
$telefone  = trim($_POST['telefone'] ?? '');
$servico   = trim($_POST['servico'] ?? '');
$alergia   = trim($_POST['alergia'] ?? ''); 
$data_hora = trim($_POST['data_hora'] ?? '');

// Verifica se os campos essenciais estão vazios
if (empty($nome) || empty($telefone) || empty($servico) || empty($data_hora)) {
    echo json_encode(['status' => 'error', 'message' => 'Dados obrigatórios ausentes.']);
    exit();
}

// Separa Data e Hora para o formato do MySQL
$data_sql = date('Y-m-d', strtotime($data_hora));
$hora_sql = date('H:i:s', strtotime($data_hora));


// 3. Insere no Banco de Dados usando Prepared Statement (PDO)
try {
    $sql = "INSERT INTO {$tabela} (Nome_completo, Telefone, {$coluna_servico}, {$coluna_alergia}, Data, Hora) 
            VALUES (:nome, :telefone, :servico, :alergia, :data_sql, :hora_sql)";
            
    $stmt = $pdo->prepare($sql);
    
    // Associa os valores
    $stmt->execute([
        ':nome'      => $nome,
        ':telefone'  => $telefone,
        ':servico'   => $servico,
        ':alergia'   => $alergia,
        ':data_sql'  => $data_sql,
        ':hora_sql'  => $hora_sql
    ]);

} catch (\PDOException $e) {
    // Retorna erro se a inserção falhar
    echo json_encode(['status' => 'error', 'message' => 'Erro ao inserir no banco: ' . $e->getMessage()]);
    exit();
}

// 4. Constrói o Link do WhatsApp (após o sucesso no banco)
$texto_whats = "Novo agendamento confirmado e salvo:\nNome: {$nome}\nTelefone: {$telefone}\nServiço: {$servico}\nData e hora: {$data_hora}";
if (!empty($alergia)) {
    $texto_whats .= "\nAlergia: {$alergia}";
}

$texto_encoded = urlencode($texto_whats);
$urlWhats = "https://wa.me/{$numeroWhats}?text={$texto_encoded}";

// 5. Retorna Sucesso e o Link do WhatsApp para o JavaScript
echo json_encode([
    'status' => 'success',
    'message' => 'Agendamento salvo e link gerado.',
    'whatsapp_url' => $urlWhats
]);
?>