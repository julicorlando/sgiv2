<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
include 'includes/db.php';

$sucesso = "";
$erro = "";

// Processa o formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? "");
    $senha = $_POST['senha'] ?? "";
    $senha2 = $_POST['senha2'] ?? "";
    $nome = trim($_POST['nome'] ?? "");
    $email = trim($_POST['email'] ?? "");
    $status = $_POST['status'] ?? "ativo";

    if (empty($usuario) || empty($senha) || empty($senha2) || empty($nome)) {
        $erro = "Preencha todos os campos obrigatórios!";
    } elseif ($senha !== $senha2) {
        $erro = "As senhas não conferem!";
    } else {
        // Verifica se já existe
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE usuario = ?");
        $stmt->bind_param("s", $usuario);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $erro = "Já existe um usuário com esse login.";
        } else {
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt2 = $conn->prepare("INSERT INTO usuarios (usuario, senha, tipo, nome, email, status) VALUES (?, ?, 'funcionario', ?, ?, ?)");
            $stmt2->bind_param("sssss", $usuario, $senha_hash, $nome, $email, $status); // CORRETO: 5 parâmetros
            if ($stmt2->execute()) {
                $sucesso = "Funcionário cadastrado com sucesso!";
            } else {
                $erro = "Erro ao cadastrar funcionário.";
            }
            $stmt2->close();
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastrar Funcionário</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<?php include 'includes/menu.php'; ?>
<div class="container mt-5" style="max-width:500px">
    <h2>Cadastrar Funcionário</h2>
    <?php if ($sucesso): ?>
        <div class="alert alert-success"><?= $sucesso ?></div>
    <?php endif; ?>
    <?php if ($erro): ?>
        <div class="alert alert-danger"><?= $erro ?></div>
    <?php endif; ?>
    <form method="post">
        <div class="mb-3">
            <label for="usuario" class="form-label">Login de Funcionário *</label>
            <input type="text" name="usuario" id="usuario" class="form-control" maxlength="40" required>
        </div>
        <div class="mb-3">
            <label for="nome" class="form-label">Nome Completo *</label>
            <input type="text" name="nome" id="nome" class="form-control" maxlength="80" required>
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">E-mail (opcional)</label>
            <input type="email" name="email" id="email" class="form-control" maxlength="100">
        </div>
        <div class="mb-3">
            <label for="status" class="form-label">Situação</label>
            <select name="status" id="status" class="form-select">
                <option value="ativo">Ativo</option>
                <option value="ferias">Férias</option>
                <option value="inativo">Inativo</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="senha" class="form-label">Senha *</label>
            <input type="password" name="senha" id="senha" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="senha2" class="form-label">Confirme a Senha *</label>
            <input type="password" name="senha2" id="senha2" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-success w-100">Cadastrar Funcionário</button>
    </form>
</div>
</body>
</html>