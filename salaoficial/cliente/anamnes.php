 <?php
session_start();

if (!isset($_SESSION['id'])) {
    header("Location: cliente.php");
    exit;
}
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
<h2>Nome: <?php echo htmlspecialchars($_SESSION['nome']); ?></h2>
</div>

<!-- Diabetes -->
<div>
<p>Tem Diabetes?</p>
<input type="radio" name="diabetes" value="sim" required> Sim
<input type="radio" name="diabetes" value="nao"> Não
</div>

<!-- Gestante -->
<div>
<p>É Gestante?</p>
<input type="radio" name="gestante" value="sim" required> Sim
<input type="radio" name="gestante" value="nao"> Não
</div>

<!-- Alergias -->
<div>
<p>Possui alergias?</p>
<input type="radio" name="alergias" value="sim" required> Sim
<input type="radio" name="alergias" value="nao"> Não
<br>
<label>Especifique:</label>
<input type="text" name="especificar_alergia">
</div>

<!-- Cutícula -->
<div>
<p>Costuma retirar a cutícula?</p>
<input type="radio" name="cuticula" value="sim" required> Sim
<input type="radio" name="cuticula" value="nao"> Não
</div>

<!-- Onicomicose -->
<div>
<p>Possui onicomicose?</p>
<input type="radio" name="onicomicose" value="sim" required> Sim
<input type="radio" name="onicomicose" value="nao"> Não
<br>
<label>Especifique:</label>
<input type="text" name="especificar_onico">
</div>

<!-- Medicamento -->
<div>
<p>Faz uso de medicamento?</p>
<input type="radio" name="medicamento" value="sim" required> Sim
<input type="radio" name="medicamento" value="nao"> Não
<br>
<label>Qual?</label>
<input type="text" name="qual_medicamento">
</div>

<!-- Lâmina -->
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

<!-- Encravada -->
<div>
<p>Possui unha encravada?</p>
<input type="radio" name="encravada" value="sim" required> Sim
<input type="radio" name="encravada" value="nao"> Não
</div>

<!-- Onicofagia -->
<div>
<p>Possui onicofagia?</p>
<input type="radio" name="onicofagia" value="sim" required> Sim
<input type="radio" name="onicofagia" value="nao"> Não
</div>

<!-- Esporte -->
<div>
<p>Pratica esporte de impacto?</p>
<input type="radio" name="esporte" value="sim" required> Sim
<input type="radio" name="esporte" value="nao"> Não
</div>

<!-- Piscina -->
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