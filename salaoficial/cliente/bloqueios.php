<?php
// Funções de bloqueio de agenda (tabela bloqueios_agenda), usando mysqli ($conn).

/**
 * Verifica se uma data (AAAA-MM-DD) está dentro de algum período bloqueado.
 * Retorna true (bloqueada), false (livre) ou null (erro ao consultar).
 */
function dataBloqueada(mysqli $conn, string $data): ?bool {
    $stmt = $conn->prepare(
        "SELECT 1 FROM bloqueios_agenda WHERE ? BETWEEN data_inicio AND data_fim LIMIT 1"
    );
    if (!$stmt) {
        error_log("dataBloqueada prepare: " . $conn->error);
        return null;
    }
    $stmt->bind_param("s", $data);
    if (!$stmt->execute()) {
        error_log("dataBloqueada execute: " . $stmt->error);
        $stmt->close();
        return null;
    }
    $stmt->store_result();
    $bloqueada = $stmt->num_rows > 0;
    $stmt->close();
    return $bloqueada;
}

/**
 * Períodos bloqueados de hoje em diante, para a página das clientes.
 * - corta o começo em "hoje" (não mostra dia que já passou);
 * - junta períodos que se sobrepõem ou ficam colados (18-21 + 19-20 = 18-21);
 * - devolve só as datas: o motivo é privado e NUNCA vai para a cliente.
 */
function listarBloqueiosFuturos(mysqli $conn): array {
    $lista = [];
    $res = $conn->query(
        "SELECT GREATEST(data_inicio, CURDATE()) AS inicio, data_fim AS fim
           FROM bloqueios_agenda
          WHERE data_fim >= CURDATE()
       ORDER BY inicio, fim"
    );
    if (!$res) {
        error_log("listarBloqueiosFuturos: " . $conn->error);
        return [];
    }
    while ($row = $res->fetch_assoc()) {
        $n = count($lista) - 1;
        // começa até 1 dia depois do fim do período anterior? então é o mesmo bloco
        if ($n >= 0 && strtotime($row['inicio']) <= strtotime($lista[$n]['fim'] . ' +1 day')) {
            if ($row['fim'] > $lista[$n]['fim']) {
                $lista[$n]['fim'] = $row['fim'];
            }
        } else {
            $lista[] = ['inicio' => $row['inicio'], 'fim' => $row['fim']];
        }
    }
    return $lista;
}