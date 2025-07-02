<?php
date_default_timezone_set('America/Sao_Paulo');
$hoje = date('d/m/Y');

// Garante que a sessão esteja iniciada e o usuário logado
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Registro de Fluxo</title>
    <link rel="stylesheet" href="assets/bootstrap.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        :root {
            --cor-primaria: #00bfff;
            --cor-primaria-hover: #009acd;
            --cor-texto: #333;
            --fundo-form: #fff;
        }
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            color: var(--cor-texto);
            background-color: #fff;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .container-fluxo {
            background: var(--fundo-form);
            max-width: 400px;
            width: 100%;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 0 10px rgba(0,0,0,0.08);
            text-align: center;
            margin: 40px auto;
        }
        h2 {
            margin-bottom: 20px;
            color: var(--cor-primaria);
        }
        .input-campo {
            padding: 10px;
            width: 100%;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 16px;
            color: #333;
            background: #fafafa;
        }
        .btn-fluxo {
            padding: 10px 20px;
            background-color: var(--cor-primaria);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .btn-fluxo:hover {
            background-color: var(--cor-primaria-hover);
        }
        .footer-fix {
            width: 100%;
            margin-top: auto;
            background: #f8f9fa;
            color: #888;
            padding: 12px 0;
            text-align: center;
            font-size: 0.95rem;
            border-top: 1px solid #eee;
        }
        @media (max-width: 500px) {
            .container-fluxo {
                padding: 20px;
            }
            h2 {
                font-size: 1.2rem;
            }
        }
    </style>
</head>
<body>
<?php include 'includes/menu.php'; ?>
<div class="container d-flex justify-content-center align-items-center flex-grow-1" style="min-height: 80vh;">
    <div class="container-fluxo">
        <h2>Fluxo dia - <?php echo $hoje; ?></h2>
        <form action="salvar.php" method="POST">
            <input class="input-campo" type="number" name="quantidade" placeholder="Fluxo de pessoas" required>
            <!-- Campo oculto com o ID do usuário logado -->
            <input type="hidden" name="usuario_id" value="<?= htmlspecialchars($_SESSION['usuario_id']) ?>">
            <br>
            <button class="btn-fluxo" type="submit">✅ Registrar</button>
        </form>
    </div>
</div>
<div class="footer-fix">
    <?php include 'includes/footer.php'; ?>
</div>
</body>
</html>