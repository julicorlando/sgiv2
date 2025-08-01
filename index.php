<?php
// index.php - Página inicial explicativa do sistema
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
    <title>Bem-vindo ao Sistema de Inspeções</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        .section-title { margin-top:2rem; font-size:1.3rem; }
        .card { margin-bottom: 1.5rem; }
    </style>
</head>
<body>
<?php include 'includes/menu.php'; ?>
<div class="container mt-4 mb-5">
    <h1 class="mb-4">Bem-vindo ao Sistema de Gestão de Operações</h1>
    <div class="alert alert-secondary">
        <b>Instruções Gerais:</b><br>
        Este sistema foi desenvolvido para facilitar o registro, controle e acompanhamento das rotinas de <b>manutenção</b>, <b>limpeza</b>, <b>relatórios de plantão</b>, <b>chamados de TI</b> e a <b>gestão do fluxo de pessoas</b> no shopping.<br><br>
        <ul>
            <li>Utilize o menu superior para acessar cada módulo do sistema.</li>
            <li>Preencha os formulários de registro de acordo com a ocorrência ou necessidade.</li>
            <li>Consulte históricos e relatórios para acompanhamento das operações.</li>
            <li>Em caso de dúvidas, utilize a área de suporte ou contate o administrador.</li>
        </ul>
    </div>

    <div class="alert alert-info mt-4">
        Dica: Salve frequentemente e revise os dados inseridos antes de finalizar cada registro. Para melhor experiência, utilize o sistema preferencialmente em computadores desktop ou notebooks.
    </div>
</div>
<?php include 'includes/footer.php'; ?>
</body>
</html>