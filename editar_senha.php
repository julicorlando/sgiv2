<?php
session_start();
include 'includes/db.php';
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senha = $_POST['senha'] ?? '';
    $senha2 = $_POST['senha2'] ?? '';
    if ($senha !== $senha2) {
        $erro = "Senhas não conferem.";
    } else {
        $hash = password_hash($senha, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE usuarios SET senha=? WHERE id=?");
        $stmt->bind_param("si", $hash, $_SESSION['usuario_id']);
        if ($stmt->execute()) {
            $msg = "Senha atualizada com sucesso!";
        } else {
            $erro = "Erro ao atualizar senha.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Alterar Senha</title>
    <link rel="stylesheet" href="assets/bootstrap.min.css">
</head>
<body>
<div class="container mt-5" style="max-width:400px">
    <h2>Alterar Senha</h2>
    <?php if (!empty($erro)) echo '<div class="alert alert-danger">'.$erro.'</div>'; ?>
    <?php if (!empty($msg)) echo '<div class="alert alert-success">'.$msg.'</div>'; ?>
    <form method="post">
        <div class="mb-3">
            <label>Nova senha</label>
            <input type="password" class="form-control" name="senha" required>
        </div>
        <div class="mb-3">
            <label>Repetir senha</label>
            <input type="password" class="form-control" name="senha2" required>
        </div>
        <button type="submit" class="btn btn-success">Alterar</button>
        <a href="index.php" class="btn btn-secondary">Voltar</a>
    </form>
</div>
<?php include 'includes/footer.php'; ?>
</body>
</html>