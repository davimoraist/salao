<?php
require_once("login.php");

  $sql = $pdo->query("SELECT c.id, c.nome, c.email, p.endereco, p.telefone, p.cpf, p.data_nascimento, p.como_conheceu, p.data_cadastro, f.diabetes, f.gestante, f.alergias, f.especificar_alergia, f.cuticula, f.onicomicose, f.especificar_onico, f.medicamento, f.qual_medicamento, f.lamina, f.outro_lamina_texto, f.encravada, f.onicofagia, f.esporte, f.piscina, f.data_cadastro AS data_ficha 

FROM cliente c

LEFT JOIN pessoais p 
    ON c.id = p.id_cliente 

LEFT JOIN ficha_anamnese f 
    ON c.id = f.id_cliente

ORDER BY c.id DESC
");
 
$clientes = $sql->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($clientes);
?>