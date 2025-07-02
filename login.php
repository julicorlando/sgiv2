<?php
session_start();
include 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'] ?? '';
    $senha = $_POST['senha'] ?? '';
    $stmt = $conn->prepare("SELECT id, senha FROM usuarios WHERE usuario = ?");
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $stmt->bind_result($user_id, $senha_hash);
    if ($stmt->fetch() && password_verify($senha, $senha_hash)) {
        $_SESSION['usuario_id'] = $user_id;
        $_SESSION['usuario_nome'] = $usuario;
        header("Location: index.php");
        exit;
    } else {
        $erro = "Usuário ou senha inválidos.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="assets/bootstrap.min.css">
    <style>
        body {
            /* Fundo branco padrão mais logo centralizada e suave como marca d'água */
            background: #fff url('logo.png') no-repeat center center fixed;
            background-size: 350px auto;
        }
        .login-container {
            background: rgba(255,255,255,0.94);
            border-radius: 16px;
            box-shadow: 0 0 18px rgba(0,0,0,0.07);
            padding: 40px 30px 30px 30px;
            margin-top: 60px;
            position: relative;
            z-index: 1;
        }
        @media (max-width: 500px) {
            body {
                background-size: 75vw auto;
            }
            .login-container {
                padding: 24px 8px 18px 8px;
            }
        }
    </style>
</head>
<body>
<div class="container login-container" style="max-width:400px">
    <h2>Login</h2>
    <?php if (!empty($erro)) echo '<div class="alert alert-danger">'.$erro.'</div>'; ?>
    <form method="post">
        <div class="mb-3">
            <label>Usuário</label>
            <input type="text" class="form-control" name="usuario" required>
        </div>
        <div class="mb-3">
            <label>Senha</label>
            <input type="password" class="form-control" name="senha" required>
        </div>
        <button type="submit" class="btn btn-primary">Entrar</button>
        <!--<a href="cadastrar_usuario.php" class="btn btn-link">Cadastrar usuário</a>-->
    </form>
</div>
<?php include 'includes/footer.php'; ?>
</body>
</html>