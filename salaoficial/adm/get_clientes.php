 <?php
// get_clientes.php
$host = "localhost";
$db = "salao";
$user = "root";
$pass = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
} catch (PDOException $e) {
    exit();
}

// Busca os clientes ordenados pelo mais novo primeiro
$sql = $pdo->query("SELECT c.id, c.nome, c.email, p.endereco, p.telefone, p.cpf, p.data_nascimento, p.como_conheceu, p.data_cadastro, f.diabetes, f.gestante, f.alergias, f.especificar_alergia, f.cuticula, f.onicomicose, f.especificar_onico, f.medicamento, f.qual_medicamento, f.lamina, f.outro_lamina_texto, f.encravada, f.onicofagia, f.esporte, f.piscina, f.data_cadastro AS data_ficha 
FROM cliente c
LEFT JOIN pessoais p ON c.id = p.id_cliente 
LEFT JOIN ficha_anamnese f ON c.id = f.id_cliente
ORDER BY c.id DESC");

$clientes = $sql->fetchAll(PDO::FETCH_ASSOC);

// Gera apenas o HTML das linhas
foreach ($clientes as $user): ?>
    <tr>
        <td><?= htmlspecialchars($user['nome']) ?></td>
        <td><?= htmlspecialchars($user['email']) ?></td>
        <td class="ver-mais">
            <button type="button" onclick="verMais(<?= $user['id'] ?>)" class="ver-mais">
                Ver mais
            </button>
        </td>
    </tr>
    <tr id="detalhes-<?= $user['id'] ?>" style="display:none;">
        <td colspan="3">
            <strong>Telefone:</strong> <?= htmlspecialchars($user['telefone'] ?? '') ?><br>
            <strong>Endereço:</strong> <?= htmlspecialchars($user['endereco'] ?? '') ?><br>
            <strong>CPF:</strong> <?= htmlspecialchars($user['cpf'] ?? '') ?><br>
            <strong>Data de Nascimento:</strong> <?= htmlspecialchars($user['data_nascimento'] ?? '') ?><br>
            <strong>Como nos conheceu:</strong> <?= htmlspecialchars($user['como_conheceu'] ?? '') ?><br>
            <strong>Data de Cadastro:</strong> <?= htmlspecialchars($user['data_cadastro'] ?? '') ?><br>
            <strong>Diabetes:</strong> <?= htmlspecialchars($user['diabetes'] ?? '') ?><br>
            <strong>Gestante:</strong> <?= htmlspecialchars($user['gestante'] ?? '') ?><br>
            <strong>Alergias:</strong> <?= htmlspecialchars($user['alergias'] ?? '') ?><br>
            <strong>Especificar Alergia:</strong> <?= htmlspecialchars($user['especificar_alergia'] ?? '') ?><br>
            <strong>Cuticula:</strong> <?= htmlspecialchars($user['cuticula'] ?? '') ?><br>
            <strong>Onicomicose:</strong> <?= htmlspecialchars($user['onicomicose'] ?? '') ?><br>
            <strong>Especificar Onicomicose:</strong> <?= htmlspecialchars($user['especificar_onico'] ?? '') ?><br>
            <strong>Medicamento:</strong> <?= htmlspecialchars($user['medicamento'] ?? '') ?><br>
            <strong>Qual Medicamento:</strong> <?= htmlspecialchars($user['qual_medicamento'] ?? '') ?><br>
            <strong>Lamina:</strong> <?= htmlspecialchars($user['lamina'] ?? '') ?><br>
            <strong>Outro Lamina:</strong> <?= htmlspecialchars($user['outro_lamina_texto'] ?? '') ?><br>
            <strong>Encravada:</strong> <?= htmlspecialchars($user['encravada'] ?? '') ?><br>
            <strong>Onicofagia:</strong> <?= htmlspecialchars($user['onicofagia'] ?? '') ?><br>
            <strong>Esporte:</strong> <?= htmlspecialchars($user['esporte'] ?? '') ?><br>
            <strong>Piscina:</strong> <?= htmlspecialchars($user['piscina'] ?? '') ?><br>
            <button onclick="verHistorico(<?= $user['id'] ?>)" class="ver-mais">Ver historico</button>
        </td>
    </tr>
<?php endforeach; ?>