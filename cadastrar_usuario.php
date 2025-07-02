<?php
session_start();

// Senha de acesso para a página de cadastro
$senha_de_acesso = "07052025";

// Verifica se a senha já foi validada nesta sessão
if (!isset($_SESSION['cadastro_usuario_liberado'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['senha_acesso'])) {
        if ($_POST['senha_acesso'] === $senha_de_acesso) {
            $_SESSION['cadastro_usuario_liberado'] = true;
            header("Location: cadastrar_usuario.php");
            exit;
        } else {
            $erro = "Senha incorreta!";
        }
    }
    ?>
    <!DOCTYPE html>
    <html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <title>Acesso Restrito - Cadastro de Usuário</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
        <style>
            body { background: #f2f2f2; }
            .login-box {
                max-width: 380px;
                margin: 100px auto;
                background: #fff;
                border-radius: 10px;
                box-shadow: 0 0 16px #ccc;
                padding: 2rem 2.5rem;
                text-align: center;
            }
        </style>
    </head>
    <body>
        <div class="login-box">
            <h4 class="mb-4">Acesso Restrito</h4>
            <?php if(isset($erro)): ?>
                <div class="alert alert-danger"><?= $erro ?></div>
            <?php endif; ?>
            <form method="post">
                <div class="mb-3">
                    <label for="senha_acesso" class="form-label">Digite a senha de acesso:</label>
                    <input type="password" class="form-control" id="senha_acesso" name="senha_acesso" required autofocus>
                </div>
                <button type="submit" class="btn btn-primary w-100">Entrar</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// SE CHEGOU AQUI, USUÁRIO ESTÁ LIBERADO PARA VER O FORMULÁRIO DE CADASTRO
// --- código normal de cadastro abaixo ---

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

include 'includes/db.php';

$sucesso = "";
$erro = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['novo_usuario'])) {
    $usuario = trim($_POST['usuario']);
    $senha = $_POST['senha'];
    $senha2 = $_POST['senha2'];

    if (empty($usuario) || empty($senha) || empty($senha2)) {
        $erro = "Preencha todos os campos!";
    } elseif ($senha !== $senha2) {
        $erro = "As senhas não conferem!";
    } else {
        // Verifica se o usuário já existe
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE usuario = ?");
        $stmt->bind_param("s", $usuario);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $erro = "Já existe um usuário com esse nome.";
        } else {
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt2 = $conn->prepare("INSERT INTO usuarios (usuario, senha) VALUES (?,?)");
            $stmt2->bind_param("ss", $usuario, $senha_hash);
            if ($stmt2->execute()) {
                $sucesso = "Usuário cadastrado com sucesso!";
            } else {
                $erro = "Erro ao cadastrar usuário.";
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
    <title>Cadastrar Usuário</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<?php include 'includes/menu.php'; ?>
<div class="container mt-5" style="max-width:500px">
    <h3 class="mb-4">Cadastrar Novo Usuário</h3>
    <?php if($sucesso): ?>
        <div class="alert alert-success"><?= $sucesso ?></div>
    <?php elseif($erro): ?>
        <div class="alert alert-danger"><?= $erro ?></div>
    <?php endif; ?>
    <form method="post">
        <input type="hidden" name="novo_usuario" value="1">
        <div class="mb-3">
            <label for="usuario" class="form-label">Usuário</label>
            <input type="text" name="usuario" id="usuario" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="senha" class="form-label">Senha</label>
            <input type="password" name="senha" id="senha" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="senha2" class="form-label">Repita a Senha</label>
            <input type="password" name="senha2" id="senha2" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Cadastrar</button>
    </form>
</div>
</body>
</html>