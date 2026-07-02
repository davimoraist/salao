 <?php
// Inicia a sessão de forma segura se ainda não tiver sido iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Bloqueia o acesso direto se o usuário não estiver devidamente autenticado
if (!isset($_SESSION['id'])) {
    header("Location: cliente.php");
    exit;
}

// Garante a existência e persistência do Token CSRF para validar o envio do formulário
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/* =======================================================
   TABELA ALVO NO BANCO DE DADOS: ficha_anamnese
======================================================= */
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/ficha.css">
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <title>mtnaildesigner.com</title>
</head>
<body>
<div class="ficha">

<form action="ficha.php" method="post" class="form-box">
<h1>Ficha de Anamnese</h1>

<div>
<h2>Nome: <?php echo htmlspecialchars($_SESSION['nome'], ENT_QUOTES, 'UTF-8'); ?></h2>
</div>

<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

<div>
<p>Tem Diabetes?</p>
<input type="radio" name="diabetes" value="sim" required> Sim
<input type="radio" name="diabetes" value="nao"> Não
</div>

<div>
<p>É Gestante?</p>
<input type="radio" name="gestante" value="sim" required> Sim
<input type="radio" name="gestante" value="nao"> Não
</div>

<div>
<p>Possui alergias?</p>
<input type="radio" name="alergias" value="sim" required> Sim
<input type="radio" name="alergias" value="nao"> Não
<br>
<label>Especifique:</label>
<input type="text" name="especificar_alergia">
</div>

<div>
<p>Costuma retirar a cutícula?</p>
<input type="radio" name="cuticula" value="sim" required> Sim
<input type="radio" name="cuticula" value="nao"> Não
</div>

<div>
<p>Possui onicomicose?</p>
<input type="radio" name="onicomicose" value="sim" required> Sim
<input type="radio" name="onicomicose" value="nao"> Não
<br>
<label>Especifique:</label>
<input type="text" name="especificar_onico">
</div>

<div>
<p>Faz uso de medicamento?</p>
<input type="radio" name="medicamento" value="sim" required> Sim
<input type="radio" name="medicamento" value="nao"> Não
<br>
<label>Qual?</label>
<input type="text" name="qual_medicamento">
</div>

<div>
<p>A lâmina ungueal apresenta:</p>
<input type="radio" name="lamina" value="descamacao" required> Descamação
<input type="radio" name="lamina" value="estrias"> Estrias
<input type="radio" name="lamina" value="manchas"> Manchas
<input type="radio" name="lamina" value="descolamento"> Descolamento
<input type="radio" name="lamina" value="outro"> Outro
<br>
<input type="text" name="outro_lamina_texto" placeholder="Se outro, especifique">
</div>

<div>
<p>Possui unha encravada?</p>
<input type="radio" name="encravada" value="sim" required> Sim
<input type="radio" name="encravada" value="nao"> Não
</div>

<div>
<p>Possui onicofagia?</p>
<input type="radio" name="onicofagia" value="sim" required> Sim
<input type="radio" name="onicofagia" value="nao"> Não
</div>

<div>
<p>Pratica esporte de impacto?</p>
<input type="radio" name="esporte" value="sim" required> Sim
<input type="radio" name="esporte" value="nao"> Não
</div>

<div>
<p>Entra em piscina com frequência?</p>
<input type="radio" name="piscina" value="sim" required> Sim
<input type="radio" name="piscina" value="nao"> Não
</div>

<button type="submit">Enviar</button>

</form>
</div>
</body>
</html>