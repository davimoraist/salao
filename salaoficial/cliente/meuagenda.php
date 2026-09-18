<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Agendamentos | MT Nail Designer</title>
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="css/meuagenda.css">
</head>

<body>

    <main class="container">
        <!-- Container principal do card -->
        <div class="meu-agendamentos">
            <h1>Meus Agendamentos</h1>

            <div class="meuagenda">
                <p><strong>Nome:</strong> <span id="nome">Carregando...</span></p>
            </div>
            <div class="meuagenda">
                <p><strong>Serviço:</strong> <span id="servico">Carregando...</span></p>
            </div>
            <div class="meuagenda">
                <p><strong>Data:</strong> <span id="data">Carregando...</span></p>
            </div>
            <div class="meuagenda">
                <p><strong>Horários:</strong> <span id="hora">Carregando...</span></p>
            </div>
            <div class="meuagenda">
                <p><strong>Entrada paga:</strong> <span id="entrada" class="valor-pago">R$ 0,00</span></p>
            </div>
            <div class="meuagenda">
                <p><strong>Falta pagar:</strong> <span id="falta" class="valor-falta">R$ 0,00</span></p>
            </div>

            <button type="button" class="Cancelar" onclick="cancelar()">Cancelar o Serviço</button>
        </div>
    </main>

    <script src="meuagenda.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>

</html>