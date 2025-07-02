<?php
// Garante sessão ativa para identificar o usuário
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario_id'])) {
    echo "<script>alert('Sessão expirada. Faça login novamente.'); window.location.href = 'login.php';</script>";
    exit;
}

$quantidade = $_POST['quantidade'] ?? null;
$usuario_id = $_SESSION['usuario_id'];

if (!$quantidade || !is_numeric($quantidade) || $quantidade <= 0) {
    echo "<script>alert('Quantidade inválida ou não fornecida.'); window.location.href = 'index.php';</script>";
    exit;
}

$conn = new mysqli("localhost", "root", "", "fluxo_pessoas");

if ($conn->connect_error) {
    echo "<script>alert('Erro na conexão com o banco de dados.'); window.location.href = 'index.php';</script>";
    exit;
}

$data_hoje = date("Y-m-d");

// Prepara e executa o INSERT, incluindo o usuário!
$sql = "INSERT INTO registros_fluxo (quantidade, usuario_id, data_registro) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo "<script>alert('Erro ao preparar o statement.'); window.location.href = 'index.php';</script>";
    $conn->close();
    exit;
}

$stmt->bind_param("iis", $quantidade, $usuario_id, $data_hoje);

try {
    $stmt->execute();
    echo "<script>alert('Registro salvo com sucesso!'); window.location.href = 'index.php';</script>";
} catch (mysqli_sql_exception $e) {
    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
        echo "<script>alert('Já existe um registro para hoje. Só é permitido um por dia.'); window.location.href = 'index.php';</script>";
    } else {
        echo "<script>alert('Erro ao salvar: " . addslashes($e->getMessage()) . "'); window.location.href = 'index.php';</script>";
    }
}

$stmt->close();
$conn->close();
?>