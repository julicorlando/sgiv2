<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

include 'includes/db.php';

function status_badge($status) {
    switch($status) {
        case 'Aberto': return '<span class="badge bg-warning">Aberto</span>';
        case 'Em andamento': return '<span class="badge bg-info">Em andamento</span>';
        case 'Pendente': return '<span class="badge bg-danger">Pendente</span>';
        case 'Finalizado': return '<span class="badge bg-success">Finalizado</span>';
        default: return '<span class="badge bg-secondary">'.htmlspecialchars($status).'</span>';
    }
}

$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : 'todos';

// FILTRO MM/AAAA
$mes_ano = isset($_GET['mes_ano']) ? $_GET['mes_ano'] : '';
$where_data = '';
if ($mes_ano && preg_match('/^\d{2}\/\d{4}$/', $mes_ano)) {
    list($mm, $yyyy) = explode('/', $mes_ano);
    if (checkdate($mm, 1, $yyyy)) {
        $where_data = " AND DATE_FORMAT(data_criacao, '%m/%Y') = '".sprintf("%02d",$mm)."/$yyyy' ";
    }
}

// Consultas para manutenção e limpeza
$sql_manut = "SELECT m.id, m.natureza, m.local, m.acao, m.anotacoes, m.status, m.foto, m.data_criacao, u.usuario as usuario_nome 
              FROM manutencao m 
              LEFT JOIN usuarios u ON m.usuario_id = u.id";
if ($tipo == 'manutencao') {
    $sql_manut .= " WHERE 1=1 $where_data";
} elseif ($tipo == 'limpeza') {
    $sql_manut = false;
}

$sql_limp = "SELECT l.id, l.local, l.acao, l.anotacoes, l.status, l.foto, l.data_criacao, u.usuario as usuario_nome 
             FROM limpeza l 
             LEFT JOIN usuarios u ON l.usuario_id = u.id";
if ($tipo == 'limpeza') {
    $sql_limp .= " WHERE 1=1 $where_data";
} elseif ($tipo == 'manutencao') {
    $sql_limp = false;
}

// Consulta para TI
$sql_ti = "SELECT s.id, s.titulo, s.descricao, s.status, s.data_criacao, u.usuario as usuario_nome
           FROM solicitacoes_ti s
           LEFT JOIN usuarios u ON s.usuario_id = u.id";
if ($tipo == 'ti') {
    $sql_manut = false;
    $sql_limp = false;
    $sql_ti .= " WHERE 1=1 $where_data";
} elseif ($tipo != 'todos') {
    $sql_ti = false;
}
if ($tipo == 'todos' && $where_data) {
    $sql_manut .= " WHERE 1=1 $where_data";
    $sql_limp .= " WHERE 1=1 $where_data";
    $sql_ti .= " WHERE 1=1 $where_data";
}

// Visualização de um registro específico
$mostrar = false;
$info = [];
$origem = '';
$fotos_anexos = [];
$assinatura_img = '';

if (isset($_GET['view']) && isset($_GET['id'])) {
    $mostrar = true;
    $id = intval($_GET['id']);
    $origem = $_GET['view'];

    if ($origem == 'manutencao') {
        $sqlv = "SELECT m.*, u.usuario as usuario_nome FROM manutencao m LEFT JOIN usuarios u ON m.usuario_id = u.id WHERE m.id = ?";
    } elseif ($origem == 'limpeza') {
        $sqlv = "SELECT l.*, u.usuario as usuario_nome FROM limpeza l LEFT JOIN usuarios u ON l.usuario_id = u.id WHERE l.id = ?";
    } elseif ($origem == 'ti') {
        $sqlv = "SELECT s.*, u.usuario as usuario_nome FROM solicitacoes_ti s LEFT JOIN usuarios u ON s.usuario_id = u.id WHERE s.id = ?";
    }
    $stmt = $conn->prepare($sqlv);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $info = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Buscar anexos para manutenção/limpeza
    if ($origem == 'manutencao') {
        $anexos_stmt = $conn->prepare("SELECT caminho FROM manutencao_fotos WHERE manutencao_id = ?");
        $anexos_stmt->bind_param("i", $id);
        $anexos_stmt->execute();
        $anexos_result = $anexos_stmt->get_result();
        while ($row = $anexos_result->fetch_assoc()) {
            $fotos_anexos[] = $row['caminho'];
        }
        $anexos_stmt->close();

        // Mostrar assinatura se natureza for ar-condicionado
        if (isset($info['natureza']) && mb_strtolower($info['natureza']) === 'ar-condicionado') {
            // Procurar assinatura pelo padrão de nome
            $assinaturas = glob("uploads/assinatura_*");
            $id_found = false;
            foreach ($assinaturas as $ass_path) {
                // Tenta buscar assinatura pelo id ou por ordem de criação (ajuste conforme seu método de salvar)
                // Aqui, como não temos o caminho salvo no banco, exibe a mais recente
                if (!$id_found && file_exists($ass_path)) {
                    $assinatura_img = $ass_path;
                    $id_found = true;
                }
            }
        }
    } elseif ($origem == 'limpeza') {
        $anexos_stmt = $conn->prepare("SELECT caminho FROM limpeza_fotos WHERE limpeza_id = ?");
        $anexos_stmt->bind_param("i", $id);
        $anexos_stmt->execute();
        $anexos_result = $anexos_stmt->get_result();
        while ($row = $anexos_result->fetch_assoc()) {
            $fotos_anexos[] = $row['caminho'];
        }
        $anexos_stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Histórico de Inspeções e Solicitações</title>
    <link rel="stylesheet" href="assets/bootstrap.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        .foto-thumb { max-width: 60px; max-height:60px; border-radius:6px; }
        .modal-img { max-width: 100%; border-radius:10px; }
        .anexo-img { max-width:120px; max-height:120px; border-radius:8px; margin:3px; cursor:pointer; transition: box-shadow 0.2s; }
        .anexo-img:hover { box-shadow: 0 0 10px #333; }
        .assinatura-img { max-width: 300px; max-height: 120px; border-radius: 8px; border: 1px solid #ccc; background: #fff; margin-bottom: 12px;}
        .badge-ti { background: #3a6ea5; color: #fff; }
        .modal-backdrop-custom {
            position: fixed; top:0; left:0; right:0; bottom:0;
            background: rgba(0,0,0,0.7); z-index: 1040; display: none;
            align-items: center; justify-content: center;
        }
        .modal-backdrop-custom.show { display: flex; }
        .modal-img-large { max-width: 90vw; max-height: 90vh; border-radius: 12px; box-shadow: 0 0 20px #222; background: #fff; padding: 8px; }
        .modal-close-btn { position: absolute; top: 24px; right: 36px; font-size: 2.2rem; color: #fff; background: none; border: none; font-weight: bold; cursor: pointer; z-index: 1100; }
    </style>
</head>
<body>
<?php include 'includes/menu.php'; ?>
<div class="container mt-4">
    <h2>Histórico de Inspeções e Solicitações</h2>
    <form method="get" class="mb-3 row g-2">
        <div class="col-auto">
            <label for="mes_ano" class="col-form-label">Filtrar por MM/AAAA:</label>
        </div>
        <div class="col-auto">
            <input type="text" name="mes_ano" id="mes_ano" class="form-control" placeholder="MM/AAAA" value="<?= htmlspecialchars($mes_ano) ?>" maxlength="7" pattern="\d{2}/\d{4}">
        </div>
        <div class="col-auto">
            <input type="hidden" name="tipo" value="<?= htmlspecialchars($tipo) ?>">
            <button type="submit" class="btn btn-primary">Filtrar</button>
        </div>
        <?php if($mes_ano): ?>
            <div class="col-auto">
                <a href="?tipo=<?= htmlspecialchars($tipo) ?>" class="btn btn-secondary">Limpar Filtro</a>
            </div>
        <?php endif; ?>
    </form>
    <div class="mb-3">
        <a href="?tipo=todos" class="btn btn-outline-primary btn-sm <?= $tipo=='todos'?'active':'' ?>">Todos</a>
        <a href="?tipo=manutencao" class="btn btn-outline-primary btn-sm <?= $tipo=='manutencao'?'active':'' ?>">Manutenção</a>
        <a href="?tipo=limpeza" class="btn btn-outline-primary btn-sm <?= $tipo=='limpeza'?'active':'' ?>">Limpeza</a>
        <a href="?tipo=ti" class="btn btn-outline-primary btn-sm <?= $tipo=='ti'?'active':'' ?>">TI</a>
    </div>

    <?php if($mostrar && $info): ?>
        <div class="card mb-4">
            <div class="card-header">
                <b>Informações detalhadas</b>
            </div>
            <div class="card-body" id="print-section">
                <?php if($origem == 'manutencao'): ?>
                    <p><b>Natureza:</b> <?= htmlspecialchars($info['natureza']) ?></p>
                    <p><b>Local/Equipamento:</b> <?= htmlspecialchars($info['local']) ?></p>
                    <p><b>Ação:</b> <?= htmlspecialchars($info['acao']) ?></p>
                    <p><b>Status:</b> <?= status_badge($info['status']) ?></p>
                    <p><b>Anotações:</b> <br><?= nl2br(htmlspecialchars($info['anotacoes'])) ?></p>
                    <?php if(!empty($info['foto'])): ?>
                        <p><b>Foto:</b><br>
                            <img src="<?= htmlspecialchars($info['foto']) ?>" class="modal-img" alt="Foto">
                        </p>
                    <?php endif; ?>
                    <?php if(count($fotos_anexos)): ?>
                        <p><b>Anexos:</b><br>
                            <?php foreach($fotos_anexos as $img): ?>
                                <img src="<?= htmlspecialchars($img) ?>" class="anexo-img" alt="Anexo" onclick="showModal('<?= htmlspecialchars($img) ?>')">
                            <?php endforeach; ?>
                        </p>
                    <?php endif; ?>
                    <p><b>Data:</b> <?= date('d/m/Y H:i', strtotime($info['data_criacao'])) ?></p>
                    <p><b>Solicitado por:</b> <?= !empty($info['usuario_nome']) ? htmlspecialchars($info['usuario_nome']) : '<em>Desconhecido</em>' ?></p>
                    <?php if(isset($info['natureza']) && mb_strtolower($info['natureza']) === 'ar-condicionado' && $assinatura_img && file_exists($assinatura_img)): ?>
                        <p><b>Assinatura do responsável:</b><br>
                            <img src="<?= htmlspecialchars($assinatura_img) ?>" class="assinatura-img" alt="Assinatura">
                        </p>
                    <?php endif; ?>
                    <?php if($info['status'] == 'Finalizado'): ?>
                        <div class="alert alert-success">Registro concluído. Não é possível editar.</div>
                    <?php else: ?>
                        <a href="editar.php?tipo=manutencao&id=<?= $info['id'] ?>" class="btn btn-warning">Editar</a>
                    <?php endif; ?>
                <?php elseif($origem == 'limpeza'): ?>
                    <p><b>Local:</b> <?= htmlspecialchars($info['local']) ?></p>
                    <p><b>Ação:</b> <?= htmlspecialchars($info['acao']) ?></p>
                    <p><b>Status:</b> <?= status_badge($info['status']) ?></p>
                    <p><b>Anotações:</b> <br><?= nl2br(htmlspecialchars($info['anotacoes'])) ?></p>
                    <?php if(!empty($info['foto'])): ?>
                        <p><b>Foto:</b><br>
                            <img src="<?= htmlspecialchars($info['foto']) ?>" class="modal-img" alt="Foto">
                        </p>
                    <?php endif; ?>
                    <?php if(count($fotos_anexos)): ?>
                        <p><b>Anexos:</b><br>
                            <?php foreach($fotos_anexos as $img): ?>
                                <img src="<?= htmlspecialchars($img) ?>" class="anexo-img" alt="Anexo" onclick="showModal('<?= htmlspecialchars($img) ?>')">
                            <?php endforeach; ?>
                        </p>
                    <?php endif; ?>
                    <p><b>Data:</b> <?= date('d/m/Y H:i', strtotime($info['data_criacao'])) ?></p>
                    <p><b>Solicitado por:</b> <?= !empty($info['usuario_nome']) ? htmlspecialchars($info['usuario_nome']) : '<em>Desconhecido</em>' ?></p>
                    <?php if($info['status'] == 'Finalizado'): ?>
                        <div class="alert alert-success">Registro concluído. Não é possível editar.</div>
                    <?php else: ?>
                        <a href="editar.php?tipo=limpeza&id=<?= $info['id'] ?>" class="btn btn-warning">Editar</a>
                    <?php endif; ?>
                <?php elseif($origem == 'ti'): ?>
                    <p><b>Título:</b> <?= htmlspecialchars($info['titulo']) ?></p>
                    <p><b>Descrição:</b><br><?= nl2br(htmlspecialchars($info['descricao'])) ?></p>
                    <p><b>Status:</b> <?= status_badge($info['status']) ?></p>
                    <p><b>Data:</b> <?= date('d/m/Y H:i', strtotime($info['data_criacao'])) ?></p>
                    <p><b>Solicitado por:</b> <?= !empty($info['usuario_nome']) ? htmlspecialchars($info['usuario_nome']) : '<em>Desconhecido</em>' ?></p>
                    <?php if($info['status'] == 'Finalizado'): ?>
                        <div class="alert alert-success">Registro concluído. Não é possível editar.</div>
                    <?php else: ?>
                        <a href="editar.php?tipo=ti&id=<?= $info['id'] ?>" class="btn btn-warning">Editar</a>
                    <?php endif; ?>
                <?php endif; ?>
                <a href="historico.php?tipo=<?= $origem ?>" class="btn btn-secondary">Voltar</a>
                <button onclick="printDetails()" class="btn btn-primary ms-2">Imprimir</button>
            </div>
        </div>
        <div class="modal-backdrop-custom" id="imgModal" onclick="hideModal()">
            <button class="modal-close-btn" onclick="hideModal(event)">&times;</button>
            <img src="" id="modalImg" class="modal-img-large" alt="Anexo Ampliado">
        </div>
        <script>
        function showModal(imgSrc) {
            document.getElementById('modalImg').src = imgSrc;
            document.getElementById('imgModal').classList.add('show');
        }
        function hideModal(e) {
            if (e) e.stopPropagation();
            document.getElementById('imgModal').classList.remove('show');
            document.getElementById('modalImg').src = '';
        }
        function printDetails() {
            var printContents = document.getElementById('print-section').innerHTML;
            var originalContents = document.body.innerHTML;
            document.body.innerHTML = printContents;
            window.print();
            document.body.innerHTML = originalContents;
            location.reload();
        }
        </script>
    <?php endif; ?>

    <?php if(!$mostrar): ?>

        <!-- Manutenção -->
        <?php if($sql_manut): 
            $result = $conn->query($sql_manut . " ORDER BY data_criacao DESC");
            if ($result && $result->num_rows > 0): ?>
                <h5>Manutenção</h5>
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Natureza</th>
                            <th>Local</th>
                            <th>Anotações</th>
                            <th>Status</th>
                            <th>Data</th>
                            <th>Foto</th>
                            <th>Solicitante</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while($r = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['natureza']) ?></td>
                            <td><?= htmlspecialchars($r['local']) ?></td>
                            <td><?= nl2br(htmlspecialchars($r['anotacoes'])) ?></td>
                            <td><?= status_badge($r['status']) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($r['data_criacao'])) ?></td>
                            <td>
                                <?php if(!empty($r['foto'])): ?>
                                    <img src="<?= htmlspecialchars($r['foto']) ?>" class="foto-thumb" alt="Foto">
                                <?php endif; ?>
                            </td>
                            <td><?= !empty($r['usuario_nome']) ? htmlspecialchars($r['usuario_nome']) : '<em>Desconhecido</em>' ?></td>
                            <td>
                                <a href="?view=manutencao&id=<?= $r['id'] ?>" class="btn btn-sm btn-info">Ver</a>
                                <?php if($r['status'] != 'Finalizado'): ?>
                                    <a href="editar.php?tipo=manutencao&id=<?= $r['id'] ?>" class="btn btn-sm btn-warning">Editar</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Nenhum registro de manutenção encontrado.</p>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Limpeza -->
        <?php if($sql_limp): 
            $result = $conn->query($sql_limp . " ORDER BY data_criacao DESC");
            if ($result && $result->num_rows > 0): ?>
                <h5>Limpeza</h5>
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Local</th>
                            <th>Anotações</th>
                            <th>Status</th>
                            <th>Data</th>
                            <th>Foto</th>
                            <th>Solicitante</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while($r = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['local']) ?></td>
                            <td><?= nl2br(htmlspecialchars($r['anotacoes'])) ?></td>
                            <td><?= status_badge($r['status']) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($r['data_criacao'])) ?></td>
                            <td>
                                <?php if(!empty($r['foto'])): ?>
                                    <img src="<?= htmlspecialchars($r['foto']) ?>" class="foto-thumb" alt="Foto">
                                <?php endif; ?>
                            </td>
                            <td><?= !empty($r['usuario_nome']) ? htmlspecialchars($r['usuario_nome']) : '<em>Desconhecido</em>' ?></td>
                            <td>
                                <a href="?view=limpeza&id=<?= $r['id'] ?>" class="btn btn-sm btn-info">Ver</a>
                                <?php if($r['status'] != 'Finalizado'): ?>
                                    <a href="editar.php?tipo=limpeza&id=<?= $r['id'] ?>" class="btn btn-sm btn-warning">Editar</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Nenhum registro de limpeza encontrado.</p>
            <?php endif; ?>
        <?php endif; ?>

        <!-- TI -->
        <?php if($sql_ti): 
            $result = $conn->query($sql_ti . " ORDER BY data_criacao DESC");
            if ($result && $result->num_rows > 0): ?>
                <h5>Solicitações de TI</h5>
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Título</th>
                            <th>Descrição</th>
                            <th>Status</th>
                            <th>Data</th>
                            <th>Solicitante</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while($r = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['titulo']) ?></td>
                            <td><?= nl2br(htmlspecialchars($r['descricao'])) ?></td>
                            <td><?= status_badge($r['status']) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($r['data_criacao'])) ?></td>
                            <td><?= !empty($r['usuario_nome']) ? htmlspecialchars($r['usuario_nome']) : '<em>Desconhecido</em>' ?></td>
                            <td>
                                <a href="?view=ti&id=<?= $r['id'] ?>" class="btn btn-sm btn-info">Ver</a>
                                <?php if($r['status'] != 'Finalizado'): ?>
                                    <a href="editar.php?tipo=ti&id=<?= $r['id'] ?>" class="btn btn-sm btn-warning">Editar</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Nenhuma solicitação de TI encontrada.</p>
            <?php endif; ?>
        <?php endif; ?>

    <?php endif; ?>
</div>
<?php include 'includes/footer.php'; ?>
</body>
</html>