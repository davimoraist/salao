 <?php
session_start();
require "verificar.php";

if (!isset($_SESSION['idusuario']) || empty($_SESSION['idusuario'])) {
    header("Location: index.php");
    exit;
}

// conexão banco
$host = "localhost";
$db = "salao";
$user = "root";
$pass = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    die("Erro ao conectar ao banco.");
}

// ===================== PRÉVIA DA EMERGÊNCIA (JSON) =====================
// adm.php?acao=previa_emergencia&inicio=AAAA-MM-DD&fim=AAAA-MM-DD
// Só consulta: lista o que seria cancelado, com valor já pago e telefone da cliente.
// (a sessão já foi validada no começo deste arquivo)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['acao'] ?? '') === 'previa_emergencia') {
    header('Content-Type: application/json; charset=utf-8');

    $pInicio = $_GET['inicio'] ?? '';
    $pFim    = $_GET['fim'] ?? '';
    $pd1 = DateTime::createFromFormat('Y-m-d', $pInicio);
    $pd2 = DateTime::createFromFormat('Y-m-d', $pFim);

    if (!$pd1 || !$pd2 || $pd1->format('Y-m-d') !== $pInicio || $pd2->format('Y-m-d') !== $pFim || $pFim < $pInicio) {
        http_response_code(400);
        echo json_encode(['erro' => 'Datas inválidas']);
        exit;
    }

    try {
        $stmt = $pdo->prepare(
            "SELECT a.id,
                    COALESCE(c.nome, 'Cliente não cadastrado') AS nome,
                    a.servico,
                    a.data_agendamento,
                    a.hora_agendamento,
                    pe.telefone,
                    (SELECT COALESCE(SUM(pg.valor_pago), 0)
                       FROM pagamentos pg
                      WHERE pg.id_agendamento = a.id) AS pago
               FROM agendamentos a
          LEFT JOIN cliente c   ON c.id = a.id_cliente
          LEFT JOIN pessoais pe ON pe.id_cliente = a.id_cliente
              WHERE a.data_agendamento BETWEEN :inicio AND :fim
                AND a.status IN ('confirmado', 'pendente')
           ORDER BY a.data_agendamento, a.hora_agendamento"
        );
        $stmt->execute([':inicio' => $pInicio, ':fim' => $pFim]);

        $lista = [];
        $pagos = 0;
        $valor = 0.0;
        foreach ($stmt->fetchAll() as $l) {
            $pago = (float) $l['pago'];
            if ($pago > 0) {
                $pagos++;
                $valor += $pago;
            }
            $lista[] = [
                'nome'     => $l['nome'],
                'servico'  => $l['servico'],
                'data'     => $l['data_agendamento'],
                'hora'     => substr((string) $l['hora_agendamento'], 0, 5),
                'pago'     => $pago,
                'telefone' => $l['telefone'],
            ];
        }

        echo json_encode([
            'total'        => count($lista),
            'pagos'        => $pagos,
            'valor'        => $valor,
            'agendamentos' => $lista,
        ]);
    } catch (PDOException $e) {
        error_log("previa emergência: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['erro' => 'Erro ao consultar']);
    }
    exit;
}

// ===================== CONFERIR SENHA DO USUÁRIO LOGADO =====================
// Aceita senha com hash (password_hash) e também senha em texto puro (como o login atual usa).
function conferirSenhaUsuario(PDO $pdo, int $idUsuario, string $senhaDigitada): bool {
    if ($senhaDigitada === '') {
        return false;
    }
    try {
        $stmt = $pdo->prepare("SELECT password FROM usuario WHERE idusuario = :id");
        $stmt->execute([':id' => $idUsuario]);
        $guardada = $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("conferirSenhaUsuario: " . $e->getMessage());
        return false;
    }
    if ($guardada === false || $guardada === null || $guardada === '') {
        return false;
    }
    $guardada = (string) $guardada;
    if (!empty(password_get_info($guardada)['algo'])) {
        return password_verify($senhaDigitada, $guardada);
    }
    return hash_equals($guardada, $senhaDigitada);
}

// ===================== AVISO DE CANCELAMENTO (WHATSAPP) =====================
// Celular com DDD. Aceita:
//  - 11 dígitos (DDD + 9 + 8 dígitos): usa como está;
//  - 10 dígitos cujo número começa com 6-9 depois do DDD (celular antigo, sem o 9 na frente):
//    acrescenta o 9 depois do DDD, como o WhatsApp exige hoje.
// Telefone fixo (começa com 2-5) ou número com tamanho estranho: não gera botão.
function telefoneWhatsApp(?string $tel): ?string {
    $d = ltrim(preg_replace('/\D+/', '', (string) $tel), '0');
    if (strlen($d) >= 12 && substr($d, 0, 2) === '55') {
        $d = substr($d, 2);
    }
    if (strlen($d) === 10 && preg_match('/^[1-9][1-9][6-9]/', $d)) {
        $d = substr($d, 0, 2) . '9' . substr($d, 2);
    }
    return preg_match('/^[1-9][1-9]9\d{8}$/', $d) ? '55' . $d : null;
}

// 5561999408277 -> (61) 99940-8277
function formatarFone(string $fone55): string {
    $n = substr($fone55, 2);
    return '(' . substr($n, 0, 2) . ') ' . substr($n, 2, 5) . '-' . substr($n, 7);
}

function linkAvisoWhatsApp(array $av): ?string {
    $fone = telefoneWhatsApp($av['telefone'] ?? '');
    if (!$fone) {
        return null;
    }
    $primeiro = explode(' ', trim($av['nome']))[0];
    $data = date('d/m/Y', strtotime($av['data_agendamento']));
    $hora = substr((string) $av['hora_agendamento'], 0, 5);

    $msg = "Olá, $primeiro! Aqui é do MT Nail Designer. Infelizmente precisamos cancelar o seu horário de "
         . $av['servico'] . " do dia $data às $hora, por um imprevisto. Pedimos desculpas pelo transtorno!";
    if ((float) $av['valor_pago'] > 0) {
        $msg .= " O sinal que você pagou (R$ " . number_format((float) $av['valor_pago'], 2, ',', '.')
              . ") ficou como crédito para você; vamos combinar a devolução ou o uso numa nova data.";
    }
    $msg .= " Quando quiser remarcar, é só acessar o site ou responder esta mensagem. 💅";

    return 'https://wa.me/' . $fone . '?text=' . rawurlencode($msg);
}

// ===================== REABRIR AGENDA (Maria voltou antes do previsto) =====================
// O bloqueio é identificado pelas datas (data_inicio + data_fim), então não depende da coluna "id".
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reabrir_agenda'])) {

    $bInicio  = $_POST['data_inicio'] ?? '';
    $bFim     = $_POST['data_fim'] ?? '';
    $novaData = $_POST['nova_data'] ?? '';

    $okData = function (string $d): bool {
        $o = DateTime::createFromFormat('Y-m-d', $d);
        return $o && $o->format('Y-m-d') === $d;
    };

    if (!$okData($bInicio) || !$okData($bFim) || !$okData($novaData)) {
        $_SESSION['flash'] = ['tipo' => 'error', 'msg' => 'Dados inválidos para reabrir a agenda. Escolha a data de volta e tente de novo.'];
    } elseif ($novaData < $bInicio || $novaData > $bFim) {
        $_SESSION['flash'] = ['tipo' => 'error', 'msg' => 'A data de volta precisa estar dentro do período bloqueado.'];
    } else {
        try {
            if ($novaData === $bInicio) {
                // volta já no primeiro dia: o bloqueio inteiro deixa de existir
                $stmt = $pdo->prepare("DELETE FROM bloqueios_agenda WHERE data_inicio = :i AND data_fim = :f");
                $stmt->execute([':i' => $bInicio, ':f' => $bFim]);
            } else {
                // o bloqueio termina na véspera do dia em que ela volta
                $novoFim = (new DateTime($novaData))->modify('-1 day')->format('Y-m-d');
                $stmt = $pdo->prepare("UPDATE bloqueios_agenda SET data_fim = :novo WHERE data_inicio = :i AND data_fim = :f");
                $stmt->execute([':novo' => $novoFim, ':i' => $bInicio, ':f' => $bFim]);
            }

            if ($stmt->rowCount() > 0) {
                $_SESSION['flash'] = [
                    'tipo' => 'success',
                    'msg'  => 'Agenda reaberta a partir de ' . date('d/m/Y', strtotime($novaData))
                              . '. Os agendamentos já cancelados continuam cancelados; as clientes podem marcar de novo pelo site.'
                ];
            } else {
                $_SESSION['flash'] = ['tipo' => 'error', 'msg' => 'Bloqueio não encontrado (talvez já tenha sido reaberto). Nada foi alterado.'];
            }
        } catch (PDOException $e) {
            error_log("Erro ao reabrir agenda: " . $e->getMessage());
            $_SESSION['flash'] = ['tipo' => 'error', 'msg' => 'Erro ao reabrir a agenda. Nada foi alterado.'];
        }
    }

    header("Location: adm.php");
    exit;
}

// ===================== EMERGÊNCIA / AUSÊNCIA =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ativar_emergencia'])) {

    $inicio = $_POST['emergencia_inicio'] ?? '';
    $fim    = $_POST['emergencia_fim'] ?? '';
    $motivo = trim($_POST['motivo'] ?? '');
    if ($motivo === '') {
        $motivo = 'Emergência / Imprevisto Pessoal';
    }
    $motivo = mb_substr($motivo, 0, 255);

    // valida formato e ordem das datas
    $d1 = DateTime::createFromFormat('Y-m-d', $inicio);
    $d2 = DateTime::createFromFormat('Y-m-d', $fim);
    $datasOk = $d1 && $d2
        && $d1->format('Y-m-d') === $inicio
        && $d2->format('Y-m-d') === $fim;

    // --- confirmação de senha + limite de 5 tentativas (bloqueio de 10 min) ---
    $erroSenha = null;
    if (!empty($_SESSION['emerg_bloqueado_ate']) && $_SESSION['emerg_bloqueado_ate'] > time()) {
        $min = (int) ceil(($_SESSION['emerg_bloqueado_ate'] - time()) / 60);
        $erroSenha = "Muitas tentativas incorretas. Tente novamente em $min minuto(s).";
    } elseif (!conferirSenhaUsuario($pdo, (int) $_SESSION['idusuario'], (string) ($_POST['senha_emergencia'] ?? ''))) {
        $_SESSION['emerg_tentativas'] = ($_SESSION['emerg_tentativas'] ?? 0) + 1;
        if ($_SESSION['emerg_tentativas'] >= 5) {
            $_SESSION['emerg_bloqueado_ate'] = time() + 600;
            $_SESSION['emerg_tentativas'] = 0;
            $erroSenha = 'Senha incorreta. Muitas tentativas: bloqueado por 10 minutos.';
        } else {
            $restam = 5 - $_SESSION['emerg_tentativas'];
            $erroSenha = "Senha incorreta. Restam $restam tentativa(s). Nada foi alterado.";
        }
    } else {
        unset($_SESSION['emerg_tentativas'], $_SESSION['emerg_bloqueado_ate']);
    }

    if ($erroSenha !== null) {
        $_SESSION['flash'] = ['tipo' => 'error', 'msg' => $erroSenha];
        // guarda o que ela já tinha preenchido (nunca a senha)
        $_SESSION['emerg_form'] = ['inicio' => $inicio, 'fim' => $fim, 'motivo' => $motivo];
    } elseif (!$datasOk) {
        $_SESSION['flash'] = ['tipo' => 'error', 'msg' => 'Datas inválidas.'];
    } elseif ($fim < $inicio) {
        $_SESSION['flash'] = ['tipo' => 'error', 'msg' => 'A data final não pode ser antes da inicial.'];
    } else {
        try {
            $pdo->beginTransaction();

            // 1) AFETADAS: levanta quem será cancelada e quem já tinha pago
            //    (o valor pago continua registrado em "pagamentos"; nenhuma tabela nova é usada)
            $stmt = $pdo->prepare(
                "SELECT a.id,
                        (SELECT COALESCE(SUM(p.valor_pago), 0)
                           FROM pagamentos p
                          WHERE p.id_agendamento = a.id) AS pago
                   FROM agendamentos a
                  WHERE a.data_agendamento BETWEEN :inicio AND :fim
                    AND a.status IN ('confirmado', 'pendente')"
            );
            $stmt->execute([':inicio' => $inicio, ':fim' => $fim]);
            $afetadas = $stmt->fetchAll();

            $comPagamento = [];
            $totalCreditos = 0.0;
            foreach ($afetadas as $linha) {
                if ((float) $linha['pago'] > 0) {
                    $comPagamento[] = $linha;
                    $totalCreditos += (float) $linha['pago'];
                }
            }

            // 2) cancela agendamentos do período
            $stmt = $pdo->prepare(
                "UPDATE agendamentos
                    SET status = 'cancelado'
                  WHERE data_agendamento BETWEEN :inicio AND :fim
                    AND status IN ('confirmado', 'pendente')"
            );
            $stmt->execute([':inicio' => $inicio, ':fim' => $fim]);
            $cancelados = $stmt->rowCount();

            // 3) bloqueia a agenda para novas marcações
            $stmt = $pdo->prepare(
                "INSERT INTO bloqueios_agenda (data_inicio, data_fim, motivo)
                 VALUES (:inicio, :fim, :motivo)"
            );
            $stmt->execute([':inicio' => $inicio, ':fim' => $fim, ':motivo' => $motivo]);

            $pdo->commit();

            $msg = "Emergência ativada! Agenda bloqueada de "
                 . $d1->format('d/m/Y') . " a " . $d2->format('d/m/Y')
                 . ". Agendamentos cancelados: $cancelados.";
            if (count($comPagamento) > 0) {
                $msg .= " Atenção: " . count($comPagamento)
                      . " cliente(s) já tinham pago, total de R$ "
                      . number_format($totalCreditos, 2, ',', '.')
                      . ". Combine a devolução ou o uso na remarcação.";
            }
            if (count($afetadas) > 0) {
                $msg .= " Avise as " . count($afetadas) . " cliente(s) pela lista \"Avisar clientes\" abaixo.";
            }
            $_SESSION['flash'] = ['tipo' => 'success', 'msg' => $msg];

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Erro emergência: " . $e->getMessage());
            $_SESSION['flash'] = ['tipo' => 'error', 'msg' => 'Erro ao ativar a emergência. Nada foi alterado.'];
        }
    }

    // evita reenvio do formulário ao dar F5
    header("Location: adm.php");
    exit;
}

$formEmerg = $_SESSION['emerg_form'] ?? [];
unset($_SESSION['emerg_form']);

// Clientes que ainda precisam ser avisadas do cancelamento
$avisos = [];
try {
    $avisos = $pdo->query(
        "SELECT a.id,
                COALESCE(c.nome, 'Cliente não cadastrada') AS nome,
                pe.telefone,
                a.servico, a.data_agendamento, a.hora_agendamento,
                (SELECT COALESCE(SUM(p.valor_pago), 0)
                   FROM pagamentos p
                  WHERE p.id_agendamento = a.id) AS valor_pago
           FROM agendamentos a
      LEFT JOIN cliente c   ON c.id = a.id_cliente
      LEFT JOIN pessoais pe ON pe.id_cliente = a.id_cliente
          WHERE a.status = 'cancelado'
            AND a.data_agendamento >= CURDATE()
            AND EXISTS (SELECT 1
                          FROM bloqueios_agenda b
                         WHERE a.data_agendamento BETWEEN b.data_inicio AND b.data_fim)
       ORDER BY a.data_agendamento, a.hora_agendamento"
    )->fetchAll();
} catch (PDOException $e) {
    error_log("lista de avisos: " . $e->getMessage());
}

// Períodos bloqueados que ainda não terminaram (para a Maria poder reabrir)
$bloqueiosAtivos = [];
try {
    $bloqueiosAtivos = $pdo->query(
        "SELECT DISTINCT data_inicio, data_fim, motivo,
                GREATEST(data_inicio, CURDATE()) AS min_data
           FROM bloqueios_agenda
          WHERE data_fim >= CURDATE()
       ORDER BY data_inicio, data_fim"
    )->fetchAll();
} catch (PDOException $e) {
    error_log("bloqueios ativos: " . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="css/adm.css">
    <title>ADM DO SALÃO</title>
</head>

<body>

<div class="cabeca">
    <h1>Salão da Maria - ADM</h1>
    <span class="nome-usuario">
        Olá! <?php echo $pessoa; ?>
    </span>
</div>
<div class="menu-mobile">
    <button id="btnMenu">☰</button>
</div>
<div class="menu">
    <button onclick="mostrarTela('agenda')" class="active">Agenda</button>
    <button onclick="mostrarTela('cliente')">Clientes</button>
    <button onclick="mostrarTela('servico')">Histórico</button>
    <button onclick="mostrarTela('servicos')">Cadastro de Serviços</button>
    <button onclick="mostrarTela('faturamento')">Faturamento</button>
    <button onclick="mostrarTela('config')">Configurações</button>
    <button onclick="window.location='sair.php'">Sair</button>
</div>

<div class="conteudo">

     <div id="agenda" class="tela ativa">
    <?php
    // Query corrigida: aceita 'Confirmado' ou 'pendente' e previne falhas no JOIN de clientes
 $sql = $pdo->query("SELECT 
    a.id,
    COALESCE(c.nome, 'Cliente não cadastrado') AS nome,
    a.servico,
    a.data_agendamento,
    a.hora_agendamento,
    a.status,
    a.id_cliente,
    p.valor_pago,
    p.valor_restante
FROM agendamentos a
LEFT JOIN cliente c ON a.id_cliente = c.id
LEFT JOIN pagamentos p ON a.id = p.id_agendamento
WHERE a.status IN ('confirmado', 'pendente')
ORDER BY a.data_agendamento DESC, a.hora_agendamento DESC");

    $agendamentos = $sql->fetchAll(PDO::FETCH_ASSOC);
    ?>

    <h2>Agendamentos</h2>

    <table class="tabela-agendamentos">
        <thead>
            <tr>
                <th>Cliente</th>
                <th>Serviço</th>
                <th>Data</th>
                <th>Hora</th>
                <th>Status</th>
                <th>Ver mais</th>
                <th>Finalizar</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($agendamentos)): ?>
                <?php foreach($agendamentos as $agendamento){ ?>
                <tr>
                    <td><?php echo htmlspecialchars($agendamento['nome']); ?></td>
                    <td><?php echo htmlspecialchars($agendamento['servico']); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($agendamento['data_agendamento'])); ?></td>
                    <td><?php echo htmlspecialchars($agendamento['hora_agendamento']); ?></td>
                    <td><strong><?php echo htmlspecialchars($agendamento['status']); ?></strong></td>
                    
                    <td>
                        <button type="button" onclick="mostrarTela('cliente', <?php echo $agendamento['id_cliente']; ?>)">
                            Ver mais
                        </button>
                    </td>

                     <td>
                        <button type="button" 
                                onclick="confirmarFinalizar(
                                    <?php echo $agendamento['id']; ?>, 
                                    <?php echo $agendamento['valor_pago'] ?? 0; ?>, 
                                    <?php echo $agendamento['valor_restante'] ?? 0; ?>
                                )">
                            Finalizar
                        </button>
                    </td>
                </tr>
                <?php } ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="text-align: center;">Nenhum agendamento encontrado no banco.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

    <div id="servicos" class="tela">
        <h1 class="titulo-servicos">Cadastro de Serviços</h1>
        <h2 class="subtitulo-servicos">Gerenciar Serviços e Preços</h2>
        <div id="servicos-area"></div>
        <button id="fab">+</button>
    </div>

    <div id="servico" class="tela">
        <h1>Histórico do Cliente</h1>
        <div class="busca-historico">
            <input type="search" id="busca-historico" placeholder="🔍 Buscar cliente pelo nome..." autocomplete="off">
        </div>
        <input type="search" id="busca-historico" class="campo-busca"
               placeholder="🔍 Buscar cliente pelo nome..." autocomplete="off">
        <p id="busca-vazia" class="busca-vazia">Nenhuma cliente encontrada.</p>
        <div class="container-historico">

    <!-- TABELA -->
    <div class="tabela-historico">
        <table>
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Agendamentos</th>
                    <th>Último agendamento</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody id="corpo-tabela-historico"></tbody>
        </table>
    </div>

    <!-- PAINEL LATERAL -->
    <div id="painel-historico" class="painel-historico">
        <h3>Histórico do Cliente</h3>
        <div id="conteudo-historico">
            Clique em "Ver detalhes"
        </div>
    </div>

</div>
    </div>

    <div id="cliente" class="tela">
        <h1>Clientes</h1>
        <table class="tabela-clientes">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Ver mais</th>
                </tr>
            </thead>
            <tbody id="corpo-tabela-clientes">

            </tbody>
        </table>
    </div>

    <div id="faturamento" class="tela"><h1>Faturamento</h1></div>
    
    <div id="config" class="tela">
    <h1>Configurações</h1>
         <!-- Seção do Botão de Emergência no Painel -->
<div class="card-emergencia">
    <div class="emergencia-header">
        <span class="emergencia-icone">🚨</span>
        <h3>Bloqueio de Emergência / Ausência</h3>
    </div>
    
    <p class="emergencia-descricao">
        Precisa fechar o salão de surpresa? As datas já vêm preenchidas para o dia de hoje. Altere a data final caso vá ficar mais dias fora.
    </p>
    
    <form action="" method="POST" id="form-emergencia">
        <div class="form-grupo-datas">
            <div class="campo-data">
                <label for="emergencia_inicio">Início do Afastamento:</label>
                <input type="date" id="emergencia_inicio" name="emergencia_inicio" value="<?= htmlspecialchars($formEmerg['inicio'] ?? date('Y-m-d')) ?>" required>
            </div>

            <div class="campo-data">
                <label for="emergencia_fim">Retorno / Data Fim:</label>
                <input type="date" id="emergencia_fim" name="emergencia_fim" value="<?= htmlspecialchars($formEmerg['fim'] ?? date('Y-m-d')) ?>" required>
            </div>
        </div>

        <div id="previa-emergencia" class="previa-emergencia"></div>

        <div class="campo-motivo">
            <label for="motivo">Motivo (opcional):</label>
            <input type="text" id="motivo" name="motivo" placeholder="Ex: Emergência de saúde, imprevisto pessoal..." value="<?= htmlspecialchars($formEmerg['motivo'] ?? 'Emergência / Imprevisto Pessoal') ?>">
        </div>

        <div class="campo-motivo">
            <label for="senha_emergencia">Confirme sua senha:</label>
            <input type="password" id="senha_emergencia" name="senha_emergencia" placeholder="Digite sua senha" autocomplete="current-password" required>
        </div>

        <input type="hidden" name="ativar_emergencia" value="1">
        <button type="submit" class="btn-emergencia">
            Ativar Emergência e Cancelar Agendamentos
        </button>
    </form>
</div>

<?php if (!empty($bloqueiosAtivos)): ?>
<div class="card-bloqueios">
    <h3>🔒 Agenda bloqueada</h3>
    <p class="bloqueios-descricao">
        Se a Maria voltar antes do previsto, escolha o primeiro dia em que ela volta a atender e clique em
        <strong>Reabrir agenda</strong>. Os agendamentos já cancelados continuam cancelados; as clientes poderão marcar de novo pelo site.
    </p>

    <?php foreach ($bloqueiosAtivos as $bl): ?>
    <form method="POST" action="" class="form-reabrir bloqueio-item">
        <input type="hidden" name="reabrir_agenda" value="1">
        <input type="hidden" name="data_inicio" value="<?= htmlspecialchars($bl['data_inicio']) ?>">
        <input type="hidden" name="data_fim" value="<?= htmlspecialchars($bl['data_fim']) ?>">

        <div class="bloqueio-info">
            <strong>
                <?= date('d/m/Y', strtotime($bl['data_inicio'])) ?>
                <?= $bl['data_inicio'] !== $bl['data_fim'] ? ' a ' . date('d/m/Y', strtotime($bl['data_fim'])) : '' ?>
            </strong>
            <small><?= htmlspecialchars($bl['motivo']) ?></small>
        </div>

        <div class="bloqueio-acao">
            <label>Voltar a atender em:
                <input type="date" name="nova_data"
                       value="<?= htmlspecialchars($bl['data_fim']) ?>"
                       min="<?= htmlspecialchars($bl['min_data']) ?>"
                       max="<?= htmlspecialchars($bl['data_fim']) ?>" required>
            </label>
            <button type="submit" class="btn-reabrir">Reabrir agenda</button>
        </div>
    </form>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!empty($avisos)): ?>
<div class="card-avisos" id="card-avisos">
    <h3>📲 Avisar clientes (<span id="qtd-avisos"><?= count($avisos) ?></span>)</h3>
    <p class="avisos-descricao">Estes horários foram cancelados pela emergência. Clique em WhatsApp, envie a mensagem e depois em "Já avisei".</p>

    <?php foreach ($avisos as $av): $zap = linkAvisoWhatsApp($av); $foneZap = telefoneWhatsApp($av['telefone'] ?? ''); ?>
    <div class="aviso-item" id="aviso-<?= (int) $av['id'] ?>">
        <div class="aviso-info">
            <strong><?= htmlspecialchars($av['nome']) ?></strong>
            <span><?= htmlspecialchars($av['servico']) ?> — <?= date('d/m/Y', strtotime($av['data_agendamento'])) ?> às <?= htmlspecialchars(substr((string) $av['hora_agendamento'], 0, 5)) ?></span>
            <?php if ((float) $av['valor_pago'] > 0): ?>
                <small>Já pagou: R$ <?= number_format((float) $av['valor_pago'], 2, ',', '.') ?></small>
            <?php endif; ?>
        </div>
        <div class="aviso-acoes">
            <?php if ($zap): ?>
                <a class="btn-zap" href="<?= htmlspecialchars($zap) ?>" target="_blank" rel="noopener">WhatsApp</a>
                <small class="zap-numero">Enviar para <?= htmlspecialchars(formatarFone($foneZap)) ?></small>
            <?php else: ?>
                <span class="sem-zap">Telefone inválido ou ausente<?= !empty($av['telefone']) ? ': ' . htmlspecialchars($av['telefone']) : '' ?></span>
            <?php endif; ?>
            <button type="button" class="btn-avisada" onclick="marcarAvisada(<?= (int) $av['id'] ?>)">Já avisei</button>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
     </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="script.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('form-emergencia');
    if (!form) return;

    const br = d => d.split('-').reverse().join('/');
    const real = v => v.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');

    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        const inicio = form.emergencia_inicio.value;
        const fim = form.emergencia_fim.value;

        if (!form.senha_emergencia.value) {
            Swal.fire({ icon: 'warning', text: 'Digite sua senha para confirmar.', confirmButtonColor: '#734440' });
            return;
        }
        if (fim < inicio) {
            Swal.fire({ icon: 'warning', text: 'A data final não pode ser antes da inicial.', confirmButtonColor: '#734440' });
            return;
        }

        // pergunta ao servidor quantos agendamentos serão afetados
        let detalhe = 'Os agendamentos do período serão cancelados.';
        try {
            const r = await fetch('adm.php?acao=previa_emergencia&inicio=' + encodeURIComponent(inicio) + '&fim=' + encodeURIComponent(fim));
            if (r.ok) {
                const info = await r.json();
                detalhe = '<strong>' + info.total + '</strong> agendamento(s) serão cancelados.';
                if (info.pagos > 0) {
                    detalhe += '<br><strong>' + info.pagos + '</strong> já pagaram (total R$ ' + real(info.valor) + ').';
                }
                if (info.itens && info.itens.length > 0) {
                    const esc = s => String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
                    detalhe += '<div style="text-align:left;max-height:220px;overflow:auto;margin-top:10px;font-size:14px;border:1px solid #eee;border-radius:6px;padding:4px 10px;">';
                    info.itens.forEach(it => {
                        detalhe += '<div style="padding:7px 0;border-bottom:1px solid #eee;">' +
                            '<strong>' + esc(it.nome) + '</strong><br>' +
                            esc(it.servico) + ' &middot; ' + esc(it.data) + ' às ' + esc(it.hora) + '<br>' +
                            (it.pago > 0 ? '<span style="color:#2f855a;">Já pagou: R$ ' + real(it.pago) + '</span>'
                                         : '<span style="color:#777;">Nada pago</span>') +
                            '</div>';
                    });
                    detalhe += '</div>';
                }
            }
        } catch (_) { /* segue com a mensagem genérica */ }

        const periodo = inicio === fim ? br(inicio) : br(inicio) + ' a ' + br(fim);

        Swal.fire({
            icon: 'warning',
            title: 'Ativar emergência?',
            html: 'Período: <strong>' + periodo + '</strong><br>' + detalhe + '<br>A agenda será bloqueada para novas marcações.',
            showCancelButton: true,
            width: 560,
            confirmButtonText: 'Sim, ativar',
            cancelButtonText: 'Voltar',
            confirmButtonColor: '#e53e3e'
        }).then(res => {
            if (res.isConfirmed) form.submit(); // o campo hidden já leva o ativar_emergencia
        });
    });
});
</script>

<script>
// "Já avisei": como não existe tabela para isso, a marcação fica guardada neste navegador
(function () {
    const chave = id => 'avisada_' + id;

    function atualizarContagem() {
        const restantes = document.querySelectorAll('.aviso-item:not(.avisada)').length;
        const qtd = document.getElementById('qtd-avisos');
        if (qtd) qtd.textContent = restantes;
        if (restantes === 0) {
            const card = document.getElementById('card-avisos');
            if (card) card.remove();
        }
    }

    function esconder(linha) {
        linha.classList.add('avisada');
        linha.style.display = 'none';
    }

    window.marcarAvisada = function (id) {
        try { localStorage.setItem(chave(id), '1'); } catch (_) {}
        const linha = document.getElementById('aviso-' + id);
        if (linha) esconder(linha);
        atualizarContagem();
    };

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.aviso-item').forEach(function (linha) {
            const id = linha.id.replace('aviso-', '');
            try { if (localStorage.getItem(chave(id))) esconder(linha); } catch (_) {}
        });
        atualizarContagem();
    });
})();
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    // ---------- prévia: o que será cancelado no período escolhido ----------
    const inicioEl = document.getElementById('emergencia_inicio');
    const fimEl = document.getElementById('emergencia_fim');
    const previa = document.getElementById('previa-emergencia');

    if (inicioEl && fimEl && previa) {
        const esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        const real = v => 'R$ ' + Number(v).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        const br = d => d.split('-').reverse().join('/');
        let timer;
        let pedido = 0;

        async function carregarPrevia() {
            const inicio = inicioEl.value;
            const fim = fimEl.value;
            const meu = ++pedido;

            if (!inicio || !fim || fim < inicio) {
                previa.innerHTML = '';
                return;
            }
            try {
                const r = await fetch('adm.php?acao=previa_emergencia&inicio=' + encodeURIComponent(inicio) + '&fim=' + encodeURIComponent(fim));
                if (!r.ok) throw new Error('HTTP ' + r.status);
                const info = await r.json();
                if (meu !== pedido) return;

                if (!info.total) {
                    previa.innerHTML = '<p class="previa-vazia">Nenhum agendamento neste período.</p>';
                    return;
                }
                let linhas = '';
                info.agendamentos.forEach(a => {
                    linhas += '<tr><td>' + br(a.data) + ' ' + esc(a.hora) + '</td><td>' + esc(a.nome) +
                              '</td><td>' + esc(a.servico) + '</td><td>' + (a.pago > 0 ? real(a.pago) : '—') + '</td></tr>';
                });
                previa.innerHTML =
                    '<p class="previa-titulo"><strong>' + info.total + '</strong> agendamento(s) neste período' +
                    (info.pagos > 0 ? ' &middot; já pago: <strong>' + real(info.valor) + '</strong>' : '') + '</p>' +
                    '<div class="previa-tabela-wrap"><table class="previa-tabela"><thead><tr><th>Dia / Hora</th><th>Cliente</th><th>Serviço</th><th>Já pagou</th></tr></thead><tbody>' +
                    linhas + '</tbody></table></div>';
            } catch (erro) {
                if (meu !== pedido) return;
                console.error('Prévia da emergência:', erro);
                previa.innerHTML = '<p class="previa-vazia">Não foi possível carregar a prévia.</p>';
            }
        }

        [inicioEl, fimEl].forEach(el => el.addEventListener('change', () => {
            clearTimeout(timer);
            timer = setTimeout(carregarPrevia, 250);
        }));
        carregarPrevia();
    }


});
</script>

<script>
// Confirmação ao reabrir a agenda.
// Se qualquer coisa falhar aqui (ex.: SweetAlert não carregou), o formulário é enviado direto:
// o botão NUNCA fica "morto".
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.form-reabrir').forEach(function (f) {
        f.addEventListener('submit', function (e) {
            if (f.dataset.confirmado === '1') return;            // já confirmou: deixa enviar
            if (typeof Swal === 'undefined') return;            // sem SweetAlert: envia direto

            let dia = '';
            try {
                const campo = f.querySelector('input[name="nova_data"]');
                dia = campo && campo.value ? campo.value.split('-').reverse().join('/') : '';
            } catch (erro) {
                console.error('Reabrir agenda:', erro);
                return;                                          // envia direto
            }

            e.preventDefault();
            Swal.fire({
                icon: 'question',
                title: 'Reabrir a agenda?',
                html: 'As clientes poderão marcar a partir de <strong>' + dia + '</strong>.<br>Os agendamentos já cancelados continuam cancelados.',
                showCancelButton: true,
                confirmButtonText: 'Sim, reabrir',
                cancelButtonText: 'Voltar',
                confirmButtonColor: '#734440'
            }).then(function (res) {
                if (res.isConfirmed) {
                    f.dataset.confirmado = '1';
                    HTMLFormElement.prototype.submit.call(f);    // não depende do nome dos campos
                }
            });
        });
    });
});
</script>

<?php if (!empty($_SESSION['flash'])): ?>
<script>
    mostrarTela('config');
    Swal.fire({
        icon: <?= json_encode($_SESSION['flash']['tipo']) ?>,
        text: <?= json_encode($_SESSION['flash']['msg']) ?>,
        confirmButtonColor: '#734440'
    })<?php if ($_SESSION['flash']['tipo'] === 'success'): ?>.then(function () {
        const lista = document.getElementById('card-avisos');
        if (lista) lista.scrollIntoView({ behavior: 'smooth' });
    })<?php endif; ?>;
</script>
<?php unset($_SESSION['flash']); endif; ?>
</body>
</html>