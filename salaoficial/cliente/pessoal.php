<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>mtnaildesigner.com</title>
    <link rel="stylesheet" href="css/ficha.css">
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
</head>
<body>
    <div  class="ficha">
        
        <form action="dadocliente.php" method="post" class="form-box">
            <h1>Dado pessoais</h1>
            <div>
             <label for="endereco">Endereço</label>
             <input type="text" name="endereco" id="endereco">
         </div>

         <div>
             <label for="tel">Telefone</label>
             <input type="tel" name="tel" id="tel" maxlength="14">
         </div>
         
            <!-- cpf -->
            <div>
                 <label for="cpf">CPF</label>
                 <input type="text" name="cpf" id="cpf" maxlength="14">
             </div>
             <!-- data de nascimento -->
             <div>
                 <label for="idade">data de nascimento</label>
                 <input type="date" name="idade" id="idade">
             </div>
        
             <div>
                 <p>Como Nos Conheceu?</p>
                 <div>
                     <input type="radio" value="facebook" name="como_conheceu" id="facebook">
                     <label for="facebook">Facebook</label>
                 </div>
                 <div>
                     <input type="radio" value="instagram" name="como_conheceu" id="instagram">
                     <label for="instagram">Instagram</label>
                 </div>
                <div>
                        <input type="radio" name="como_conheceu" value="Outros" id="outros_conheceu">
                        <label for="outros_conheceu">Outros</label>
                </div>

                <div id="campo_outros" style="display:none;">
                        <input type="text" name="complemento" id="complemento" placeholder="Escreva aqui">
                </div>

            </div>
            <button>Enviar</button>
        </form>
    </div>
    <script src="anamnes.js"></script>
</body>
</html>