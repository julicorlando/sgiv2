<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

include 'includes/db.php';

// Buscar pendências de manutenção
$sql_manut = "SELECT m.id, m.natureza, m.local, m.acao, m.status, m.data_criacao, u.usuario as usuario_nome
              FROM manutencao m
              LEFT JOIN usuarios u ON m.usuario_id = u.id
              WHERE m.status='Aberto' OR m.status='Pendente'
              ORDER BY m.data_criacao DESC";
$res_manut = $conn->query($sql_manut);

// Buscar pendências de limpeza
$sql_limp = "SELECT l.id, l.local, l.acao, l.status, l.data_criacao, u.usuario as usuario_nome
             FROM limpeza l
             LEFT JOIN usuarios u ON l.usuario_id = u.id
             WHERE l.status='Aberto' OR l.status='Pendente'
             ORDER BY l.data_criacao DESC";
$res_limp = $conn->query($sql_limp);

// Buscar pendências de TI
$sql_ti = "SELECT t.id, t.titulo, t.status, t.data_criacao, u.usuario as usuario_nome
           FROM solicitacoes_ti t
           LEFT JOIN usuarios u ON t.usuario_id = u.id
           WHERE t.status='Aberto' OR t.status='Pendente'
           ORDER BY t.data_criacao DESC";
$res_ti = $conn->query($sql_ti);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Pendências</title>
    <link rel="stylesheet" href="assets/bootstrap.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
<?php include 'includes/menu.php'; ?>
<div class="container mt-4">

    <div class="row">
        <div class="col-12 mb-4">
            <h2>Dashboard - Pendências</h2>
        </div>
    </div>

    <!-- Pendências de Limpeza -->
    <div class="row">
        <div class="col-12 mb-4">
            <h4>Pendências de Limpeza</h4>
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Local</th>
                        <th>Ação</th>
                        <th>Status</th>
                        <th>Data</th>
                        <th>Solicitante</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($res_limp->num_rows > 0): ?>
                        <?php while($row = $res_limp->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['local']) ?></td>
                                <td><?= htmlspecialchars($row['acao']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $row['status']=='Aberto'?'warning':'danger' ?>">
                                        <?= htmlspecialchars($row['status']) ?>
                                    </span>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($row['data_criacao'])) ?></td>
                                <td><?= !empty($row['usuario_nome']) ? htmlspecialchars($row['usuario_nome']) : '<em>Desconhecido</em>' ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5">Nenhuma pendência encontrada.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pendências de Manutenção -->
    <div class="row">
        <div class="col-12 mb-4">
            <h4>Pendências de Manutenção</h4>
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Natureza</th>
                        <th>Local/Equipamento</th>
                        <th>Ação</th>
                        <th>Status</th>
                        <th>Data</th>
                        <th>Solicitante</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($res_manut->num_rows > 0): ?>
                        <?php while($row = $res_manut->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['natureza']) ?></td>
                                <td><?= htmlspecialchars($row['local']) ?></td>
                                <td><?= htmlspecialchars($row['acao']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $row['status']=='Aberto'?'warning':'danger' ?>">
                                        <?= htmlspecialchars($row['status']) ?>
                                    </span>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($row['data_criacao'])) ?></td>
                                <td><?= !empty($row['usuario_nome']) ? htmlspecialchars($row['usuario_nome']) : '<em>Desconhecido</em>' ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6">Nenhuma pendência encontrada.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pendências de TI -->
    <div class="row">
        <div class="col-12 mb-4">
            <h4>Pendências de TI</h4>
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Título</th>
                        <th>Status</th>
                        <th>Data</th>
                        <th>Solicitante</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($res_ti->num_rows > 0): ?>
                        <?php while($row = $res_ti->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['titulo']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $row['status']=='Aberto'?'warning':'danger' ?>">
                                        <?= htmlspecialchars($row['status']) ?>
                                    </span>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($row['data_criacao'])) ?></td>
                                <td><?= !empty($row['usuario_nome']) ? htmlspecialchars($row['usuario_nome']) : '<em>Desconhecido</em>' ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4">Nenhuma pendência encontrada.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <a href="ti.php" class="btn btn-primary mt-2">Nova Solicitação de TI</a>
        </div>
    </div>

</div>
<?php include 'includes/footer.php'; ?>
</body>
</html>