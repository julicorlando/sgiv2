<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

include 'includes/db.php';

// Recupera o tipo do usuário
$tipo_usuario = 'padrao';
if (isset($_SESSION['usuario_id'])) {
    $stmt = $conn->prepare("SELECT tipo FROM usuarios WHERE id=?");
    $stmt->bind_param("i", $_SESSION['usuario_id']);
    $stmt->execute();
    $stmt->bind_result($tipo_usuario);
    $stmt->fetch();
    $stmt->close();
}

// Verifica se é administrador
if ($tipo_usuario !== 'administrador' && $tipo_usuario !== 'admin') {
    header('Location: index.php');
    exit;
}

// Filtros
$data_inicio = $_GET['data_inicio'] ?? date('Y-m-01'); // Primeiro dia do mês atual
$data_fim = $_GET['data_fim'] ?? date('Y-m-d'); // Hoje
$usuario_filtro = $_GET['usuario'] ?? '';
$produto_filtro = $_GET['produto'] ?? '';

// Query base para movimentações
$sql = "SELECT m.*, p.nome_produto, p.codigo_barras, u.nome as usuario_nome
        FROM estoque_movimentacoes m
        JOIN estoque_produtos p ON m.produto_id = p.id
        JOIN usuarios u ON m.usuario_id = u.id
        WHERE m.data_movimentacao >= ? AND m.data_movimentacao <= ?";

$params = [$data_inicio . ' 00:00:00', $data_fim . ' 23:59:59'];
$types = 'ss';

if ($usuario_filtro) {
    $sql .= " AND u.nome LIKE ?";
    $params[] = '%' . $usuario_filtro . '%';
    $types .= 's';
}

if ($produto_filtro) {
    $sql .= " AND p.nome_produto LIKE ?";
    $params[] = '%' . $produto_filtro . '%';
    $types .= 's';
}

$sql .= " ORDER BY m.data_movimentacao DESC";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$movimentacoes = $stmt->get_result();
$stmt->close();

// Estatísticas do período
$sql_stats = "SELECT 
                COUNT(*) as total_movimentacoes,
                SUM(CASE WHEN tipo_movimentacao = 'saida' THEN quantidade ELSE 0 END) as total_saidas,
                SUM(CASE WHEN tipo_movimentacao = 'entrada' THEN quantidade ELSE 0 END) as total_entradas,
                COUNT(DISTINCT usuario_id) as usuarios_ativos,
                COUNT(DISTINCT produto_id) as produtos_movimentados
              FROM estoque_movimentacoes 
              WHERE data_movimentacao >= ? AND data_movimentacao <= ?";

$stmt_stats = $conn->prepare($sql_stats);
$stmt_stats->bind_param("ss", $data_inicio . ' 00:00:00', $data_fim . ' 23:59:59');
$stmt_stats->execute();
$stats = $stmt_stats->get_result()->fetch_assoc();
$stmt_stats->close();

// Produtos com estoque baixo (menos de 10)
$sql_baixo = "SELECT * FROM estoque_produtos WHERE quantidade_atual < 10 AND ativo = TRUE ORDER BY quantidade_atual ASC";
$produtos_baixo = $conn->query($sql_baixo);

// Usuários mais ativos no período
$sql_usuarios = "SELECT u.nome, COUNT(*) as total_retiradas, SUM(m.quantidade) as total_quantidade
                 FROM estoque_movimentacoes m
                 JOIN usuarios u ON m.usuario_id = u.id
                 WHERE m.tipo_movimentacao = 'saida' 
                   AND m.data_movimentacao >= ? AND m.data_movimentacao <= ?
                 GROUP BY u.id, u.nome
                 ORDER BY total_retiradas DESC
                 LIMIT 10";

$stmt_usuarios = $conn->prepare($sql_usuarios);
$stmt_usuarios->bind_param("ss", $data_inicio . ' 00:00:00', $data_fim . ' 23:59:59');
$stmt_usuarios->execute();
$usuarios_ativos = $stmt_usuarios->get_result();
$stmt_usuarios->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios - Almoxarifado</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .stat-item {
            text-align: center;
            padding: 15px;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        .alert-estoque {
            background-color: #fff3cd;
            border-color: #ffecb5;
            color: #664d03;
        }
        .table-actions {
            min-width: 120px;
        }
        .badge-movimento {
            font-size: 0.8em;
        }
    </style>
</head>
<body>
<?php include 'includes/menu.php'; ?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <h2>📊 Relatórios do Almoxarifado</h2>
            <p class="text-muted">Acompanhe as movimentações e estatísticas do estoque (apenas administradores)</p>
        </div>
    </div>
    
    <!-- Estatísticas do Período -->
    <div class="stats-card">
        <h5 class="text-center mb-4">📈 Estatísticas do Período</h5>
        <div class="row">
            <div class="col-md-2 col-6">
                <div class="stat-item">
                    <div class="stat-number"><?= $stats['total_movimentacoes'] ?></div>
                    <div class="stat-label">Total de Movimentações</div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="stat-item">
                    <div class="stat-number"><?= $stats['total_saidas'] ?></div>
                    <div class="stat-label">Produtos Retirados</div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="stat-item">
                    <div class="stat-number"><?= $stats['total_entradas'] ?></div>
                    <div class="stat-label">Produtos Adicionados</div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="stat-item">
                    <div class="stat-number"><?= $stats['usuarios_ativos'] ?></div>
                    <div class="stat-label">Usuários Ativos</div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="stat-item">
                    <div class="stat-number"><?= $stats['produtos_movimentados'] ?></div>
                    <div class="stat-label">Produtos Movimentados</div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="stat-item">
                    <div class="stat-number"><?= $produtos_baixo->num_rows ?></div>
                    <div class="stat-label">Produtos com Estoque Baixo</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Alertas de Estoque Baixo -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">⚠️ Produtos com Estoque Baixo</h6>
                </div>
                <div class="card-body">
                    <?php if ($produtos_baixo->num_rows > 0): ?>
                        <div class="list-group list-group-flush">
                            <?php while ($produto = $produtos_baixo->fetch_assoc()): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <div>
                                        <strong><?= htmlspecialchars($produto['nome_produto']) ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($produto['codigo_barras']) ?></small>
                                    </div>
                                    <span class="badge bg-warning rounded-pill"><?= $produto['quantidade_atual'] ?></span>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted">
                            <i class="fas fa-check-circle fa-3x mb-3"></i>
                            <p>Todos os produtos têm estoque adequado!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Usuários Mais Ativos -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">👥 Usuários Mais Ativos no Período</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>Usuário</th>
                                    <th>Retiradas</th>
                                    <th>Total de Itens</th>
                                    <th>Média por Retirada</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($usuarios_ativos->num_rows > 0): ?>
                                    <?php while ($usuario = $usuarios_ativos->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($usuario['nome']) ?></td>
                                            <td><span class="badge bg-primary"><?= $usuario['total_retiradas'] ?></span></td>
                                            <td><span class="badge bg-info"><?= $usuario['total_quantidade'] ?></span></td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    <?= number_format($usuario['total_quantidade'] / $usuario['total_retiradas'], 1) ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">Nenhuma movimentação no período</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filtros -->
    <div class="card mt-4">
        <div class="card-header">
            <h6 class="card-title mb-0">🔍 Filtros de Movimentação</h6>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="data_inicio" class="form-label">Data Início</label>
                    <input type="date" class="form-control" id="data_inicio" name="data_inicio" value="<?= $data_inicio ?>">
                </div>
                <div class="col-md-3">
                    <label for="data_fim" class="form-label">Data Fim</label>
                    <input type="date" class="form-control" id="data_fim" name="data_fim" value="<?= $data_fim ?>">
                </div>
                <div class="col-md-3">
                    <label for="usuario" class="form-label">Usuário</label>
                    <input type="text" class="form-control" id="usuario" name="usuario" value="<?= htmlspecialchars($usuario_filtro) ?>" placeholder="Nome do usuário">
                </div>
                <div class="col-md-3">
                    <label for="produto" class="form-label">Produto</label>
                    <input type="text" class="form-control" id="produto" name="produto" value="<?= htmlspecialchars($produto_filtro) ?>" placeholder="Nome do produto">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">🔍 Filtrar</button>
                    <a href="estoque_relatorios.php" class="btn btn-secondary">🔄 Limpar Filtros</a>
                    <button type="button" class="btn btn-success" onclick="exportarCSV()">📥 Exportar CSV</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Movimentações -->
    <div class="card mt-4">
        <div class="card-header">
            <h6 class="card-title mb-0">📋 Movimentações do Estoque</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="tabelaMovimentacoes">
                    <thead>
                        <tr>
                            <th>Data/Hora</th>
                            <th>Tipo</th>
                            <th>Produto</th>
                            <th>Código</th>
                            <th>Quantidade</th>
                            <th>Usuário</th>
                            <th>Observação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($movimentacoes && $movimentacoes->num_rows > 0): ?>
                            <?php while ($mov = $movimentacoes->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <small><?= date('d/m/Y H:i', strtotime($mov['data_movimentacao'])) ?></small>
                                    </td>
                                    <td>
                                        <?php
                                        $badge_class = '';
                                        $badge_text = '';
                                        switch($mov['tipo_movimentacao']) {
                                            case 'saida':
                                                $badge_class = 'bg-danger';
                                                $badge_text = '📤 Saída';
                                                break;
                                            case 'entrada':
                                                $badge_class = 'bg-success';
                                                $badge_text = '📥 Entrada';
                                                break;
                                            case 'ajuste':
                                                $badge_class = 'bg-warning';
                                                $badge_text = '⚙️ Ajuste';
                                                break;
                                        }
                                        ?>
                                        <span class="badge badge-movimento <?= $badge_class ?>"><?= $badge_text ?></span>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($mov['nome_produto']) ?></strong>
                                    </td>
                                    <td>
                                        <code><?= htmlspecialchars($mov['codigo_barras']) ?></code>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?= $mov['quantidade'] ?></span>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($mov['usuario_nome']) ?>
                                    </td>
                                    <td>
                                        <small><?= htmlspecialchars($mov['observacao'] ?: '-') ?></small>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">
                                    Nenhuma movimentação encontrada para os filtros aplicados.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function exportarCSV() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'csv');
    window.location.href = 'estoque_export.php?' + params.toString();
}
</script>

</body>
</html>