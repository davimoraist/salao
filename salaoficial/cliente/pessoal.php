<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="css/ficha.css">
</head>
<body>
    <div  class="ficha">
        
        <form action="#" method="post" class="form-box">
            <h1>Dados Pessoais</h1>
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
                     <input type="radio" name="como_conheceu" id="facebook">
                     <label for="facebook">Facebook</label>
                 </div>
                 <div>
                     <input type="radio" name="como_conheceu" id="instagram">
                     <label for="instagram">Instagram</label>
                 </div>
                 <div>
                     <input type="radio" name="como_conheceu" id="indicacao">
                     <label for="indicacao">Indecação</label>
                     <input type="text" name="indicacao_texto">
                 </div>
                 <div>
                     <input type="radio" name="como_conheceu" id="outros_conheceu">
                     <label for="outros_conheceu">Outros</label>
                     <input type="text" name="outros_conheceu_texto">
                 </div>
            </div>
            <button>Enviar</button>
        </form>
    </div>
    <script src="anamnes.js"></script>
</body>
</html>