<?php
// index.php - Página inicial explicativa do sistema
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Recupera tipo do usuário
$tipo_usuario = 'padrao';
if (isset($_SESSION['usuario_id'])) {
    include_once 'includes/db.php';
    $stmt = $conn->prepare("SELECT tipo FROM usuarios WHERE id=?");
    $stmt->bind_param("i", $_SESSION['usuario_id']);
    $stmt->execute();
    $stmt->bind_result($tipo_usuario);
    $stmt->fetch();
    $stmt->close();
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

    <?php if ($tipo_usuario === 'administrador' || $tipo_usuario === 'admin'): ?>
        <div class="card">
            <div class="card-header bg-primary text-white">
                <strong>Informações Administrativas</strong>
            </div>
            <div class="card-body">
                <p>Como administrador, você pode visualizar todas as informações já cadastradas no sistema, acessar relatórios completos, editar registros e gerenciar usuários. Utilize o menu para acessar áreas exclusivas e realizar consultas detalhadas.</p>
                <ul>
                    <li>Acesse <b>Histórico de Serviços</b> para consultar e filtrar todas as operações registradas.</li>
                    <li>Utilize <b>Cadastro de Funcionário</b> para incluir ou editar funcionários do sistema.</li>
                    <li>Veja <b>Relatórios de Plantão</b> para acompanhar a rotina dos funcionários.</li>
                </ul>
            </div>
        </div>
    <?php elseif ($tipo_usuario === 'funcionario'): ?>
        <div class="card">
            <div class="card-header bg-success text-white">
                <strong>Área do Funcionário</strong>
            </div>
            <div class="card-body">
                <ul>
                    <li><b>Botão "Abertura de Chamados"</b> acima permite abrir solicitações diretamente para o setor de TI.</li>
                    <li><b>Botão "Serviços em Aberto"</b> mostra para você todas as tarefas de manutenção e operações que estão pendentes ou em andamento e que foram atribuídas ao seu usuário.</li>
                    <li>Após concluir o serviço, marque-o como finalizado para atualizar o status.</li>
                </ul>
                <div class="alert alert-warning mt-3">
                    Atenção: Fique atento às atualizações dos seus serviços e utilize os botões para acessar rapidamente suas tarefas e chamados.
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="alert alert-info mt-4">
        Dica: Salve frequentemente e revise os dados inseridos antes de finalizar cada registro. Para melhor experiência, utilize o sistema preferencialmente em computadores desktop ou notebooks.
    </div>
</div>
<?php include 'includes/footer.php'; ?>
</body>
</html>