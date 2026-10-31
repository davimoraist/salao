 <?php
// Funções de bloqueio de agenda (tabela bloqueios_agenda), usando PDO ($pdo).[cite: 14]

/**
 * Verifica se uma data (AAAA-MM-DD) está dentro de algum período bloqueado.[cite: 14]
 * Retorna true (bloqueada), false (livre) ou null (erro ao consultar).[cite: 14]
 */
function dataBloqueada(PDO $pdo, string $data): ?bool {
    try {
        $stmt = $pdo->prepare(
            "SELECT 1 FROM bloqueios_agenda WHERE ? BETWEEN data_inicio AND data_fim LIMIT 1"
        );
        $stmt->execute([$data]);
        return (bool) $stmt->fetchColumn();
    } catch (\PDOException $e) {
        error_log("dataBloqueada: " . $e->getMessage());
        return null;
    }
}

/**
 * Períodos bloqueados de hoje em diante, para a página das clientes.[cite: 14]
 * - corta o começo em "hoje" (não mostra dia que já passou);[cite: 14]
 * - junta períodos que se sobrepõem ou ficam colados (18-21 + 19-20 = 18-21);[cite: 14]
 * - devolve só as datas: o motivo é privado e NUNCA vai para a cliente.[cite: 14]
 */
function listarBloqueiosFuturos(PDO $pdo): array {
    $lista = [];
    try {
        $stmt = $pdo->query(
            "SELECT GREATEST(data_inicio, CURDATE()) AS inicio, data_fim AS fim
               FROM bloqueios_agenda
              WHERE data_fim >= CURDATE()
           ORDER BY inicio, fim"
        );
        $res = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($res as $row) {
            $n = count($lista) - 1;
            // começa até 1 dia depois do fim do período anterior? então é o mesmo bloco[cite: 14]
            if ($n >= 0 && strtotime($row['inicio']) <= strtotime($lista[$n]['fim'] . ' +1 day')) {
                if ($row['fim'] > $lista[$n]['fim']) {
                    $lista[$n]['fim'] = $row['fim'];
                }
            } else {
                $lista[] = ['inicio' => $row['inicio'], 'fim' => $row['fim']];
            }
        }
    } catch (\PDOException $e) {
        error_log("listarBloqueiosFuturos: " . $e->getMessage());
        return [];
    }
    return $lista;
}
?>