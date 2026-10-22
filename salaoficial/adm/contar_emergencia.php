<?php
// Prévia da emergência (só consulta, não altera nada): lista os agendamentos
// que seriam cancelados no período, com valor já pago e telefone da cliente.
// Uso: contar_emergencia.php?inicio=2026-10-20&fim=2026-10-22
session_start();
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['idusuario'])) {
    http_response_code(401);
    echo json_encode(['erro' => 'Não autorizado']);
    exit;
}

$inicio = $_GET['inicio'] ?? '';
$fim    = $_GET['fim'] ?? '';
$d1 = DateTime::createFromFormat('Y-m-d', $inicio);
$d2 = DateTime::createFromFormat('Y-m-d', $fim);

if (!$d1 || !$d2 || $d1->format('Y-m-d') !== $inicio || $d2->format('Y-m-d') !== $fim || $fim < $inicio) {
    http_response_code(400);
    echo json_encode(['erro' => 'Datas inválidas']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=salao;charset=utf8", "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

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
    $stmt->execute([':inicio' => $inicio, ':fim' => $fim]);
    $linhas = $stmt->fetchAll();

    $lista = [];
    $pagos = 0;
    $valor = 0.0;
    foreach ($linhas as $l) {
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
    ], JSON_INVALID_UTF8_SUBSTITUTE); // não quebra se algum texto vier com acento mal gravado

} catch (PDOException $e) {
    error_log("contar_emergencia.php: " . $e->getMessage());
    http_response_code(500);
    // "detalhe" ajuda a achar o problema; só aparece para quem está logado. Pode remover depois.
    echo json_encode(['erro' => 'Erro ao consultar', 'detalhe' => $e->getMessage()]);
     }
     ?>