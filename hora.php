<?php
// Configurações de conexão
$host = "localhost";
$user = "root";
$pass = "";
$db   = "fluxo_pessoas";

// Conectar ao banco de dados
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

// Consulta para obter a hora do banco
$sql = "SELECT NOW() as hora_banco";
$result = $conn->query($sql);

if ($result && $row = $result->fetch_assoc()) {
    echo "Hora atual do banco: " . $row['hora_banco'];
} else {
    echo "Não foi possível obter a hora do banco.";
}

$conn->close();
?>