<?php
// Recebe dados do formulário
$tipo = $_POST['tipo'] ?? '';
$natureza = $_POST['natureza'] ?? '';
$local = $_POST['local'] ?? '';
$acao = $_POST['acao'] ?? '';
$anotacoes = $_POST['anotacoes'] ?? '';
$status = $_POST['status'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Impressão - <?php echo ucfirst($tipo); ?></title>
    <link rel="stylesheet" href="assets/bootstrap.min.css">
    <style>
        @media print {
            .no-print { display: none; }
        }
        .status-bar {
            height: 30px;
            border-radius: 5px;
            margin-bottom: 16px;
        }
        .status-Aberto { background-color: #ffc107; }
        .status-Em\ andamento { background-color: #17a2b8; }
        .status-Pendente { background-color: #fd7e14; }
        .status-Finalizado { background-color: #28a745; }
    </style>
</head>
<body>
<div class="container mt-5">
    <button class="btn btn-secondary no-print mb-3" onclick="window.print()">Imprimir</button>
    <h2 class="mb-4">Relatório de Inspeção - <?php echo ucfirst($tipo); ?></h2>
    <?php if ($tipo == 'manutencao'): ?>
        <div><strong>Natureza:</strong> <?php echo htmlspecialchars($natureza); ?></div>
    <?php endif; ?>
    <div><strong>Local/Equipamento:</strong> <?php echo htmlspecialchars($local); ?></div>
    <div><strong>Ação:</strong> <?php echo htmlspecialchars($acao); ?></div>
    <div><strong>Anotações:</strong> <?php echo nl2br(htmlspecialchars($anotacoes)); ?></div>
    <div class="my-3">
        <strong>Status:</strong> <?php echo htmlspecialchars($status); ?>
        <div class="status-bar status-<?php echo str_replace(' ', '\ ', $status); ?>"></div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
</body>
</html>