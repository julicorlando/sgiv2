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
    
    <div class="card">
        <div class="card-header section-title bg-primary text-white">
            Inspeções
        </div>
        <div class="card-body">
            <b>Manutenção:</b> Cadastre e acompanhe solicitações de manutenção predial, elétrica, hidráulica ou de equipamentos. É possível registrar ações, anotações, anexar fotos e atualizar o status.<br>
            <b>Limpeza:</b> Registre inspeções e ocorrências relacionadas à limpeza dos ambientes, com campos para ações, anotações, anexos e acompanhamento de status.<br>
            <b>Pendências:</b> Visualize um resumo rápido das principais atividades, pendências e status do sistema ao acessar o Dashboard. Use-o para se orientar rapidamente sobre o andamento das operações.
        </div>
    </div>

    <div class="card">
        <div class="card-header section-title bg-primary text-white">
            Plantão
        </div>
        <div class="card-body">
            Preencha e salve relatórios de plantão, com observações e ações realizadas em cada setor (Limpeza, Segurança, Manutenção, Marketing, Ocorrências, Estacionamento, etc). Consulte relatórios anteriores em "Histórico de Plantão" e imprima sempre que necessário.
        </div>
    </div>

    <div class="card">
        <div class="card-header section-title bg-primary text-white">
            Chamados
        </div>
        <div class="card-body">
            <b>T.I.:</b> Abra e acompanhe chamados de tecnologia, descrevendo o problema, status e histórico de atendimento.<br>
            <b>Cadastro de Usuário:</b> Adicione novos usuários ao sistema (uso restrito).<br>
            <b>Alterar Senha:</b> Permite que cada usuário altere sua própria senha de acesso.
        </div>
    </div>

    <div class="card">
        <div class="card-header section-title bg-primary text-white">
            Históricos
        </div>
        <div class="card-body">
            <b>Histórico de Inspeções:</b> Consulte todo o histórico de registros de manutenção, limpeza e chamados de TI, com filtros e detalhes de cada registro.<br>
            <b>Histórico de Plantão:</b> Acesse os relatórios de plantão enviados, podendo visualizar detalhes ou imprimir relatórios anteriores.
        </div>
    </div>

    <div class="card">
        <div class="card-header section-title bg-primary text-white">
            Fluxo de Pessoas
        </div>
        <div class="card-body">
            Registre diariamente o fluxo de pessoas no shopping e gere relatórios comparativos de períodos, auxiliando no controle de acesso e tomada de decisões estratégicas.
        </div>
    </div>

    <div class="alert alert-info mt-4">
        Dica: Salve frequentemente e revise os dados inseridos antes de finalizar cada registro. Para melhor experiência, utilize o sistema preferencialmente em computadores desktop ou notebooks.
    </div>
</div>
<?php include 'includes/footer.php'; ?>
</body>
</html>