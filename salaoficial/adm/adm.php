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

            // 1) PAGAMENTOS: registra crédito para cada agendamento que já tinha valor pago
            //    (SUM + GROUP BY evita duplicar caso haja mais de um pagamento por agendamento)
            $stmt = $pdo->prepare(
                "SELECT a.id, a.id_cliente, SUM(p.valor_pago) AS pago
                   FROM agendamentos a
                   JOIN pagamentos p ON p.id_agendamento = a.id
                  WHERE a.data_agendamento BETWEEN :inicio AND :fim
                    AND a.status IN ('confirmado', 'pendente')
                  GROUP BY a.id, a.id_cliente
                 HAVING pago > 0"
            );
            $stmt->execute([':inicio' => $inicio, ':fim' => $fim]);
            $comPagamento = $stmt->fetchAll();

            $stmtCredito = $pdo->prepare(
                "INSERT IGNORE INTO creditos_clientes (id_cliente, id_agendamento, valor, motivo)
                 VALUES (:cliente, :agendamento, :valor, :motivo)"
            );
            $totalCreditos = 0.0;
            foreach ($comPagamento as $linha) {
                $stmtCredito->execute([
                    ':cliente'     => $linha['id_cliente'],
                    ':agendamento' => $linha['id'],
                    ':valor'       => $linha['pago'],
                    ':motivo'      => $motivo,
                ]);
                $totalCreditos += (float) $linha['pago'];
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
                      . " registrado como crédito pendente.";
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
            const r = await fetch('contar_emergencia.php?inicio=' + encodeURIComponent(inicio) + '&fim=' + encodeURIComponent(fim));
            if (r.ok) {
                const info = await r.json();
                detalhe = '<strong>' + info.total + '</strong> agendamento(s) serão cancelados.';
                if (info.pagos > 0) {
                    detalhe += '<br><strong>' + info.pagos + '</strong> já pagaram (R$ ' + real(info.valor) + ') e ficarão como crédito pendente.';
                }
            }
        } catch (_) { /* segue com a mensagem genérica */ }

        const periodo = inicio === fim ? br(inicio) : br(inicio) + ' a ' + br(fim);

        Swal.fire({
            icon: 'warning',
            title: 'Ativar emergência?',
            html: 'Período: <strong>' + periodo + '</strong><br>' + detalhe + '<br>A agenda será bloqueada para novas marcações.',
            showCancelButton: true,
            confirmButtonText: 'Sim, ativar',
            cancelButtonText: 'Voltar',
            confirmButtonColor: '#e53e3e'
        }).then(res => {
            if (res.isConfirmed) form.submit(); // o campo hidden já leva o ativar_emergencia
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
    });
</script>
<?php unset($_SESSION['flash']); endif; ?>
</body>
</html>