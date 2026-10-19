 <?php
// Lista do Histórico (lado esquerdo): UMA linha por cliente, com busca por nome.
// Uso: buscar_agendamentos.php?q=teresinha   (q vazio = todas)
date_default_timezone_set('America/Sao_Paulo');

try {
    $pdo = new PDO("mysql:host=localhost;dbname=salao;charset=utf8", "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo '<tr><td colspan="4">Erro ao conectar ao banco.</td></tr>';
    exit;
}

$q = trim($_GET['q'] ?? '');
$q = mb_substr($q, 0, 100);

try {
    $sql = "SELECT c.id AS id_cliente,
                   c.nome,
                   COUNT(*) AS total,
                   MAX(CONCAT(a.data_agendamento, ' ', a.hora_agendamento)) AS ultimo,
                   SUM(CASE WHEN a.status IN ('confirmado', 'pendente')
                             AND CONCAT(a.data_agendamento, ' ', a.hora_agendamento) >= :agora
                            THEN 1 ELSE 0 END) AS futuros
              FROM agendamentos a
        INNER JOIN cliente c ON a.id_cliente = c.id";

    $params = [':agora' => date('Y-m-d H:i:s')];

    if ($q !== '') {
        $sql .= " WHERE c.nome LIKE :q";
        // escapa % e _ para o usuário não usar como curinga
        $params[':q'] = '%' . addcslashes($q, '%_\\') . '%';
    }

    $sql .= " GROUP BY c.id, c.nome
              ORDER BY ultimo DESC
              LIMIT 100";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $lista = $stmt->fetchAll();

    if (!$lista) {
        $msg = $q !== ''
            ? 'Nenhuma cliente encontrada para "' . htmlspecialchars($q) . '".'
            : 'Nenhum histórico encontrado.';
        echo '<tr><td colspan="4" style="text-align:center;">' . $msg . '</td></tr>';
        exit;
    }

    foreach ($lista as $item):
        $ultimo = strtotime($item['ultimo']);
        $temFuturo = (int)$item['futuros'] > 0;
?>
        <tr style="<?= $temFuturo ? 'border-left: 4px solid #2ecc71;' : 'opacity: 0.85;' ?>">
            <td><?= htmlspecialchars($item['nome']) ?></td>
            <td><?= (int)$item['total'] ?></td>
            <td>
                <?= date('d/m/Y', $ultimo) ?><br>
                <small><?= date('H:i', $ultimo) ?></small>
            </td>
            <td>
                <button class="ver-detalhes-btn" onclick="verhistorico(<?= (int)$item['id_cliente'] ?>)">
                    Ver detalhes
                </button>
            </td>
        </tr>
<?php
    endforeach;

} catch (PDOException $e) {
    error_log("buscar_agendamentos.php: " . $e->getMessage());
    http_response_code(500);
    echo '<tr><td colspan="4">Erro ao carregar o histórico.</td></tr>';
}