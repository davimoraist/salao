<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="css/agenda.css">
</head>
<body>
    <form action="#">
        <h1>agendamento</h1>
        <div class="linha"></div>
        <div class="none">
            <H2>Nome:</H2>
        </div>
        <H2>Escolha a data</H2>
        <div class="carrossel">
            <div class="seta" onclick="mover(-1)">◀</div>
            <div class="datas" id="datas"></div>
            <div class="seta" onclick="mover(1)">▶</div>
        </div>
        <h2>Horários disponíveis</h2>
        <div class="horarios" id="horarios"></div>
    </form>
    <script src="agenda.js"></script>
</body>
</html>