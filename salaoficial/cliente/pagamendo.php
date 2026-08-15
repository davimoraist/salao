 <?php

session_start();

require_once __DIR__ . "/conecte.php";

/*
|--------------------------------------------------------------------------
| Verifica login
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['id']) || !is_numeric($_SESSION['id'])) {
    die("Você precisa estar logado.");
}

$id_cliente = (int) $_SESSION['id'];

/*
|--------------------------------------------------------------------------
| Verifica agendamento temporário
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['agendamento_temporario']) ||
    !is_array($_SESSION['agendamento_temporario'])
) {
    die("Nenhum agendamento encontrado.");
}

$agendamento = $_SESSION['agendamento_temporario'];

/*
|--------------------------------------------------------------------------
| Verifica dados necessários
|--------------------------------------------------------------------------
*/

if (
    empty($agendamento['servico']) ||
    empty($agendamento['data_agendamento']) ||
    empty($agendamento['hora_agendamento'])
) {
    die("Dados do agendamento incompletos.");
}

/*
|--------------------------------------------------------------------------
| Serviços
|--------------------------------------------------------------------------
*/

$servicos = array_filter(
    array_map(
        'trim',
        explode(',', $agendamento['servico'])
    )
);

if (empty($servicos)) {
    die("Nenhum serviço selecionado.");
}

/*
|--------------------------------------------------------------------------
| Busca preços diretamente no banco
|--------------------------------------------------------------------------
|
| O valor enviado pela sessão NÃO é usado para calcular o pagamento.
| O banco de dados é a fonte oficial dos preços.
|
*/

$precos_servicos = [];
$preco_total = 0;

$stmt = $conn->prepare("
    SELECT nome, preco
    FROM servico
    WHERE nome = ?
    LIMIT 1
");

if (!$stmt) {
    die("Erro interno ao consultar os serviços.");
}

foreach ($servicos as $servico) {

    if ($servico === '') {
        continue;
    }

    $stmt->bind_param("s", $servico);

    if (!$stmt->execute()) {
        $stmt->close();
        die("Erro ao consultar o serviço.");
    }

    $resultado = $stmt->get_result()->fetch_assoc();

    if (!$resultado) {
        $stmt->close();
        die("O serviço \"" . htmlspecialchars($servico, ENT_QUOTES, 'UTF-8') . "\" não foi encontrado.");
    }

    $preco = (float) $resultado['preco'];

    if ($preco < 0) {
        $stmt->close();
        die("Preço de serviço inválido.");
    }

    $precos_servicos[] = [
        'nome'  => $resultado['nome'],
        'preco' => $preco
    ];

    $preco_total += $preco;
}

$stmt->close();

/*
|--------------------------------------------------------------------------
| Verifica valor total
|--------------------------------------------------------------------------
*/

if ($preco_total <= 0) {
    die("O valor total do agendamento é inválido.");
}

/*
|--------------------------------------------------------------------------
| Calcula o sinal de 30%
|--------------------------------------------------------------------------
*/

$valor_sinal = round($preco_total * 0.30, 2);

/*
|--------------------------------------------------------------------------
| Calcula o restante
|--------------------------------------------------------------------------
*/

$valor_restante = round(
    $preco_total - $valor_sinal,
    2
);

/*
|--------------------------------------------------------------------------
| Data
|--------------------------------------------------------------------------
*/

$data_timestamp = strtotime(
    $agendamento['data_agendamento']
);

if ($data_timestamp === false) {
    die("Data do agendamento inválida.");
}

$data = date(
    "d/m/Y",
    $data_timestamp
);

/*
|--------------------------------------------------------------------------
| Horário
|--------------------------------------------------------------------------
*/

$hora = substr(
    (string) $agendamento['hora_agendamento'],
    0,
    5
);

?>

<!DOCTYPE html>

<html lang="pt-br">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>mtnaildesigner.com</title>

    <link
        rel="shortcut icon"
        href="favicon.ico"
    >

    <link
        rel="stylesheet"
        href="css/pagamento.css"
    >

</head>

<body>

<div class="paga">

    <h1>Confira seu agendamento</h1>

    <!--
    ----------------------------------------------------------------------
    Serviços
    ----------------------------------------------------------------------
    -->

    <table>

        <thead>

            <tr>

                <th>Serviço</th>

                <th>Preço</th>

            </tr>

        </thead>

        <tbody>

        <?php foreach ($precos_servicos as $item): ?>

            <tr>

                <td>
                    <?= htmlspecialchars(
                        $item['nome'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </td>

                <td>

                    R$

                    <?= number_format(
                        $item['preco'],
                        2,
                        ",",
                        "."
                    ) ?>

                </td>

            </tr>

        <?php endforeach; ?>

        <!-- Total -->

        <tr>

            <td>

                <strong>
                    Total
                </strong>

            </td>

            <td>

                <strong>

                    R$

                    <?= number_format(
                        $preco_total,
                        2,
                        ",",
                        "."
                    ) ?>

                </strong>

            </td>

        </tr>

        </tbody>

    </table>

    <br>

    <!--
    ----------------------------------------------------------------------
    Data
    ----------------------------------------------------------------------
    -->

    <p>

        <strong>
            Data:
        </strong>

        <?= htmlspecialchars(
            $data,
            ENT_QUOTES,
            'UTF-8'
        ) ?>

    </p>

    <!--
    ----------------------------------------------------------------------
    Horário
    ----------------------------------------------------------------------
    -->

    <p>

        <strong>
            Horário:
        </strong>

        <?= htmlspecialchars(
            $hora,
            ENT_QUOTES,
            'UTF-8'
        ) ?>

    </p>

    <br>

    <!--
    ----------------------------------------------------------------------
    Resumo financeiro
    ----------------------------------------------------------------------
    -->

    <div class="valor">

        <p>

            Valor Total:

            <strong>

                R$

                <?= number_format(
                    $preco_total,
                    2,
                    ",",
                    "."
                ) ?>

            </strong>

        </p>

        <p>

            Sinal de 30%:

            <strong>

                R$

                <?= number_format(
                    $valor_sinal,
                    2,
                    ",",
                    "."
                ) ?>

            </strong>

        </p>

        <p>

            Restante a pagar no salão:

            <strong>

                R$

                <?= number_format(
                    $valor_restante,
                    2,
                    ",",
                    "."
                ) ?>

            </strong>

        </p>

    </div>

    <br>

    <!--
    ----------------------------------------------------------------------
    Aviso
    ----------------------------------------------------------------------
    -->

    <p>

        Para confirmar o agendamento, é necessário
        pagar <strong>30%</strong> do valor do serviço.

    </p>

    <p>

        O valor restante deverá ser pago
        <strong>no salão</strong> após o atendimento.

    </p>

    <br>

    <!--
    ----------------------------------------------------------------------
    Botão de pagamento
    ----------------------------------------------------------------------
    -->

    <a href="paga.php">

        Fazer Pagamento

    </a>

</div>

</body>

</html>