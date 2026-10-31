 <?php
session_start();

// Carrega o arquivo de conexão PDO
require_once __DIR__ . "/conecte.php";

if (!isset($pdo) || !($pdo instanceof PDO)) {
    die("Erro crítico: A conexão PDO com o banco não foi encontrada. Verifique o arquivo de conexão.");
}

// Ative (true) só para diagnosticar o problema da senha. Depois volte para false.
$DEBUG = false;

// 1. Pega o ID do cliente logado na sessão (chave 'id' = cliente.id)
$id = isset($_SESSION['id']) ? (int) $_SESSION['id'] : 0;
if ($id <= 0) {
    header("Location: cliente.php");
    exit;
}

$mensagem = "";
$erro = "";

// Marca a opção selecionada de um <select>
function sel(array $dados, string $campo, string $valor, string $padrao = ''): string {
    $atual = $dados[$campo] ?? $padrao;
    return $atual === $valor ? 'selected' : '';
}

// 2. Se o formulário foi enviado, valida a senha e atualiza os dados
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senha_atual       = $_POST['senha_atual'] ?? '';

    // Dados pessoais editáveis
    $telefone          = trim($_POST['telefone'] ?? '');
    $endereco          = trim($_POST['endereco'] ?? '');

    // Dados de saúde / Anamnese
    $diabetes          = trim($_POST['diabetes'] ?? 'Não');
    $gestante          = trim($_POST['gestante'] ?? 'Não');
    $alergias          = trim($_POST['alergias'] ?? 'Não');
    $especificar_alerg = trim($_POST['especificar_alergia'] ?? '');
    $cuticula          = trim($_POST['cuticula'] ?? 'Sim');
    $onicomicose       = trim($_POST['onicomicose'] ?? 'Não');
    $especificar_onico = trim($_POST['especificar_onico'] ?? '');
    $medicamento       = trim($_POST['medicamento'] ?? 'Não');
    $qual_medicamento  = trim($_POST['qual_medicamento'] ?? '');
    $lamina            = trim($_POST['lamina'] ?? '');
    $outro_lamina      = trim($_POST['outro_lamina'] ?? '');
    $encravada         = trim($_POST['encravada'] ?? 'Não');
    $onicofagia        = trim($_POST['onicofagia'] ?? 'Não');
    $esporte           = trim($_POST['esporte'] ?? 'Não');
    $piscina           = trim($_POST['piscina'] ?? 'Não');

    if ($senha_atual === '') {
        $erro = "Por favor, digite a sua senha atual para confirmar as alterações.";
    } else {
        // Busca a senha criptografada guardada na tabela 'cliente' usando PDO
        $stmtPass = $pdo->prepare("SELECT password FROM cliente WHERE id = ? LIMIT 1");
        $stmtPass->execute([$id]);
        $clientePass = $stmtPass->fetch();
        $hash_senha = $clientePass['password'] ?? null;

        if ($DEBUG) {
            echo "<pre>";
            var_dump([
                'id_sessao'    => $id,
                'achou'        => (bool) $hash_senha,
                'hash_len'     => $hash_senha ? strlen($hash_senha) : null,
                'hash_prefixo' => $hash_senha ? substr($hash_senha, 0, 4) : null,
                'senha_len'    => strlen($senha_atual),
            ]);
            echo "</pre>";
        }

        if (!$hash_senha) {
            $erro = "Cliente não encontrado. Faça login novamente.";
        } elseif (!password_verify($senha_atual, $hash_senha)) {
            $erro = "Senha atual incorreta. As alterações não foram salvas.";
        } else {
            try {
                // Inicia transação com PDO
                $pdo->beginTransaction();

                // A. Atualiza ou Insere na tabela 'pessoais' (só telefone e endereco)
                $stmtCheckP = $pdo->prepare("SELECT COUNT(*) FROM pessoais WHERE id_cliente = ?");
                $stmtCheckP->execute([$id]);
                $existePes = $stmtCheckP->fetchColumn();

                if ($existePes > 0) {
                    $stmtPes = $pdo->prepare("UPDATE pessoais SET telefone = ?, endereco = ? WHERE id_cliente = ?");
                    $stmtPes->execute([$telefone, $endereco, $id]);
                } else {
                    $stmtPes = $pdo->prepare("INSERT INTO pessoais (id_cliente, telefone, endereco) VALUES (?, ?, ?)");
                    $stmtPes->execute([$id, $telefone, $endereco]);
                }

                // B. Atualiza ou Insere na tabela 'ficha_anamnese'
                $stmtCheckA = $pdo->prepare("SELECT COUNT(*) FROM ficha_anamnese WHERE id_cliente = ?");
                $stmtCheckA->execute([$id]);
                $existeAna = $stmtCheckA->fetchColumn();

                if ($existeAna > 0) {
                    $stmtAna = $pdo->prepare(
                        "UPDATE ficha_anamnese SET
                            diabetes = ?, gestante = ?, alergias = ?, especificar_alergia = ?,
                            cuticula = ?, onicomicose = ?, especificar_onico = ?,
                            medicamento = ?, qual_medicamento = ?,
                            lamina = ?, outro_lamina_texto = ?, encravada = ?, onicofagia = ?, esporte = ?, piscina = ?
                         WHERE id_cliente = ?"
                    );
                    $stmtAna->execute([
                        $diabetes, $gestante, $alergias, $especificar_alerg,
                        $cuticula, $onicomicose, $especificar_onico,
                        $medicamento, $qual_medicamento,
                        $lamina, $outro_lamina, $encravada, $onicofagia, $esporte, $piscina,
                        $id
                    ]);
                } else {
                    $stmtAna = $pdo->prepare(
                        "INSERT INTO ficha_anamnese
                            (id_cliente, diabetes, gestante, alergias, especificar_alergia,
                             cuticula, onicomicose, especificar_onico,
                             medicamento, qual_medicamento,
                             lamina, outro_lamina_texto, encravada, onicofagia, esporte, piscina)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                    );
                    $stmtAna->execute([
                        $id,
                        $diabetes, $gestante, $alergias, $especificar_alerg,
                        $cuticula, $onicomicose, $especificar_onico,
                        $medicamento, $qual_medicamento,
                        $lamina, $outro_lamina, $encravada, $onicofagia, $esporte, $piscina
                    ]);
                }

                $pdo->commit();
                $mensagem = "Dados e ficha de saúde atualizados com sucesso!";
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log("perfil.php: " . $e->getMessage());
                $erro = "Não foi possível salvar as alterações. Tente novamente.";
            }
        }
    }
}

// 3. Busca os dados unidos das três tabelas para preencher o formulário com PDO
$sql = "SELECT c.nome, c.email,
               p.telefone, p.cpf, p.endereco, p.data_nascimento, p.como_conheceu, p.data_cadastro,
               a.diabetes, a.gestante, a.alergias, a.especificar_alergia,
               a.cuticula, a.onicomicose, a.especificar_onico,
               a.medicamento, a.qual_medicamento,
               a.lamina, a.outro_lamina_texto AS outro_lamina, a.encravada, a.onicofagia, a.esporte, a.piscina
        FROM cliente c
        LEFT JOIN pessoais p ON c.id = p.id_cliente
        LEFT JOIN ficha_anamnese a ON c.id = a.id_cliente
        WHERE c.id = ? LIMIT 1";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$dados = $stmt->fetch() ?: [];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Conta | MT Nail Designer</title>
    <link rel="stylesheet" href="css/panal.css">
    <link rel="stylesheet" href="css/perfil.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&icon_names=account_circle" />
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
</head>
<body>

<div class="container">
    <div class="logo">
        <img src="imagem/Maria.png" alt="logo da empresa">
    </div>
    <div class="menu">
        <span class="material-symbols-outlined">account_circle</span>
        <h2>Minha Conta</h2>
    </div>
</div>

<div class="perfil-box">
    <h2>Meus Dados e Ficha de Saúde</h2>

    <?php if (!empty($dados['data_cadastro'])): ?>
        <p class="data-cadastro">Data de Cadastro: <?= htmlspecialchars(date('d/m/Y', strtotime($dados['data_cadastro'])), ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <?php if (!empty($mensagem)): ?>
        <p class="msg msg-sucesso"><?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <?php if (!empty($erro)): ?>
        <p class="msg msg-erro"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <form action="perfil.php" method="POST" autocomplete="off">
        <h3>Informações Pessoais</h3>
        <p class="aviso-campos">Nome, Email, CPF, Data de Nascimento e Como nos conheceu não podem ser alterados.</p>

        <div class="campo">
            <label>Nome:</label>
            <input type="text" class="bloqueado" value="<?= htmlspecialchars($dados['nome'] ?? '', ENT_QUOTES, 'UTF-8') ?>" readonly tabindex="-1">
        </div>

        <div class="campo">
            <label>Email:</label>
            <input type="email" class="bloqueado" value="<?= htmlspecialchars($dados['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" readonly tabindex="-1">
        </div>

        <div class="campo">
            <label>Telefone:</label>
            <input type="text" name="telefone" value="<?= htmlspecialchars($dados['telefone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="campo">
            <label>CPF:</label>
            <input type="text" class="bloqueado" value="<?= htmlspecialchars($dados['cpf'] ?? '', ENT_QUOTES, 'UTF-8') ?>" readonly tabindex="-1">
        </div>

        <div class="campo">
            <label>Data de Nascimento:</label>
            <input type="date" class="bloqueado" value="<?= htmlspecialchars($dados['data_nascimento'] ?? '', ENT_QUOTES, 'UTF-8') ?>" readonly tabindex="-1">
        </div>

        <div class="campo">
            <label>Endereço:</label>
            <input type="text" name="endereco" value="<?= htmlspecialchars($dados['endereco'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="campo">
            <label>Como nos conheceu:</label>
            <input type="text" class="bloqueado" value="<?= htmlspecialchars($dados['como_conheceu'] ?? '', ENT_QUOTES, 'UTF-8') ?>" readonly tabindex="-1">
        </div>

        <hr class="separador">

        <h3>Ficha de Anamnese (Saúde)</h3>

        <div class="campo">
            <label>Diabetes:</label>
            <select name="diabetes">
                <option value="Não" <?= sel($dados, 'diabetes', 'Não', 'Não') ?>>Não</option>
                <option value="Sim" <?= sel($dados, 'diabetes', 'Sim', 'Não') ?>>Sim</option>
            </select>
        </div>

        <div class="campo">
            <label>Gestante:</label>
            <select name="gestante">
                <option value="Não" <?= sel($dados, 'gestante', 'Não', 'Não') ?>>Não</option>
                <option value="Sim" <?= sel($dados, 'gestante', 'Sim', 'Não') ?>>Sim</option>
            </select>
        </div>

        <div class="campo">
            <label>Alergias:</label>
            <select name="alergias">
                <option value="Não" <?= sel($dados, 'alergias', 'Não', 'Não') ?>>Não</option>
                <option value="Sim" <?= sel($dados, 'alergias', 'Sim', 'Não') ?>>Sim</option>
            </select>
        </div>

        <div class="campo">
            <label>Especificar Alergia:</label>
            <input type="text" name="especificar_alergia" value="<?= htmlspecialchars($dados['especificar_alergia'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="campo">
            <label>Cuticula:</label>
            <select name="cuticula">
                <option value="Sim" <?= sel($dados, 'cuticula', 'Sim', 'Sim') ?>>Sim</option>
                <option value="Não" <?= sel($dados, 'cuticula', 'Não', 'Sim') ?>>Não</option>
            </select>
        </div>

        <div class="campo">
            <label>Onicomicose:</label>
            <select name="onicomicose">
                <option value="Não" <?= sel($dados, 'onicomicose', 'Não', 'Não') ?>>Não</option>
                <option value="Sim" <?= sel($dados, 'onicomicose', 'Sim', 'Não') ?>>Sim</option>
            </select>
        </div>

        <div class="campo">
            <label>Especificar Onicomicose:</label>
            <input type="text" name="especificar_onico" value="<?= htmlspecialchars($dados['especificar_onico'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="campo">
            <label>Medicamento:</label>
            <select name="medicamento">
                <option value="Não" <?= sel($dados, 'medicamento', 'Não', 'Não') ?>>Não</option>
                <option value="Sim" <?= sel($dados, 'medicamento', 'Sim', 'Não') ?>>Sim</option>
            </select>
        </div>

        <div class="campo">
            <label>Qual Medicamento:</label>
            <input type="text" name="qual_medicamento" value="<?= htmlspecialchars($dados['qual_medicamento'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="campo">
            <label>Lamina:</label>
            <input type="text" name="lamina" value="<?= htmlspecialchars($dados['lamina'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="campo">
            <label>Outro Lamina:</label>
            <input type="text" name="outro_lamina" value="<?= htmlspecialchars($dados['outro_lamina'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="campo">
            <label>Encravada:</label>
            <select name="encravada">
                <option value="Não" <?= sel($dados, 'encravada', 'Não', 'Não') ?>>Não</option>
                <option value="Sim" <?= sel($dados, 'encravada', 'Sim', 'Não') ?>>Sim</option>
            </select>
        </div>

        <div class="campo">
            <label>Onicofagia:</label>
            <select name="onicofagia">
                <option value="Não" <?= sel($dados, 'onicofagia', 'Não', 'Não') ?>>Não</option>
                <option value="Sim" <?= sel($dados, 'onicofagia', 'Sim', 'Não') ?>>Sim</option>
            </select>
        </div>

        <div class="campo">
            <label>Esporte:</label>
            <select name="esporte">
                <option value="Não" <?= sel($dados, 'esporte', 'Não', 'Não') ?>>Não</option>
                <option value="Sim" <?= sel($dados, 'esporte', 'Sim', 'Não') ?>>Sim</option>
            </select>
        </div>

        <div class="campo">
            <label>Piscina:</label>
            <select name="piscina">
                <option value="Não" <?= sel($dados, 'piscina', 'Não', 'Não') ?>>Não</option>
                <option value="Sim" <?= sel($dados, 'piscina', 'Sim', 'Não') ?>>Sim</option>
            </select>
        </div>

        <hr class="separador">

        <!-- Confirmação de segurança (com botão mostrar/ocultar senha) -->
        <div class="confirmar-senha">
            <label for="senha_atual">Confirme a sua Senha Atual para Salvar:</label>
            <div class="senha-wrapper">
                <input type="password" id="senha_atual" name="senha_atual" required autocomplete="new-password" placeholder="Digite a sua senha">
                <button type="button" class="btn-olho" onclick="toggleSenha()" title="Mostrar/Ocultar Senha">👁️</button>
            </div>
        </div>

        <button type="submit" class="btn-salvar">Salvar Alterações</button>
        <a href="panel.php" class="link-voltar">Voltar ao Painel</a>
    </form>
</div>

<script>
(function () {
    const campo = document.getElementById('senha_atual');
    let digitou = false;
    campo.addEventListener('input', function () { digitou = true; });
    function limpar() { if (!digitou) campo.value = ''; }
    window.addEventListener('load', function () {
        limpar();
        setTimeout(limpar, 100);
        setTimeout(limpar, 500);
    });
})();

function toggleSenha() {
    const inputSenha = document.getElementById('senha_atual');
    inputSenha.type = inputSenha.type === 'password' ? 'text' : 'password';
}
</script>
</body>
</html>