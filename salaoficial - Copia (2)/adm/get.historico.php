 <?php
date_default_timezone_set('America/Sao_Paulo');

try {
    $pdo = new PDO("mysql:host=localhost;dbname=salao;charset=utf8", "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo "Erro ao conectar ao banco.";
    exit;
}

$id_cliente = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {

    if ($id_cliente > 0) {
        // =========================================================
        // MODO DETALHES (lado direito): todos os atendimentos da cliente
        // (a lista da esquerda, com busca, está em buscar_agendamentos.php)
        // =========================================================
        $sql = $pdo->prepare(
            "SELECT a.id, a.servico, a.data_agendamento, a.hora_agendamento, a.status, c.nome,
                    (SELECT COALESCE(SUM(p.valor_pago), 0)
                       FROM pagamentos p
                      WHERE p.id_agendamento = a.id) AS pago
               FROM agendamentos a
         INNER JOIN cliente c ON a.id_cliente = c.id
              WHERE a.id_cliente = ?
           ORDER BY a.data_agendamento DESC, a.hora_agendamento DESC"
        );
        $sql->execute([$id_cliente]);
        $historicos = $sql->fetchAll();

        if (!$historicos) {
            echo "Nenhum histórico encontrado.";
            exit;
        }

        echo "<h4 style='margin-bottom: 12px;'>" . htmlspecialchars($historicos[0]['nome']) . "</h4>";

        foreach ($historicos as $item) {
            $status = strtolower(trim($item['status'] ?? ''));
            $quando = strtotime($item['data_agendamento'] . ' ' . $item['hora_agendamento']);
            $passou = $quando < time();

            if ($status === 'cancelado') {
                $label = 'Cancelado';
                $estilo = 'color: #c53030; font-weight: bold;';
            } elseif (in_array($status, ['realizado', 'finalizado'], true)) {
                $label = 'Realizado';
                $estilo = 'color: #777;';
            } elseif ($passou) {
                $label = 'Aguardando finalização';
                $estilo = 'color: #d69e2e; font-weight: bold;';
            } else {
                $label = 'Agendado';
                $estilo = 'color: #2ecc71; font-weight: bold;';
            }

            echo "<div style='margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px;'>";
            echo "<strong>Data:</strong> " . date('d/m/Y', $quando) . " às " . date('H:i', $quando) . "<br>";
            echo "<strong>Serviço:</strong> " . htmlspecialchars($item['servico']) . "<br>";
            if ((float)$item['pago'] > 0) {
                echo "<strong>Valor pago:</strong> R$ " . number_format((float)$item['pago'], 2, ',', '.') . "<br>";
            }
            echo "<strong>Situação:</strong> <span style='$estilo'>$label</span>";
            echo "</div>";
        }

    } else {
        echo "Selecione uma cliente.";
    }

} catch (PDOException $e) {
    error_log("get.historico.php: " . $e->getMessage());
    http_response_code(500);
    echo "Erro ao carregar o histórico.";
}
?>