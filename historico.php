<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

include 'includes/db.php';
include 'includes/logger.php';

function status_badge($status) {
    switch($status) {
        case 'Aberto': return '<span class="badge bg-warning">Aberto</span>';
        case 'Em andamento': return '<span class="badge bg-info">Em andamento</span>';
        case 'Pendente': return '<span class="badge bg-danger">Pendente</span>';
        case 'Finalizado': return '<span class="badge bg-success">Finalizado</span>';
        default: return '<span class="badge bg-secondary">'.htmlspecialchars($status).'</span>';
    }
}

// --- NOVO: processa mudança de responsável ---
if (isset($_POST['novo_responsavel_id']) && isset($_POST['id_manutencao'])) {
    $id_manut = intval($_POST['id_manutencao']);
    $novo_resp = intval($_POST['novo_responsavel_id']);
    
    // Buscar responsável anterior para log
    $stmt_anterior = $conn->prepare("SELECT funcionario_id FROM manutencao WHERE id = ?");
    $stmt_anterior->bind_param("i", $id_manut);
    $stmt_anterior->execute();
    $stmt_anterior->bind_result($responsavel_anterior);
    $stmt_anterior->fetch();
    $stmt_anterior->close();
    
    // Atualizar responsável
    $stmt = $conn->prepare("UPDATE manutencao SET funcionario_id=? WHERE id=?");
    $stmt->bind_param("ii", $novo_resp, $id_manut);
    $stmt->execute();
    $stmt->close();
    
    // Log da alteração
    $valor_anterior = get_user_name_by_id($conn, $responsavel_anterior);
    $valor_novo = get_user_name_by_id($conn, $novo_resp);
    log_change($conn, $_SESSION['usuario_id'], 'manutencao', $id_manut, 'responsavel', $valor_anterior, $valor_novo);
    
    $msg = '<div class="alert alert-success">Responsável alterado com sucesso!</div>';
}

// FILTRO MM/AAAA
$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : 'todos';
$mes_ano = isset($_GET['mes_ano']) ? $_GET['mes_ano'] : '';
$where_data = '';
if ($mes_ano && preg_match('/^\d{2}\/\d{4}$/', $mes_ano)) {
    list($mm, $yyyy) = explode('/', $mes_ano);
    if (checkdate($mm, 1, $yyyy)) {
        $where_data = " AND DATE_FORMAT(data_criacao, '%m/%Y') = '".sprintf("%02d",$mm)."/$yyyy' ";
    }
}

// Pagination
$items_per_page = 20;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $items_per_page;

// --- NOVO: lista funcionários ativos para troca ---
$funcionarios = [];
$res_func = $conn->query("SELECT id, nome FROM usuarios WHERE tipo='funcionario' AND status='ativo'");
while ($row = $res_func->fetch_assoc()) {
    $funcionarios[] = $row;
}

// Consultas para manutenção e limpeza
$sql_manut = "SELECT m.id, m.natureza, m.local, m.acao, m.anotacoes, m.status, m.foto, m.data_criacao, m.data_programada, u.usuario as usuario_nome, f.nome as responsavel_nome, m.funcionario_id
              FROM manutencao m
              LEFT JOIN usuarios u ON m.usuario_id = u.id
              LEFT JOIN usuarios f ON m.funcionario_id = f.id";
$sql_manut_count = "SELECT COUNT(*) as total FROM manutencao m LEFT JOIN usuarios u ON m.usuario_id = u.id LEFT JOIN usuarios f ON m.funcionario_id = f.id";

if ($tipo == 'manutencao') {
    $sql_manut .= " WHERE 1=1 $where_data";
    $sql_manut_count .= " WHERE 1=1 $where_data";
} elseif ($tipo == 'limpeza') {
    $sql_manut = false;
    $sql_manut_count = false;
}

$sql_limp = "SELECT l.id, l.local, l.acao, l.anotacoes, l.status, l.foto, l.data_criacao, u.usuario as usuario_nome
             FROM limpeza l
             LEFT JOIN usuarios u ON l.usuario_id = u.id";
$sql_limp_count = "SELECT COUNT(*) as total FROM limpeza l LEFT JOIN usuarios u ON l.usuario_id = u.id";

if ($tipo == 'limpeza') {
    $sql_limp .= " WHERE 1=1 $where_data";
    $sql_limp_count .= " WHERE 1=1 $where_data";
} elseif ($tipo == 'manutencao') {
    $sql_limp = false;
    $sql_limp_count = false;
}

$sql_ti = "SELECT s.id, s.titulo, s.descricao, s.status, s.data_criacao, u.usuario as usuario_nome
           FROM solicitacoes_ti s
           LEFT JOIN usuarios u ON s.usuario_id = u.id";
$sql_ti_count = "SELECT COUNT(*) as total FROM solicitacoes_ti s LEFT JOIN usuarios u ON s.usuario_id = u.id";

if ($tipo == 'ti') {
    $sql_manut = false;
    $sql_manut_count = false;
    $sql_limp = false;
    $sql_limp_count = false;
    $sql_ti .= " WHERE 1=1 $where_data";
    $sql_ti_count .= " WHERE 1=1 $where_data";
} elseif ($tipo != 'todos') {
    $sql_ti = false;
    $sql_ti_count = false;
}

if ($tipo == 'todos' && $where_data) {
    $sql_manut .= " WHERE 1=1 $where_data";
    $sql_manut_count .= " WHERE 1=1 $where_data";
    $sql_limp .= " WHERE 1=1 $where_data";
    $sql_limp_count .= " WHERE 1=1 $where_data";
    $sql_ti .= " WHERE 1=1 $where_data";
    $sql_ti_count .= " WHERE 1=1 $where_data";
}

// Function to create pagination links
function create_pagination_links($current_page, $total_pages, $base_url) {
    $links = [];
    
    if ($current_page > 1) {
        $links[] = '<li class="page-item"><a class="page-link" href="' . $base_url . '&page=' . ($current_page - 1) . '">Anterior</a></li>';
    }
    
    $start_page = max(1, $current_page - 2);
    $end_page = min($total_pages, $current_page + 2);
    
    for ($i = $start_page; $i <= $end_page; $i++) {
        $active = $i == $current_page ? ' active' : '';
        $links[] = '<li class="page-item' . $active . '"><a class="page-link" href="' . $base_url . '&page=' . $i . '">' . $i . '</a></li>';
    }
    
    if ($current_page < $total_pages) {
        $links[] = '<li class="page-item"><a class="page-link" href="' . $base_url . '&page=' . ($current_page + 1) . '">Próxima</a></li>';
    }
    
    return implode('', $links);
}

// Build URL for pagination
$base_url = '?tipo=' . urlencode($tipo);
if ($mes_ano) {
    $base_url .= '&mes_ano=' . urlencode($mes_ano);
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
        $sqlv = "SELECT m.*, u.usuario as usuario_nome, f.nome as responsavel_nome, m.funcionario_id
                 FROM manutencao m
                 LEFT JOIN usuarios u ON m.usuario_id = u.id
                 LEFT JOIN usuarios f ON m.funcionario_id = f.id
                 WHERE m.id = ?";
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
        .pagination-container { text-align: center; margin: 20px 0; }
        .section-separator { border-top: 2px solid #dee2e6; margin: 40px 0 30px 0; padding-top: 20px; }
    </style>
</head>
<body>
<?php include 'includes/menu.php'; ?>
<div class="container mt-4">
    <h2>Histórico de Inspeções e Solicitações</h2>
    <?php if(isset($msg)) echo $msg; ?>
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
        <!-- Visualização detalhada -->
        <div class="card mb-4">
            <div class="card-header">
                <strong>Detalhes da <?= htmlspecialchars(ucfirst($origem)) ?></strong>
            </div>
            <div class="card-body">
                <table class="table">
                    <?php if ($origem == 'manutencao'): ?>
                        <tr>
                            <th>Natureza</th>
                            <td><?= htmlspecialchars($info['natureza']) ?></td>
                        </tr>
                        <tr>
                            <th>Local</th>
                            <td><?= htmlspecialchars($info['local']) ?></td>
                        </tr>
                        <tr>
                            <th>Ação</th>
                            <td><?= htmlspecialchars($info['acao']) ?></td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td><?= status_badge($info['status']) ?></td>
                        </tr>
                        <tr>
                            <th>Data de Criação</th>
                            <td><?= date('d/m/Y H:i', strtotime($info['data_criacao'])) ?></td>
                        </tr>
                        <tr>
                            <th>Data Programada</th>
                            <td>
                                <?php if (!empty($info['data_programada'])): ?>
                                    <?= date('d/m/Y', strtotime($info['data_programada'])) ?>
                                <?php else: ?>
                                    <em>Não programada</em>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Anotações</th>
                            <td><?= nl2br(htmlspecialchars($info['anotacoes'])) ?></td>
                        </tr>
                        <tr>
                            <th>Solicitante</th>
                            <td><?= htmlspecialchars($info['usuario_nome']) ?></td>
                        </tr>
                        <tr>
                            <th>Responsável pelo serviço</th>
                            <td>
                                <?= !empty($info['responsavel_nome']) ? htmlspecialchars($info['responsavel_nome']) : '<em>Não atribuído</em>' ?>
                                <!-- Form para troca de responsável -->
                                <form method="post" class="row g-2 mt-2" style="max-width:300px;">
                                    <input type="hidden" name="id_manutencao" value="<?= $info['id'] ?>">
                                    <select name="novo_responsavel_id" class="form-select form-select-sm" required>
                                        <option value="">Mudar responsável</option>
                                        <?php foreach($funcionarios as $f): ?>
                                            <option value="<?= $f['id'] ?>" <?= ($info['funcionario_id']==$f['id'])?'selected':'' ?>>
                                                <?= htmlspecialchars($f['nome']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="col-auto">
                                        <button type="submit" class="btn btn-sm btn-primary">Atualizar</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                        <?php if (!empty($fotos_anexos)): ?>
                        <tr>
                            <th>Anexos</th>
                            <td>
                                <?php foreach ($fotos_anexos as $foto): ?>
                                    <img src="<?= htmlspecialchars($foto) ?>" class="anexo-img" alt="Anexo">
                                <?php endforeach; ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($assinatura_img): ?>
                        <tr>
                            <th>Assinatura</th>
                            <td><img src="<?= htmlspecialchars($assinatura_img) ?>" class="assinatura-img" alt="Assinatura"></td>
                        </tr>
                        <?php endif; ?>
                    <?php elseif ($origem == 'limpeza'): ?>
                        <tr>
                            <th>Local</th>
                            <td><?= htmlspecialchars($info['local']) ?></td>
                        </tr>
                        <tr>
                            <th>Ação</th>
                            <td><?= htmlspecialchars($info['acao']) ?></td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td><?= status_badge($info['status']) ?></td>
                        </tr>
                        <tr>
                            <th>Data de Criação</th>
                            <td><?= date('d/m/Y H:i', strtotime($info['data_criacao'])) ?></td>
                        </tr>
                        <tr>
                            <th>Anotações</th>
                            <td><?= nl2br(htmlspecialchars($info['anotacoes'])) ?></td>
                        </tr>
                        <tr>
                            <th>Solicitante</th>
                            <td><?= htmlspecialchars($info['usuario_nome']) ?></td>
                        </tr>
                        <?php if (!empty($fotos_anexos)): ?>
                        <tr>
                            <th>Anexos</th>
                            <td>
                                <?php foreach ($fotos_anexos as $foto): ?>
                                    <img src="<?= htmlspecialchars($foto) ?>" class="anexo-img" alt="Anexo">
                                <?php endforeach; ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    <?php elseif ($origem == 'ti'): ?>
                        <tr>
                            <th>Título</th>
                            <td><?= htmlspecialchars($info['titulo']) ?></td>
                        </tr>
                        <tr>
                            <th>Descrição</th>
                            <td><?= nl2br(htmlspecialchars($info['descricao'])) ?></td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td><?= status_badge($info['status']) ?></td>
                        </tr>
                        <tr>
                            <th>Data de Criação</th>
                            <td><?= date('d/m/Y H:i', strtotime($info['data_criacao'])) ?></td>
                        </tr>
                        <tr>
                            <th>Solicitante</th>
                            <td><?= htmlspecialchars($info['usuario_nome']) ?></td>
                        </tr>
                    <?php endif; ?>
                </table>
                <a href="javascript:history.back()" class="btn btn-secondary">Voltar</a>
            </div>
        </div>
    <?php endif; ?>

    <?php if(!$mostrar): ?>

        <!-- Manutenção -->
        <?php
        $finalizados_manut = [];
        $outros_manut = [];
        $total_manut = 0;
        $total_pages_manut = 0;
        
        if ($sql_manut) {
            // Get total count
            $total_manut = $conn->query($sql_manut_count)->fetch_assoc()['total'];
            $total_pages_manut = ceil($total_manut / $items_per_page);
            
            $result = $conn->query($sql_manut . " ORDER BY data_criacao DESC LIMIT $items_per_page OFFSET $offset");
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
        
        <?php if (!empty($outros_manut) || !empty($finalizados_manut)): ?>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5>Manutenção (<?= $total_manut ?> registros)</h5>
                <?php if ($total_pages_manut > 1): ?>
                    <small class="text-muted">Página <?= $page ?> de <?= $total_pages_manut ?></small>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($outros_manut)): ?>
            <h6 class="text-warning">Em andamento ou pendente</h6>
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Natureza</th>
                        <th>Local</th>
                        <th>Anotações</th>
                        <th>Status</th>
                        <th>Data</th>
                        <th>Programado</th>
                        <th>RESP.</th>
                        <th>Mudar Responsável</th>
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
                            <?php if (!empty($r['data_programada'])): ?>
                                <?= date('d/m/Y', strtotime($r['data_programada'])) ?>
                            <?php else: ?>
                                <em>-</em>
                            <?php endif; ?>
                        </td>
                        <td><?= !empty($r['responsavel_nome']) ? htmlspecialchars($r['responsavel_nome']) : '<em>Não atribuído</em>' ?></td>
                        <td>
                            <form method="post" style="width:140px;">
                                <input type="hidden" name="id_manutencao" value="<?= $r['id'] ?>">
                                <select name="novo_responsavel_id" class="form-select form-select-sm" required>
                                    <option value="">Trocar</option>
                                    <?php foreach($funcionarios as $f): ?>
                                        <option value="<?= $f['id'] ?>" <?= ($r['funcionario_id']==$f['id'])?'selected':'' ?>>
                                            <?= htmlspecialchars($f['nome']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn-sm btn-primary mt-1">Atualizar</button>
                            </form>
                        </td>
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
            <h6 class="text-success">Finalizada</h6>
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Natureza</th>
                        <th>Local</th>
                        <th>Anotações</th>
                        <th>Status</th>
                        <th>Data</th>
                        <th>Data Programada</th>
                        <th>Foto</th>
                        <th>Solicitante</th>
                        <th>Responsável</th>
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
                            <?php if (!empty($r['data_programada'])): ?>
                                <?= date('d/m/Y', strtotime($r['data_programada'])) ?>
                            <?php else: ?>
                                <em>-</em>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if(!empty($r['foto'])): ?>
                                <img src="<?= htmlspecialchars($r['foto']) ?>" class="foto-thumb" alt="Foto">
                            <?php endif; ?>
                        </td>
                        <td><?= !empty($r['usuario_nome']) ? htmlspecialchars($r['usuario_nome']) : '<em>Desconhecido</em>' ?></td>
                        <td><?= !empty($r['responsavel_nome']) ? htmlspecialchars($r['responsavel_nome']) : '<em>Não atribuído</em>' ?></td>
                        <td>
                            <a href="?view=manutencao&id=<?= $r['id'] ?>" class="btn btn-sm btn-info">Ver</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <!-- Pagination for Manutenção -->
        <?php if ($sql_manut && $total_pages_manut > 1): ?>
            <nav class="pagination-container">
                <ul class="pagination justify-content-center">
                    <?= create_pagination_links($page, $total_pages_manut, $base_url) ?>
                </ul>
            </nav>
        <?php endif; ?>

        <!-- Limpeza -->
        <?php
        $finalizados_limp = [];
        $outros_limp = [];
        $total_limp = 0;
        $total_pages_limp = 0;
        
        if ($sql_limp) {
            // Get total count
            $total_limp = $conn->query($sql_limp_count)->fetch_assoc()['total'];
            $total_pages_limp = ceil($total_limp / $items_per_page);
            
            $result = $conn->query($sql_limp . " ORDER BY data_criacao DESC LIMIT $items_per_page OFFSET $offset");
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
        
        <?php if (!empty($outros_limp) || !empty($finalizados_limp)): ?>
            <div class="section-separator">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5>Limpeza (<?= $total_limp ?> registros)</h5>
                    <?php if ($total_pages_limp > 1): ?>
                        <small class="text-muted">Página <?= $page ?> de <?= $total_pages_limp ?></small>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($outros_limp)): ?>
            <h6 class="text-warning">Em andamento ou pendente</h6>
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
            <h6 class="text-success">Finalizada</h6>
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

        <!-- Pagination for Limpeza -->
        <?php if ($sql_limp && $total_pages_limp > 1): ?>
            <nav class="pagination-container">
                <ul class="pagination justify-content-center">
                    <?= create_pagination_links($page, $total_pages_limp, $base_url) ?>
                </ul>
            </nav>
        <?php endif; ?>

        <!-- TI -->
        <?php
        $finalizados_ti = [];
        $outros_ti = [];
        $total_ti = 0;
        $total_pages_ti = 0;
        
        if ($sql_ti) {
            // Get total count
            $total_ti = $conn->query($sql_ti_count)->fetch_assoc()['total'];
            $total_pages_ti = ceil($total_ti / $items_per_page);
            
            $result = $conn->query($sql_ti . " ORDER BY data_criacao DESC LIMIT $items_per_page OFFSET $offset");
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
        
        <?php if (!empty($outros_ti) || !empty($finalizados_ti)): ?>
            <div class="section-separator">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5>Solicitações de TI (<?= $total_ti ?> registros)</h5>
                    <?php if ($total_pages_ti > 1): ?>
                        <small class="text-muted">Página <?= $page ?> de <?= $total_pages_ti ?></small>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($outros_ti)): ?>
            <h6 class="text-warning">Em andamento ou pendente</h6>
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
            <h6 class="text-success">Finalizadas</h6>
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

        <!-- Pagination for TI -->
        <?php if ($sql_ti && $total_pages_ti > 1): ?>
            <nav class="pagination-container">
                <ul class="pagination justify-content-center">
                    <?= create_pagination_links($page, $total_pages_ti, $base_url) ?>
                </ul>
            </nav>
        <?php endif; ?>

    <?php endif; ?>
</div>
<?php include 'includes/footer.php'; ?>
</body>
</html>