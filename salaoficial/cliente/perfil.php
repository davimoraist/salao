 <?php
session_start();

// Carrega o arquivo de conexão
require_once __DIR__ . "/conecte.php";

$db = $conn ?? $conexao ?? $mysqli ?? null;

if (!$db || !($db instanceof mysqli)) {
    die("Erro crítico: A conexão MySQLi não foi encontrada. Verifique o arquivo de conexão.");
}

// Faz o mysqli lançar exceções em caso de erro de SQL (necessário para o rollback funcionar)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

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
    // IMPORTANTE: a senha NÃO leva trim() nem htmlspecialchars()
    $senha_atual       = $_POST['senha_atual'] ?? '';

    // Dados pessoais editáveis (nome, email, cpf, data de nascimento e
    // "como nos conheceu" NÃO são lidos do formulário: não podem ser alterados)
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
        // Busca a senha criptografada guardada na tabela 'cliente'
        $stmtPass = $db->prepare("SELECT password FROM cliente WHERE id = ? LIMIT 1");
        $stmtPass->bind_param("i", $id);
        $stmtPass->execute();
        $stmtPass->bind_result($hash_senha);
        $achou = $stmtPass->fetch();
        $stmtPass->close();

        if ($DEBUG) {
            echo "<pre>";
            var_dump([
                'id_sessao'    => $id,
                'achou'        => (bool) $achou,
                'hash_len'     => $hash_senha ? strlen($hash_senha) : null,
                'hash_prefixo' => $hash_senha ? substr($hash_senha, 0, 4) : null,
                'senha_len'    => strlen($senha_atual),
            ]);
            echo "</pre>";
        }

        if (!$achou) {
            $erro = "Cliente não encontrado. Faça login novamente.";
        } elseif (!password_verify($senha_atual, $hash_senha)) {
            $erro = "Senha atual incorreta. As alterações não foram salvas.";
        } else {
            try {
                // As duas tabelas são atualizadas juntas, ou nenhuma é
                $db->begin_transaction();

                // A. Atualiza ou Insere na tabela 'pessoais' (só telefone e endereço)
                $stmtCheckP = $db->prepare("SELECT COUNT(*) FROM pessoais WHERE id_cliente = ?");
                $stmtCheckP->bind_param("i", $id);
                $stmtCheckP->execute();
                $stmtCheckP->bind_result($existePes);
                $stmtCheckP->fetch();
                $stmtCheckP->close();

                if ($existePes > 0) {
                    $stmtPes = $db->prepare("UPDATE pessoais SET telefone = ?, endereco = ? WHERE id_cliente = ?");
                    $stmtPes->bind_param("ssi", $telefone, $endereco, $id);
                } else {
                    $stmtPes = $db->prepare("INSERT INTO pessoais (id_cliente, telefone, endereco) VALUES (?, ?, ?)");
                    $stmtPes->bind_param("iss", $id, $telefone, $endereco);
                }
                $stmtPes->execute();
                $stmtPes->close();

                // B. Atualiza ou Insere na tabela 'ficha_anamnese'
                $stmtCheckA = $db->prepare("SELECT COUNT(*) FROM ficha_anamnese WHERE id_cliente = ?");
                $stmtCheckA->bind_param("i", $id);
                $stmtCheckA->execute();
                $stmtCheckA->bind_result($existeAna);
                $stmtCheckA->fetch();
                $stmtCheckA->close();

                if ($existeAna > 0) {
                    $stmtAna = $db->prepare(
                        "UPDATE ficha_anamnese SET
                            diabetes = ?, gestante = ?, alergias = ?, especificar_alergia = ?,
                            cuticula = ?, onicomicose = ?, especificar_onico = ?,
                            medicamento = ?, qual_medicamento = ?,
                            lamina = ?, outro_lamina_texto = ?, encravada = ?, onicofagia = ?, esporte = ?, piscina = ?
                         WHERE id_cliente = ?"
                    );
                    $stmtAna->bind_param(
                        "sssssssssssssssi",
                        $diabetes, $gestante, $alergias, $especificar_alerg,
                        $cuticula, $onicomicose, $especificar_onico,
                        $medicamento, $qual_medicamento,
                        $lamina, $outro_lamina, $encravada, $onicofagia, $esporte, $piscina,
                        $id
                    );
                } else {
                    $stmtAna = $db->prepare(
                        "INSERT INTO ficha_anamnese
                            (id_cliente, diabetes, gestante, alergias, especificar_alergia,
                             cuticula, onicomicose, especificar_onico,
                             medicamento, qual_medicamento,
                             lamina, outro_lamina_texto, encravada, onicofagia, esporte, piscina)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                    );
                    $stmtAna->bind_param(
                        "isssssssssssssss",
                        $id,
                        $diabetes, $gestante, $alergias, $especificar_alerg,
                        $cuticula, $onicomicose, $especificar_onico,
                        $medicamento, $qual_medicamento,
                        $lamina, $outro_lamina, $encravada, $onicofagia, $esporte, $piscina
                    );
                }
                $stmtAna->execute();
                $stmtAna->close();

                $db->commit();

                $mensagem = "Dados e ficha de saúde atualizados com sucesso!";
            } catch (Throwable $e) {
                $db->rollback();
                error_log("perfil.php: " . $e->getMessage());
                $erro = "Não foi possível salvar as alterações. Tente novamente.";
            }
        }
    }
}

// 3. Busca os dados unidos das três tabelas para preencher o formulário
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

$stmt = $db->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$resultado = $stmt->get_result();
$dados = $resultado->fetch_assoc() ?? [];
$stmt->close();

// Estilo dos campos que não podem ser editados
$bloqueado = 'width: 100%; padding: 8px; box-sizing: border-box; background: #f1f1f1; color: #555; cursor: not-allowed;';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Conta | MT Nail Designer</title>
    <link rel="stylesheet" href="css/panal.css">
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

<div style="max-width: 650px; margin: 40px auto; padding: 25px; background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <h2>Meus Dados e Ficha de Saúde</h2>

    <?php if (!empty($dados['data_cadastro'])): ?>
        <p style="color: #666; margin-top: 0;">Data de Cadastro: <?= htmlspecialchars(date('d/m/Y', strtotime($dados['data_cadastro']))) ?></p>
    <?php endif; ?>

    <?php if (!empty($mensagem)): ?>
        <p style="padding: 10px; background: #d4edda; color: #155724; border-radius: 4px; margin-bottom: 15px;">
            <?= htmlspecialchars($mensagem) ?>
        </p>
    <?php endif; ?>

    <?php if (!empty($erro)): ?>
        <p style="padding: 10px; background: #f8d7da; color: #721c24; border-radius: 4px; margin-bottom: 15px;">
            <?= htmlspecialchars($erro) ?>
        </p>
    <?php endif; ?>

    <form action="perfil.php" method="POST" autocomplete="off">
        <h3>Informações Pessoais</h3>
        <p style="color: #888; font-size: 13px; margin-top: 0;">Nome, Email, CPF, Data de Nascimento e Como nos conheceu não podem ser alterados.</p>

        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Nome:</label>
            <input type="text" value="<?= htmlspecialchars($dados['nome'] ?? '') ?>" readonly tabindex="-1" style="<?= $bloqueado ?>">
        </div>

        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Email:</label>
            <input type="email" value="<?= htmlspecialchars($dados['email'] ?? '') ?>" readonly tabindex="-1" style="<?= $bloqueado ?>">
        </div>

        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Telefone:</label>
            <input type="text" name="telefone" value="<?= htmlspecialchars($dados['telefone'] ?? '') ?>" style="width: 100%; padding: 8px; box-sizing: border-box;">
        </div>

        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">CPF:</label>
            <input type="text" value="<?= htmlspecialchars($dados['cpf'] ?? '') ?>" readonly tabindex="-1" style="<?= $bloqueado ?>">
        </div>

        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Data de Nascimento:</label>
            <input type="date" value="<?= htmlspecialchars($dados['data_nascimento'] ?? '') ?>" readonly tabindex="-1" style="<?= $bloqueado ?>">
        </div>

        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Endereço:</label>
            <input type="text" name="endereco" value="<?= htmlspecialchars($dados['endereco'] ?? '') ?>" style="width: 100%; padding: 8px; box-sizing: border-box;">
        </div>

        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Como nos conheceu:</label>
            <input type="text" value="<?= htmlspecialchars($dados['como_conheceu'] ?? '') ?>" readonly tabindex="-1" style="<?= $bloqueado ?>">
        </div>

        <hr style="margin: 25px 0; border: 0; border-top: 1px solid #ddd;">

        <h3>Ficha de Anamnese (Saúde)</h3>

        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Diabetes:</label>
            <select name="diabetes" style="width: 100%; padding: 8px; box-sizing: border-box;">
                <option value="Não" <?= sel($dados, 'diabetes', 'Não', 'Não') ?>>Não</option>
                <option value="Sim" <?= sel($dados, 'diabetes', 'Sim', 'Não') ?>>Sim</option>
            </select>
        </div>

        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Gestante:</label>
            <select name="gestante" style="width: 100%; padding: 8px; box-sizing: border-box;">
                <option value="Não" <?= sel($dados, 'gestante', 'Não', 'Não') ?>>Não</option>
                <option value="Sim" <?= sel($dados, 'gestante', 'Sim', 'Não') ?>>Sim</option>
            </select>
        </div>

        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Alergias:</label>
            <select name="alergias" style="width: 100%; padding: 8px; box-sizing: border-box;">
                <option value="Não" <?= sel($dados, 'alergias', 'Não', 'Não') ?>>Não</option>
                <option value="Sim" <?= sel($dados, 'alergias', 'Sim', 'Não') ?>>Sim</option>
            </select>
        </div>

        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Especificar Alergia:</label>
            <input type="text" name="especificar_alergia" value="<?= htmlspecialchars($dados['especificar_alergia'] ?? '') ?>" style="width: 100%; padding: 8px; box-sizing: border-box;">
        </div>

        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Cuticula:</label>
            <select name="cuticula" style="width: 100%; padding: 8px; box-sizing: border-box;">
                <option value="Sim" <?= sel($dados, 'cuticula', 'Sim', 'Sim') ?>>Sim</option>
                <option value="Não" <?= sel($dados, 'cuticula', 'Não', 'Sim') ?>>Não</option>
            </select>
        </div>

        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Onicomicose:</label>
            <select name="onicomicose" style="width: 100%; padding: 8px; box-sizing: border-box;">
                <option value="Não" <?= sel($dados, 'onicomicose', 'Não', 'Não') ?>>Não</option>
                <option value="Sim" <?= sel($dados, 'onicomicose', 'Sim', 'Não') ?>>Sim</option>
            </select>
        </div>

        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Especificar Onicomicose:</label>
            <input type="text" name="especificar_onico" value="<?= htmlspecialchars($dados['especificar_onico'] ?? '') ?>" style="width: 100%; padding: 8px; box-sizing: border-box;">
        </div>

        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Medicamento:</label>
            <select name="medicamento" style="width: 100%; padding: 8px; box-sizing: border-box;">
                <option value="Não" <?= sel($dados, 'medicamento', 'Não', 'Não') ?>>Não</option>
                <option value="Sim" <?= sel($dados, 'medicamento', 'Sim', 'Não') ?>>Sim</option>
            </select>
        </div>

        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Qual Medicamento:</label>
            <input type="text" name="qual_medicamento" value="<?= htmlspecialchars($dados['qual_medicamento'] ?? '') ?>" style="width: 100%; padding: 8px; box-sizing: border-box;">
        </div>

        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Lamina:</label>
            <input type="text" name="lamina" value="<?= htmlspecialchars($dados['lamina'] ?? '') ?>" style="width: 100%; padding: 8px; box-sizing: border-box;">
        </div>

        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Outro Lamina:</label>
            <input type="text" name="outro_lamina" value="<?= htmlspecialchars($dados['outro_lamina'] ?? '') ?>" style="width: 100%; padding: 8px; box-sizing: border-box;">
        </div>

        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Encravada:</label>
            <select name="encravada" style="width: 100%; padding: 8px; box-sizing: border-box;">
                <option value="Não" <?= sel($dados, 'encravada', 'Não', 'Não') ?>>Não</option>
                <option value="Sim" <?= sel($dados, 'encravada', 'Sim', 'Não') ?>>Sim</option>
            </select>
        </div>

        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Onicofagia:</label>
            <select name="onicofagia" style="width: 100%; padding: 8px; box-sizing: border-box;">
                <option value="Não" <?= sel($dados, 'onicofagia', 'Não', 'Não') ?>>Não</option>
                <option value="Sim" <?= sel($dados, 'onicofagia', 'Sim', 'Não') ?>>Sim</option>
            </select>
        </div>

        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Esporte:</label>
            <select name="esporte" style="width: 100%; padding: 8px; box-sizing: border-box;">
                <option value="Não" <?= sel($dados, 'esporte', 'Não', 'Não') ?>>Não</option>
                <option value="Sim" <?= sel($dados, 'esporte', 'Sim', 'Não') ?>>Sim</option>
            </select>
        </div>

        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Piscina:</label>
            <select name="piscina" style="width: 100%; padding: 8px; box-sizing: border-box;">
                <option value="Não" <?= sel($dados, 'piscina', 'Não', 'Não') ?>>Não</option>
                <option value="Sim" <?= sel($dados, 'piscina', 'Sim', 'Não') ?>>Sim</option>
            </select>
        </div>

        <hr style="margin: 25px 0; border: 0; border-top: 1px solid #ddd;">

        <!-- Confirmação de Segurança -->
        <div style="margin-bottom: 20px; background: #fff3cd; padding: 15px; border-radius: 6px; border: 1px solid #ffeeba;">
            <label for="senha_atual" style="display: block; margin-bottom: 5px; font-weight: bold; color: #856404;">Confirme a sua Senha Atual para Salvar:</label>
            <input type="password" id="senha_atual" name="senha_atual" required autocomplete="current-password" placeholder="Digite a sua senha" style="width: 100%; padding: 8px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px;">
        </div>

        <button type="submit" style="padding: 10px 20px; background: #d81b60; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">Salvar Alterações</button>
        <a href="painel.php" style="margin-left: 15px; color: #666; text-decoration: none;">Voltar ao Painel</a>
    </form>
</div>

</body>
</html>