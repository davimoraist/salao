 <?php
// finalizar_agendamento.php
session_start();
require "verificar.php";

// Só o admin logado pode finalizar um agendamento — sem essa checagem,
// qualquer pessoa que soubesse a URL (?id=5) conseguiria finalizar
// agendamentos de outras pessoas sem estar logada.
if (!isset($_SESSION['idusuario']) || empty($_SESSION['idusuario'])) {
    header("Location: index.php");
    exit;
}

$host = "localhost";
$db = "salao";
$user = "root";
$pass = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    // Faz o PDO lançar exceção em caso de erro, em vez de falhar
    // silenciosamente (como acontecia antes, escondendo o problema).
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}

// Verifica se o ID foi enviado pela URL, e garante que é um número
// (evita passar lixo tipo "id=abc" ou "id=1 OR 1=1" pra query)
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id) {
    try {
        // Prepara o comando para mudar o status e gravar a data/hora
        // exata da finalização. Isso faz o agendamento sumir da agenda
        // (que só mostra 'pendente'/'confirmado') e permite mostrar
        // pro cliente quando o serviço foi concluído.
        $sql = $pdo->prepare("UPDATE agendamentos SET status = 'concluido', finalizado_em = NOW() WHERE id = ?");
        $sql->execute([$id]);

        if ($sql->rowCount() === 0) {
            // A query rodou sem erro, mas não alterou nenhuma linha —
            // normalmente significa que o ID não existe mais.
            die("Nenhum agendamento com id=$id foi encontrado para finalizar.");
        }

        // Se deu certo, volta para a tela administrativa
        header("Location: adm.php?sucesso=1");
        exit;
    } catch (PDOException $e) {
        // Mostra o erro real (ex: valor 'concluido' não permitido pelo
        // ENUM da coluna status) em vez de falhar calado.
        die("Erro ao finalizar agendamento: " . $e->getMessage());
    }
} else {
    echo "ID não fornecido.";
}
?>