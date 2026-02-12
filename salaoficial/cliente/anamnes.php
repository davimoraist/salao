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
    <title>ficha anamnese</title>
</head>
<body>
    <div class="ficha">
       
       <form action="#" class="form-box">
        <h1>ficha anamnese</h1> 
         <!--==dados pessoais==-->
         <div>
            <h2>Nome: <?php echo $_SESSION['nome']; ?></h2>
         </div>


        <!--==Tem Diabetes==-->
        <div>
            <p>Tem Diabetes?</p>
            <input type="radio" name="diabetes" id="diabetes_sim">
            <label for="diabetes_sim">Sim</label>

            <input type="radio" name="diabetes" id="diabetes_nao">
            <label for="diabetes_nao">Não</label>
        </div>

        <!--==Gestante==-->
        <div>
            <p>É Gestante?</p>
            <input type="radio" name="gestante" id="gestante_sim">
            <label for="gestante_sim">Sim</label>

            <input type="radio" name="gestante" id="gestante_nao">
            <label for="gestante_nao">Não</label>
        </div>

        <!--==Alergias==-->
        <div>
            <p>Possui Alergias a esmalte, cosmético ou algum outro componente?</p>
            <input type="radio" name="alergias" id="alergia_sim">
            <label for="alergia_sim">Sim</label>

            <input type="radio" name="alergias" id="alergia_nao">
            <label for="alergia_nao">Não</label>

            <div>
                <label for="especificar_alergia">Especifique:</label>
                <input type="text" name="especificar_alergia" id="especificar_alergia">
            </div>
        </div>

        <!--==Cutícula==-->
        <div>
            <p>Costuma retirar a cutícula (eponiquio)?</p>
            <input type="radio" name="cuticula" id="cuticula_sim">
            <label for="cuticula_sim">Sim</label>

            <input type="radio" name="cuticula" id="cuticula_nao">
            <label for="cuticula_nao">Não</label>
        </div>

        <!--==Onicomicose==-->
        <div>
            <p>Possui algum problema de onicimicose?</p>
            <input type="radio" name="onicomicose" id="onicomicose_sim">
            <label for="onicomicose_sim">Sim</label>

            <input type="radio" name="onicomicose" id="onicomicose_nao">
            <label for="onicomicose_nao">Não</label>

            <div>
                <label for="especificar_onico">Especifique:</label>
                <input type="text" name="especificar_onico" id="especificar_onico">
            </div>
        </div>

        <!--==Medicamento==-->
        <div>
            <p>Faz uso de algum medicamento?</p>
            <input type="radio" name="medicamento" id="medicamento_sim">
            <label for="medicamento_sim">Sim</label>

            <input type="radio" name="medicamento" id="medicamento_nao">
            <label for="medicamento_nao">Não</label>

            <div>
                <label for="qual_medicamento">Qual</label>
                <input type="text" name="qual_medicamento" id="qual_medicamento">
            </div>
        </div>

        <!--==Lâmina ungueal==-->
        <div>
            <p>A lâmina ungueal apresenta:</p>
            <input type="radio" name="lamina" id="descamacao">
            <label for="descamacao">Descamação</label>

            <input type="radio" name="lamina" id="estrias">
            <label for="estrias">Estrias</label>

            <input type="radio" name="lamina" id="manchas">
            <label for="manchas">Manchas</label>

            <input type="radio" name="lamina" id="descolamento">
            <label for="descolamento">Descolamento</label>

            <input type="radio" name="lamina" id="outro_lamina">
            <label for="outro_lamina">Outro</label>
            <input type="text" name="outro_lamina_texto">
        </div>

        <!--==Unha encravada==-->
        <div>
            <p>Possui onicocriptose (unha encravada)?</p>
            <input type="radio" name="encravada" id="encravada_sim">
            <label for="encravada_sim">Sim</label>

            <input type="radio" name="encravada" id="encravada_nao">
            <label for="encravada_nao">Não</label>
        </div>

        <!--==Onicofagia==-->
        <div>
            <p>Possui onicofagia?</p>
            <input type="radio" name="onicofagia" id="onicofagia_sim">
            <label for="onicofagia_sim">Sim</label>

            <input type="radio" name="onicofagia" id="onicofagia_nao">
            <label for="onicofagia_nao">Não</label>
        </div>

        <!--==Esporte impacto==-->
        <div>
            <p>Pratica esporte de impacto?</p>
            <input type="radio" name="esporte" id="esporte_sim">
            <label for="esporte_sim">Sim</label>

            <input type="radio" name="esporte" id="esporte_nao">
            <label for="esporte_nao">Não</label>
        </div>

        <!--==Piscina==-->
        <div>
            <p>Entra em piscina com frequência?</p>
            <input type="radio" name="piscina" id="piscina_sim">
            <label for="piscina_sim">Sim</label>

            <input type="radio" name="piscina" id="piscina_nao">
            <label for="piscina_nao">Não</label>
        </div>
        <button>Enviar</button>
       </form>
    </div>
     
</body>
</html>