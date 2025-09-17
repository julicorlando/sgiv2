<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

include 'includes/db.php';

// Verificar se a tabela de logs existe, se não, criar
$conn->query("CREATE TABLE IF NOT EXISTS system_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    tabela VARCHAR(50) NOT NULL,
    registro_id INT NOT NULL,
    campo_alterado VARCHAR(100) NOT NULL,
    valor_anterior TEXT,
    valor_novo TEXT,
    data_alteracao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_data (data_alteracao),
    INDEX idx_tabela (tabela),
    INDEX idx_usuario (usuario_id)
)");

// Pagination
$items_per_page = 20;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $items_per_page;

// Filters
$filtro_usuario = isset($_GET['usuario']) ? trim($_GET['usuario']) : '';
$filtro_tabela = isset($_GET['tabela']) ? $_GET['tabela'] : '';
$filtro_data = isset($_GET['data']) ? $_GET['data'] : '';

// Build WHERE clause
$where_conditions = [];
$params = [];
$types = '';

if ($filtro_usuario) {
    $where_conditions[] = "u.nome LIKE ?";
    $params[] = "%$filtro_usuario%";
    $types .= 's';
}

if ($filtro_tabela) {
    $where_conditions[] = "l.tabela = ?";
    $params[] = $filtro_tabela;
    $types .= 's';
}

if ($filtro_data) {
    $where_conditions[] = "DATE(l.data_alteracao) = ?";
    $params[] = $filtro_data;
    $types .= 's';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Count total records
$count_sql = "SELECT COUNT(*) as total 
              FROM system_logs l 
              LEFT JOIN usuarios u ON l.usuario_id = u.id 
              $where_clause";

if (!empty($params)) {
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param($types, ...$params);
    $count_stmt->execute();
    $total_records = $count_stmt->get_result()->fetch_assoc()['total'];
    $count_stmt->close();
} else {
    $total_records = $conn->query($count_sql)->fetch_assoc()['total'];
}

$total_pages = ceil($total_records / $items_per_page);

// Get logs with pagination
$sql = "SELECT l.*, u.nome as usuario_nome 
        FROM system_logs l 
        LEFT JOIN usuarios u ON l.usuario_id = u.id 
        $where_clause
        ORDER BY l.data_alteracao DESC 
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $params[] = $items_per_page;
    $params[] = $offset;
    $types .= 'ii';
    $stmt->bind_param($types, ...$params);
} else {
    $stmt->bind_param('ii', $items_per_page, $offset);
}

$stmt->execute();
$logs = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Logs de Alterações</title>
    <link rel="stylesheet" href="assets/bootstrap.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        .pagination-container { text-align: center; margin: 20px 0; }
        .log-row { font-size: 0.9rem; }
        .log-diff { background: #f8f9fa; padding: 8px; border-radius: 4px; margin: 2px 0; }
        .valor-anterior { color: #d63384; font-weight: bold; }
        .valor-novo { color: #198754; font-weight: bold; }
    </style>
</head>
<body>
<?php include 'includes/menu.php'; ?>
<div class="container mt-4">
    <h2>Logs de Alterações do Sistema</h2>
    
    <!-- Filtros -->
    <form method="get" class="mb-4 card p-3">
        <div class="row g-2">
            <div class="col-md-3">
                <label class="form-label">Usuário:</label>
                <input type="text" name="usuario" class="form-control" value="<?= htmlspecialchars($filtro_usuario) ?>" placeholder="Nome do usuário">
            </div>
            <div class="col-md-2">
                <label class="form-label">Tabela:</label>
                <select name="tabela" class="form-select">
                    <option value="">Todas</option>
                    <option value="manutencao" <?= $filtro_tabela == 'manutencao' ? 'selected' : '' ?>>Manutenção</option>
                    <option value="limpeza" <?= $filtro_tabela == 'limpeza' ? 'selected' : '' ?>>Limpeza</option>
                    <option value="usuarios" <?= $filtro_tabela == 'usuarios' ? 'selected' : '' ?>>Usuários</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Data:</label>
                <input type="date" name="data" class="form-control" value="<?= htmlspecialchars($filtro_data) ?>">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">Filtrar</button>
                <a href="logs.php" class="btn btn-secondary">Limpar</a>
            </div>
        </div>
    </form>

    <!-- Informações de paginação -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <small class="text-muted">
            Mostrando <?= min($offset + 1, $total_records) ?> a <?= min($offset + $items_per_page, $total_records) ?> de <?= $total_records ?> registros
        </small>
        <small class="text-muted">Página <?= $page ?> de <?= $total_pages ?></small>
    </div>

    <!-- Tabela de logs -->
    <?php if ($logs->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Data/Hora</th>
                        <th>Usuário</th>
                        <th>Tabela</th>
                        <th>Registro ID</th>
                        <th>Campo</th>
                        <th>Alteração</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($log = $logs->fetch_assoc()): ?>
                        <tr class="log-row">
                            <td><?= date('d/m/Y H:i:s', strtotime($log['data_alteracao'])) ?></td>
                            <td><?= htmlspecialchars($log['usuario_nome']) ?></td>
                            <td><?= htmlspecialchars(ucfirst($log['tabela'])) ?></td>
                            <td><?= $log['registro_id'] ?></td>
                            <td><?= htmlspecialchars($log['campo_alterado']) ?></td>
                            <td>
                                <div class="log-diff">
                                    <small><strong>Anterior:</strong></small><br>
                                    <span class="valor-anterior"><?= $log['valor_anterior'] ? htmlspecialchars($log['valor_anterior']) : '<em>Vazio</em>' ?></span><br>
                                    <small><strong>Novo:</strong></small><br>
                                    <span class="valor-novo"><?= $log['valor_novo'] ? htmlspecialchars($log['valor_novo']) : '<em>Vazio</em>' ?></span>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginação -->
        <?php if ($total_pages > 1): ?>
            <nav class="pagination-container">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $page - 1 ?><?= $filtro_usuario ? '&usuario=' . urlencode($filtro_usuario) : '' ?><?= $filtro_tabela ? '&tabela=' . urlencode($filtro_tabela) : '' ?><?= $filtro_data ? '&data=' . urlencode($filtro_data) : '' ?>">Anterior</a>
                        </li>
                    <?php endif; ?>

                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?><?= $filtro_usuario ? '&usuario=' . urlencode($filtro_usuario) : '' ?><?= $filtro_tabela ? '&tabela=' . urlencode($filtro_tabela) : '' ?><?= $filtro_data ? '&data=' . urlencode($filtro_data) : '' ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $page + 1 ?><?= $filtro_usuario ? '&usuario=' . urlencode($filtro_usuario) : '' ?><?= $filtro_tabela ? '&tabela=' . urlencode($filtro_tabela) : '' ?><?= $filtro_data ? '&data=' . urlencode($filtro_data) : '' ?>">Próxima</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>

    <?php else: ?>
        <div class="alert alert-info">
            <strong>Nenhum log encontrado</strong><br>
            Não há registros de alterações para os filtros aplicados.
        </div>
    <?php endif; ?>

</div>
<?php include 'includes/footer.php'; ?>
</body>
</html>