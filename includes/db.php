<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'sistema_inspecao';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die('Erro ao conectar: ' . $conn->connect_error);
}
?>