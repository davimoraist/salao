<?php
header("Content-Type: application/json; charset=utf-8");
$apiKey = 'coloca seu api aqui';

$nomecliente  = filter_input(INPUT_POST, 'nome', FILTER_DEFAULT);
$emailcliente = filter_input(INPUT_POST, 'email', FILTER_DEFAULT);
$cpfcliente   = filter_input(INPUT_POST, 'cpf', FILTER_DEFAULT);
$cepcliente   = filter_input(INPUT_POST, 'cep', FILTER_DEFAULT);

if (!$nomeCliente) $nomeCliente = "Cliente Ficticio " . rand(10, 99);
if (!$cpfCliente)  $cpfCliente  = criarCpfFicticioForcado(); 
if (!$valorPix || $valorPix <= 0) $valorPix = 50.00;

?>