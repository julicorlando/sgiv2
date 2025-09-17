<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
include 'includes/db.php';

// Verifica se o usuário logado é funcionário e pega o nome cadastrado
$stmt = $conn->prepare("SELECT tipo, nome FROM usuarios WHERE id=?");
$stmt->bind_param("i", $_SESSION['usuario_id']);
$stmt->execute();
$stmt->bind_result($tipo_usuario, $nome_funcionario);
$stmt->fetch();
$stmt->close();

if ($tipo_usuario !== 'funcionario') {
    echo "<div class='alert alert-danger'>Acesso restrito! Apenas funcionários podem acessar esta área.</div>";
    exit;
}

// Adiciona descrição ao serviço (nova funcionalidade)
if (isset($_POST['descricao_servico']) && isset($_POST['servico_id'])) {
    $id = intval($_POST['servico_id']);
    $descricao = trim($_POST['descricao_servico']);
    $autor = $nome_funcionario;
    if ($descricao !== '') {
        $stmt = $conn->prepare("UPDATE manutencao SET anotacoes = CONCAT(IFNULL(anotacoes,''), CONCAT('\n\n[" . $autor . "] ', ?)) WHERE id=? AND funcionario_id=?");
        $stmt->bind_param("sii", $descricao, $id, $_SESSION['usuario_id']);
        $stmt->execute();
        $stmt->close();
        echo "<div class='alert alert-success'>Descrição adicional salva com sucesso!</div>";
    }
}

// Marcar serviço como feito (finalizar)
if (isset($_POST['finalizar']) && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $stmt = $conn->prepare("UPDATE manutencao SET status='Finalizado' WHERE id=? AND funcionario_id=?");
    $stmt->bind_param("ii", $id, $_SESSION['usuario_id']);
    $stmt->execute();
    $stmt->close();
    echo "<div class='alert alert-success'>Serviço marcado como feito!</div>";
}

// --- AJAX: Endpoints para atualização automática ---
if (isset($_GET['ajax']) && $_GET['ajax'] == 'pendentes') {
    $sqlPendentes = "SELECT m.*, u.nome AS solicitante 
        FROM manutencao m 
        LEFT JOIN usuarios u ON m.usuario_id = u.id 
        WHERE m.funcionario_id=? AND m.status <> 'Finalizado' 
        ORDER BY m.data_criacao DESC";
    $stmtPendentes = $conn->prepare($sqlPendentes);
    $stmtPendentes->bind_param("i", $_SESSION['usuario_id']);
    $stmtPendentes->execute();
    $resultPendentes = $stmtPendentes->get_result();
    ob_start();
    ?>
    <?php if ($resultPendentes->num_rows > 0): ?>
        <?php while ($row = $resultPendentes->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['natureza']) ?></td>
            <td><?= htmlspecialchars($row['local']) ?></td>
            <td><?= htmlspecialchars($row['acao']) ?></td>
            <td><?= nl2br(htmlspecialchars($row['anotacoes'])) ?></td>
            <td><?= htmlspecialchars($row['solicitante']) ?></td>
            <td>
                <?php
                $badge = "secondary";
                if ($row['status'] === 'Aberto') $badge = "warning";
                elseif ($row['status'] === 'Em andamento') $badge = "info";
                elseif ($row['status'] === 'Pendente') $badge = "danger";
                ?>
                <span class="badge bg-<?= $badge ?>">
                    <?= htmlspecialchars($row['status']) ?>
                </span>
            </td>
            <td><?= date('d/m/Y H:i', strtotime($row['data_criacao'])) ?></td>
            <td>
                <?php if (!empty($row['data_programada'])): ?>
                    <?= date('d/m/Y', strtotime($row['data_programada'])) ?>
                <?php else: ?>
                    <em>-</em>
                <?php endif; ?>
            </td>
            <td>
                <!-- Botão para ver detalhes do serviço (abre modal) -->
                <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#modalServico<?= $row['id'] ?>">Ver</button>
                <!-- Modal (o modal não é renderizado no AJAX, mas pode ser renderizado no carregamento inicial ou via JS se desejar) -->
            </td>
            <td>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                    <button type="submit" name="finalizar" class="btn btn-success btn-sm">Marcar como Feito</button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr><td colspan="10" class="text-center">Nenhum serviço pendente atribuído a você no momento.</td></tr>
    <?php endif;
    echo ob_get_clean();
    exit;
}

// Buscar serviços atribuídos ao funcionário logado (pendentes)
$sqlPendentes = "SELECT m.*, u.nome AS solicitante 
    FROM manutencao m 
    LEFT JOIN usuarios u ON m.usuario_id = u.id 
    WHERE m.funcionario_id=? AND m.status <> 'Finalizado' 
    ORDER BY m.data_criacao DESC";
$stmtPendentes = $conn->prepare($sqlPendentes);
$stmtPendentes->bind_param("i", $_SESSION['usuario_id']);
$stmtPendentes->execute();
$resultPendentes = $stmtPendentes->get_result();

// Buscar histórico de serviços (finalizados)
$sqlHistorico = "SELECT m.*, u.nome AS solicitante 
    FROM manutencao m 
    LEFT JOIN usuarios u ON m.usuario_id = u.id 
    WHERE m.funcionario_id=? AND m.status = 'Finalizado' 
    ORDER BY m.data_criacao DESC";
$stmtHistorico = $conn->prepare($sqlHistorico);
$stmtHistorico->bind_param("i", $_SESSION['usuario_id']);
$stmtHistorico->execute();
$resultHistorico = $stmtHistorico->get_result();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Área do Funcionário - Serviços de Manutenção</title>
    <link rel="stylesheet" href="assets/bootstrap.min.css">
    <style>
        .table-responsive { margin-bottom: 2rem; }
        .modal-footer .form-control { max-width: 350px; }
    </style>
</head>
<body>
<?php include 'includes/menu.php'; ?>
<div class="container mt-4">
    <h2>Meus Serviços de Manutenção</h2>
    <p>Olá, <strong><?= htmlspecialchars($nome_funcionario) ?></strong>. Aqui estão os serviços atribuídos a você.</p>

    <!-- Serviços Pendentes -->
    <div class="table-responsive">
        <h4>Serviços Pendentes</h4>
        <table class="table table-bordered table-striped">
            <thead class="table-primary">
                <tr>
                    <th>Natureza</th>
                    <th>Local</th>
                    <th>Ação</th>
                    <th>Anotações</th>
                    <th>Solicitante</th>
                    <th>Status</th>
                    <th>Data</th>
                    <th>Data Programada</th>
                    <th>Ver Serviço</th>
                    <th>Marcar como Feito</th>
                </tr>
            </thead>
            <tbody id="pendentesBody">
            <?php if ($resultPendentes->num_rows > 0): ?>
                <?php while ($row = $resultPendentes->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['natureza']) ?></td>
                    <td><?= htmlspecialchars($row['local']) ?></td>
                    <td><?= htmlspecialchars($row['acao']) ?></td>
                    <td><?= nl2br(htmlspecialchars($row['anotacoes'])) ?></td>
                    <td><?= htmlspecialchars($row['solicitante']) ?></td>
                    <td>
                        <?php
                        $badge = "secondary";
                        if ($row['status'] === 'Aberto') $badge = "warning";
                        elseif ($row['status'] === 'Em andamento') $badge = "info";
                        elseif ($row['status'] === 'Pendente') $badge = "danger";
                        ?>
                        <span class="badge bg-<?= $badge ?>">
                            <?= htmlspecialchars($row['status']) ?>
                        </span>
                    </td>
                    <td><?= date('d/m/Y H:i', strtotime($row['data_criacao'])) ?></td>
                    <td>
                        <?php if (!empty($row['data_programada'])): ?>
                            <?= date('d/m/Y', strtotime($row['data_programada'])) ?>
                        <?php else: ?>
                            <em>-</em>
                        <?php endif; ?>
                    </td>
                    <td>
                        <!-- Botão para ver detalhes do serviço (abre modal) -->
                        <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#modalServico<?= $row['id'] ?>">Ver</button>
                        <!-- Modal -->
                        <div class="modal fade" id="modalServico<?= $row['id'] ?>" tabindex="-1" aria-labelledby="modalServicoLabel<?= $row['id'] ?>" aria-hidden="true">
                          <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                              <div class="modal-header">
                                <h5 class="modal-title" id="modalServicoLabel<?= $row['id'] ?>">Detalhes do Serviço</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                              </div>
                              <div class="modal-body">
                                <strong>Natureza:</strong> <?= htmlspecialchars($row['natureza']) ?><br>
                                <strong>Local:</strong> <?= htmlspecialchars($row['local']) ?><br>
                                <strong>Ação:</strong> <?= htmlspecialchars($row['acao']) ?><br>
                                <strong>Anotações:</strong> <?= nl2br(htmlspecialchars($row['anotacoes'])) ?><br>
                                <strong>Solicitante:</strong> <?= htmlspecialchars($row['solicitante']) ?><br>
                                <strong>Status:</strong> <?= htmlspecialchars($row['status']) ?><br>
                                <strong>Data de Criação:</strong> <?= date('d/m/Y H:i', strtotime($row['data_criacao'])) ?><br>
                                <strong>Data Programada:</strong>
                                <?= !empty($row['data_programada']) ? date('d/m/Y', strtotime($row['data_programada'])) : '<em>-</em>' ?><br>
                                <?php if (!empty($row['codigo_aparelho'])): ?>
                                    <strong>Código do Aparelho:</strong> <?= htmlspecialchars($row['codigo_aparelho']) ?><br>
                                <?php endif; ?>
                                <hr>
                                <!-- Formulário para adicionar descrição adicional -->
                                <form method="post" class="mt-2">
                                    <input type="hidden" name="servico_id" value="<?= $row['id'] ?>">
                                    <label for="descricao_servico<?= $row['id'] ?>" class="form-label">Adicionar descrição/justificativa:</label>
                                    <textarea id="descricao_servico<?= $row['id'] ?>" name="descricao_servico" class="form-control mb-2" rows="2" maxlength="255" placeholder="Ex: Não foi executado por falta de tinta verde"></textarea>
                                    <button type="submit" class="btn btn-sm btn-primary">Salvar Descrição</button>
                                </form>
                              </div>
                              <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                              </div>
                            </div>
                          </div>
                        </div>
                    </td>
                    <td>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                            <button type="submit" name="finalizar" class="btn btn-success btn-sm">Marcar como Feito</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="10" class="text-center">Nenhum serviço pendente atribuído a você no momento.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Histórico de Serviços Finalizados -->
    <div class="table-responsive">
        <h4>Histórico de Serviços Finalizados</h4>
        <table class="table table-bordered table-striped">
            <thead class="table-success">
                <tr>
                    <th>Natureza</th>
                    <th>Local</th>
                    <th>Ação</th>
                    <th>Anotações</th>
                    <th>Solicitante</th>
                    <th>Status</th>
                    <th>Data</th>
                    <th>Data Programada</th>
                    <th>Ver Serviço</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($resultHistorico->num_rows > 0): ?>
                <?php while ($row = $resultHistorico->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['natureza']) ?></td>
                    <td><?= htmlspecialchars($row['local']) ?></td>
                    <td><?= htmlspecialchars($row['acao']) ?></td>
                    <td><?= nl2br(htmlspecialchars($row['anotacoes'])) ?></td>
                    <td><?= htmlspecialchars($row['solicitante']) ?></td>
                    <td>
                        <span class="badge bg-success"><?= htmlspecialchars($row['status']) ?></span>
                    </td>
                    <td><?= date('d/m/Y H:i', strtotime($row['data_criacao'])) ?></td>
                    <td>
                        <?php if (!empty($row['data_programada'])): ?>
                            <?= date('d/m/Y', strtotime($row['data_programada'])) ?>
                        <?php else: ?>
                            <em>-</em>
                        <?php endif; ?>
                    </td>
                    <td>
                        <!-- Botão para ver detalhes do serviço (abre modal) -->
                        <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#modalHistorico<?= $row['id'] ?>">Ver</button>
                        <!-- Modal -->
                        <div class="modal fade" id="modalHistorico<?= $row['id'] ?>" tabindex="-1" aria-labelledby="modalHistoricoLabel<?= $row['id'] ?>" aria-hidden="true">
                          <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                              <div class="modal-header">
                                <h5 class="modal-title" id="modalHistoricoLabel<?= $row['id'] ?>">Detalhes do Serviço Finalizado</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                              </div>
                              <div class="modal-body">
                                <strong>Natureza:</strong> <?= htmlspecialchars($row['natureza']) ?><br>
                                <strong>Local:</strong> <?= htmlspecialchars($row['local']) ?><br>
                                <strong>Ação:</strong> <?= htmlspecialchars($row['acao']) ?><br>
                                <strong>Anotações:</strong> <?= nl2br(htmlspecialchars($row['anotacoes'])) ?><br>
                                <strong>Solicitante:</strong> <?= htmlspecialchars($row['solicitante']) ?><br>
                                <strong>Status:</strong> <?= htmlspecialchars($row['status']) ?><br>
                                <strong>Data de Criação:</strong> <?= date('d/m/Y H:i', strtotime($row['data_criacao'])) ?><br>
                                <strong>Data Programada:</strong>
                                <?= !empty($row['data_programada']) ? date('d/m/Y', strtotime($row['data_programada'])) : '<em>-</em>' ?><br>
                                <?php if (!empty($row['codigo_aparelho'])): ?>
                                    <strong>Código do Aparelho:</strong> <?= htmlspecialchars($row['codigo_aparelho']) ?><br>
                                <?php endif; ?>
                              </div>
                              <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                              </div>
                            </div>
                          </div>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="9" class="text-center">Nenhum serviço finalizado no seu histórico.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- AJAX para atualizar serviços pendentes automaticamente -->
<script>
function atualizarPendentes() {
    fetch('?ajax=pendentes')
        .then(res => res.text())
        .then(html => {
            document.getElementById('pendentesBody').innerHTML = html;
        });
}
// Atualiza a cada 10 segundos
setInterval(atualizarPendentes, 10000);
</script>
</body>
</html>