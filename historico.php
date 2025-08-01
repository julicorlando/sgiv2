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
        <!-- ... (o bloco já existente para visualização detalhada permanece igual) ... -->
    <?php endif; ?>

    <?php if(!$mostrar): ?>

        <!-- Manutenção -->
        <?php
        $finalizados_manut = [];
        $outros_manut = [];
        if ($sql_manut) {
            $result = $conn->query($sql_manut . " ORDER BY data_criacao DESC");
            if ($result && $result->num_rows > 0) {
                while($r = $result->fetch_assoc()) {
                    if ($r['status'] == 'Finalizado') {
                        $finalizados_manut[] = $r;
                    } else {
                        $outros_manut[] = $r;
                    }
                }
            }
        }
        ?>
        <?php if (!empty($outros_manut)): ?>
            <h5>Manutenção em andamento ou pendente</h5>
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
                <?php foreach($outros_manut as $r): ?>
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
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        <?php if (!empty($finalizados_manut)): ?>
            <h5>Manutenção Finalizada</h5>
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
                <?php foreach($finalizados_manut as $r): ?>
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
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <!-- Limpeza -->
        <?php
        $finalizados_limp = [];
        $outros_limp = [];
        if ($sql_limp) {
            $result = $conn->query($sql_limp . " ORDER BY data_criacao DESC");
            if ($result && $result->num_rows > 0) {
                while($r = $result->fetch_assoc()) {
                    if ($r['status'] == 'Finalizado') {
                        $finalizados_limp[] = $r;
                    } else {
                        $outros_limp[] = $r;
                    }
                }
            }
        }
        ?>
        <?php if (!empty($outros_limp)): ?>
            <h5>Limpeza em andamento ou pendente</h5>
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
                <?php foreach($outros_limp as $r): ?>
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
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        <?php if (!empty($finalizados_limp)): ?>
            <h5>Limpeza Finalizada</h5>
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
                <?php foreach($finalizados_limp as $r): ?>
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
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <!-- TI -->
        <?php
        $finalizados_ti = [];
        $outros_ti = [];
        if ($sql_ti) {
            $result = $conn->query($sql_ti . " ORDER BY data_criacao DESC");
            if ($result && $result->num_rows > 0) {
                while($r = $result->fetch_assoc()) {
                    if ($r['status'] == 'Finalizado') {
                        $finalizados_ti[] = $r;
                    } else {
                        $outros_ti[] = $r;
                    }
                }
            }
        }
        ?>
        <?php if (!empty($outros_ti)): ?>
            <h5>Solicitações de TI em andamento ou pendente</h5>
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
                <?php foreach($outros_ti as $r): ?>
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
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        <?php if (!empty($finalizados_ti)): ?>
            <h5>Solicitações de TI Finalizadas</h5>
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
                <?php foreach($finalizados_ti as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['titulo']) ?></td>
                        <td><?= nl2br(htmlspecialchars($r['descricao'])) ?></td>
                        <td><?= status_badge($r['status']) ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($r['data_criacao'])) ?></td>
                        <td><?= !empty($r['usuario_nome']) ? htmlspecialchars($r['usuario_nome']) : '<em>Desconhecido</em>' ?></td>
                        <td>
                            <a href="?view=ti&id=<?= $r['id'] ?>" class="btn btn-sm btn-info">Ver</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

    <?php endif; ?>
</div>
<?php include 'includes/footer.php'; ?>
</body>
</html>