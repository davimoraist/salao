<?php
 session_start();
 require_once __DIR__ . "/conecte.php";
 
 $diabetes = trim($_POST['diabetes'] ?? '');
 $gestante = trim($_POST['gestante'] ?? '');
 $alergias = trim($_POST['alergias'] ?? '');
 $retirar = trim($_POST['cuticula'] ?? '');
 $onicomicose = trim($_POST['onicomicose'] ?? '');
 $medicamento = trim($_POST['medicamento'] ?? '');
 $lamina = trim($_POST['lamina'] ?? '');
 $encravada = trim($_POST['encravada'] ?? '');
 $onicofagia = trim($_POST['onicofagia'] ?? '');
 $esporte = trim($_POST['esporte'] ?? '');
 $piscina = trim($_POST['piscina'] ?? '');
 
 if ($diabetes === '' || $gestante === '' || $alergias === '' || $retirar === '' || $onicomicose === '' || $medicamento === '' || $lamina === '' || $encravada === '' || $onicofagia === '' || $esporte === '' || $piscina === '') {
     $_SESSION['criar_error'] = '❌ Preencha todos os campos.';
     header("Location: agenda.php");
     exit;
 }

 
?>  