 <?php
$pdo = new PDO("mysql:host=localhost;dbname=salao;charset=utf8", "root", "");

$id_cliente = isset($_GET['id']) ? (int)$_GET['id'] : null;

if ($id_cliente) {
    // =========================================================
    // MODO DETALHES (Lado Direito)
    // =========================================================
    $sql = $pdo->prepare("SELECT h.*, c.nome 
                          FROM agendamentos h 
                          INNER JOIN cliente c ON h.id_cliente = c.id 
                          WHERE h.id_cliente = ? 
                          ORDER BY h.data_agendamento DESC, h.hora_agendamento DESC");
    $sql->execute([$id_cliente]);
    $historicos = $sql->fetchAll(PDO::FETCH_ASSOC);

    if ($historicos) {
        foreach ($historicos as $item) {
            // Lógica simples para diferenciar "Já fez" de "Vai fazer"
            $data_agendada = $item['data_agendamento'] . ' ' . $item['hora_agendamento'];
            $estilo_status = (strtotime($data_agendada) < time()) ? "color: #777;" : "color: #2ecc71; font-weight: bold;";
            $label = (strtotime($data_agendada) < time()) ? "Realizado" : "Agendado";

            echo "<div style='margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px; $estilo_status'>";
            echo "<strong>Data:</strong> " . date('d/m/Y', strtotime($item['data_agendamento'])) . " às " . date('H:i', strtotime($item['hora_agendamento'])) . "<br>";
            echo "<strong>Serviço:</strong> " . htmlspecialchars($item['servico']) . "<br>";
            echo "<strong>Situação:</strong> " . $label;
            echo "</div>";
        }
    } else {
        echo "Nenhum histórico encontrado.";
    }

} else {
    // =========================================================
    // MODO LISTA (Lado Esquerdo) - Mostra TUDO (Passado e Futuro)
    // =========================================================
    // REMOVI O "GROUP BY" para listar cada agendamento individualmente
    $sql = $pdo->query("SELECT h.id, h.id_cliente, c.nome, h.servico, h.data_agendamento, h.hora_agendamento 
                        FROM agendamentos h 
                        INNER JOIN cliente c ON h.id_cliente = c.id 
                        ORDER BY h.data_agendamento DESC, h.hora_agendamento DESC");
    
    $lista = $sql->fetchAll(PDO::FETCH_ASSOC);

    foreach ($lista as $item): 
        $data_agendada = $item['data_agendamento'] . ' ' . $item['hora_agendamento'];
        $passou = strtotime($data_agendada) < time();
    ?>
        <tr style="<?= $passou ? 'opacity: 0.7;' : 'border-left: 4px solid #2ecc71;' ?>">
            <td><?= htmlspecialchars($item['nome']) ?></td>
            <td><?= htmlspecialchars($item['servico']) ?></td>
            <td>
                <?= date('d/m/Y', strtotime($item['data_agendamento'])) ?><br>
                <small><?= date('H:i', strtotime($item['hora_agendamento'])) ?></small>
            </td>
            <td>
                <button class="ver-detalhes-btn" onclick="verhistorico(<?= $item['id_cliente'] ?>)">
                    Ver detalhes
                </button>
            </td>
        </tr>
    <?php endforeach;
}
?>