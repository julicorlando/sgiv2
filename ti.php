<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
include 'includes/db.php';

$mensagem = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = $_POST['titulo'];
    $descricao = $_POST['descricao'];
    $usuario_id = $_SESSION['usuario_id'];
    $status = "Aberto";
    $data_criacao = date('Y-m-d H:i:s');

    // Salva o chamado de TI
    $sql = "INSERT INTO solicitacoes_ti (titulo, descricao, usuario_id, status, data_criacao) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssiss", $titulo, $descricao, $usuario_id, $status, $data_criacao);

    if ($stmt->execute()) {
        $mensagem = '<div class="alert alert-success">Solicitação registrada com sucesso!</div>';
    } else {
        $mensagem = '<div class="alert alert-danger">Erro ao registrar: ' . $conn->error . '</div>';
    }
    $stmt->close();
}

// Buscar solicitações do usuário logado
$sql_list = "SELECT s.*, u.usuario FROM solicitacoes_ti s LEFT JOIN usuarios u ON s.usuario_id = u.id ORDER BY s.data_criacao DESC";
$res_list = $conn->query($sql_list);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Solicitações de TI</title>
    <link rel="stylesheet" href="assets/bootstrap.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        .status-badge {
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 0.95em;
        }
        .status-aberto { background: #ffc107; color: #222; }
        .status-andamento { background: #17a2b8; color: #fff; }
        .status-fechado { background: #28a745; color: #fff; }
    </style>
</head>
<body>
<?php include 'includes/menu.php'; ?>
<div class="container mt-4">
    <h2>Solicitações de TI</h2>
    <?php if ($mensagem) echo $mensagem; ?>
    <div class="card mb-4">
        <div class="card-header"><b>Nova Solicitação</b></div>
        <div class="card-body">
            <form method="post" class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Título</label>
                    <input type="text" class="form-control" name="titulo" maxlength="100" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Descrição detalhada</label>
                    <textarea class="form-control" name="descricao" rows="4" maxlength="1000" required></textarea>
                </div>
                <div class="col-12">
                    <button class="btn btn-success">Enviar Solicitação</button>
                </div>
            </form>
        </div>
    </div>

    <h4 class="mb-3">Minhas solicitações e histórico</h4>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Título</th>
                <th>Descrição</th>
                <th>Status</th>
                <th>Data/Hora</th>
                <th>Solicitante</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($res_list && $res_list->num_rows > 0): ?>
                <?php while ($r = $res_list->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['titulo']) ?></td>
                        <td><?= nl2br(htmlspecialchars($r['descricao'])) ?></td>
                        <td>
                            <span class="status-badge status-<?= strtolower(str_replace(' ', '', $r['status'])) ?>">
                                <?= htmlspecialchars($r['status']) ?>
                            </span>
                        </td>
                        <td><?= date('d/m/Y H:i', strtotime($r['data_criacao'])) ?></td>
                        <td><?= htmlspecialchars($r['usuario']) ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5">Nenhuma solicitação registrada ainda.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php include 'includes/footer.php'; ?>
</body>
</html>