<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
include 'includes/db.php';

// Salvar inventário
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $setor = trim($_POST['setor']);
    $descricao = trim($_POST['descricao']);
    $marca_modelo = trim($_POST['marca_modelo']);
    $num_serie = trim($_POST['num_serie']);
    $conservacao = trim($_POST['conservacao']);
    $quantidade = intval($_POST['quantidade']);
    $foto_nome = null;

    // Upload de foto (opcional)
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','webp'])) {
            $foto_nome = uniqid('mob_', true) . '.' . $ext;
            // Garante que a pasta uploads existe
            if (!is_dir('uploads')) {
                mkdir('uploads', 0777, true);
            }
            if (!move_uploaded_file($_FILES['foto']['tmp_name'], "uploads/" . $foto_nome)) {
                error_log("Falha ao mover arquivo para uploads/" . $foto_nome);
                $foto_nome = null;
            }
        }
    }

    $stmt = $conn->prepare("INSERT INTO inventario_mobiliario (setor, descricao, marca_modelo, num_serie, conservacao, quantidade, foto, data_registro, usuario_id) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)");
    // 8 variáveis, tipos: sssssisi
    $stmt->bind_param("ssssssis", $setor, $descricao, $marca_modelo, $num_serie, $conservacao, $quantidade, $foto_nome, $_SESSION['usuario_id']);
    if ($stmt->execute()) {
        $msg = "Bem registrado com sucesso!";
    } else {
        $msg = "Erro ao registrar!";
    }
    $stmt->close();
}

// Listagem dos bens já registrados (opcional, por setor)
$setorFiltro = $_GET['setor'] ?? '';
$sql_list = "SELECT * FROM inventario_mobiliario";
if ($setorFiltro) {
    $sql_list .= " WHERE setor = '" . $conn->real_escape_string($setorFiltro) . "'";
}
$sql_list .= " ORDER BY data_registro DESC";
$result = $conn->query($sql_list);

// Função para buscar item pelo id
function buscarItem($conn, $id) {
    $stmt = $conn->prepare("SELECT * FROM inventario_mobiliario WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $item = $res->fetch_assoc();
    $stmt->close();
    return $item;
}

// Se houver um id para visualizar
$verItem = null;
if (isset($_GET['ver']) && is_numeric($_GET['ver'])) {
    $verItem = buscarItem($conn, (int)$_GET['ver']);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Inventário de Patrimônio</title>
    <link rel="stylesheet" href="assets/bootstrap.min.css">
    <style>
        .inventario-form { background: #fff; border-radius: 12px; padding: 24px; max-width: 600px; margin: 40px auto; box-shadow: 0 0 10px rgba(0,0,0,0.09);}
        .foto-thumb { width:60px; height:60px; object-fit:cover; border-radius:4px;}
        .table-box { background: #fff; border-radius: 12px; padding: 18px; margin: 40px auto; box-shadow: 0 0 8px rgba(0,0,0,0.07);}
        .modal-bg { background: rgba(0,0,0,0.55); position: fixed; top:0; left:0; width:100vw; height:100vh; display:flex; align-items:center; justify-content:center; z-index:9999;}
        .modal-content { background: #fff; padding: 28px 32px; border-radius: 10px; min-width:320px; max-width:98vw; box-shadow:0 0 16px #0002; }
        .close-modal { position: absolute; right:14px; top:8px; font-size:2rem; color:#888; text-decoration:none;}
        .ver-label { font-weight:bold; }
    </style>
</head>
<body>
<?php include 'includes/menu.php'; ?>

<?php if ($verItem): ?>
    <div class="modal-bg" onclick="location.href='inventario.php'">
      <div class="modal-content position-relative" onclick="event.stopPropagation()">
        <a href="inventario.php" class="close-modal">&times;</a>
        <h4>Dados do Patrimônio</h4>
        <div class="row mb-2">
            <div class="col-sm-6 mb-2"><span class="ver-label">Setor:</span> <?= htmlspecialchars($verItem['setor']) ?></div>
            <div class="col-sm-6 mb-2"><span class="ver-label">Descrição:</span> <?= htmlspecialchars($verItem['descricao']) ?></div>
            <div class="col-sm-6 mb-2"><span class="ver-label">Marca/Modelo:</span> <?= htmlspecialchars($verItem['marca_modelo']) ?></div>
            <div class="col-sm-6 mb-2"><span class="ver-label">Nº Série/Patrimônio:</span> <?= htmlspecialchars($verItem['num_serie']) ?></div>
            <div class="col-sm-6 mb-2"><span class="ver-label">Estado de Conservação:</span> <?= htmlspecialchars($verItem['conservacao']) ?></div>
            <div class="col-sm-6 mb-2"><span class="ver-label">Quantidade:</span>
                <?php
                if ($verItem['quantidade'] !== null && $verItem['quantidade'] !== "") {
                    echo (int)$verItem['quantidade'];
                } else {
                    echo '<span class="text-muted">-</span>';
                }
                ?>
            </div>
            <div class="col-sm-12 mb-2"><span class="ver-label">Data:</span> <?= date('d/m/Y H:i', strtotime($verItem['data_registro'])) ?></div>
            <div class="col-sm-12 mb-2"><span class="ver-label">Foto:</span>
                <?php if ($verItem['foto']) : ?>
                    <a href="uploads/<?= htmlspecialchars($verItem['foto']) ?>" target="_blank">
                        <img src="uploads/<?= htmlspecialchars($verItem['foto']) ?>" class="foto-thumb" alt="Foto" style="width:90px;height:90px;">
                    </a>
                <?php else: ?>
                    <span class="text-muted">-</span>
                <?php endif; ?>
            </div>
        </div>
      </div>
    </div>
<?php endif; ?>

<div class="inventario-form">
    <h3>Cadastro de Patrimônio</h3>
    <?php if ($msg) echo "<div class='alert alert-info'>$msg</div>"; ?>
    <form method="post" enctype="multipart/form-data">
        <div class="mb-3">
            <label>Setor / Loja <span class="text-danger">*</span></label>
            <input type="text" name="setor" class="form-control" required maxlength="100">
        </div>
        <div class="mb-3">
            <label>Descrição do Bem <span class="text-danger">*</span></label>
            <input type="text" name="descricao" class="form-control" required maxlength="255">
        </div>
        <div class="mb-3">
            <label>Marca/Modelo (opcional)</label>
            <input type="text" name="marca_modelo" class="form-control" maxlength="100">
        </div>
        <div class="mb-3">
            <label>Nº de Série / Patrimônio (opcional)</label>
            <input type="text" name="num_serie" class="form-control" maxlength="100">
        </div>
        <div class="mb-3">
            <label>Estado de Conservação <span class="text-danger">*</span></label>
            <select name="conservacao" class="form-control" required>
                <option value="">Selecione...</option>
                <option value="Ótimo">Ótimo</option>
                <option value="Bom">Bom</option>
                <option value="Regular">Regular</option>
                <option value="Ruim">Ruim</option>
                <option value="Sucata">Sucata</option>
            </select>
        </div>
        <div class="mb-3">
            <label>Quantidade <span class="text-danger">*</span></label>
            <input type="number" name="quantidade" class="form-control" required min="1" max="999">
        </div>
        <div class="mb-3">
            <label>Foto (opcional)</label>
            <input type="file" name="foto" accept="image/*" class="form-control">
        </div>
        <button class="btn btn-primary" type="submit">Cadastrar Bem</button>
    </form>
</div>

<div class="table-box" style="max-width:98vw;">
    <h4>Bens Cadastrados
        <form method="get" class="d-inline ms-3">
            <label for="setorfiltro">Filtrar por setor:</label>
            <input type="text" id="setorfiltro" name="setor" value="<?= htmlspecialchars($setorFiltro) ?>">
            <button class="btn btn-sm btn-secondary">Filtrar</button>
            <a href="inventario.php" class="btn btn-sm btn-link">Limpar</a>
        </form>
    </h4>
    <div style="overflow-x:auto;">
        <table class="table table-bordered table-striped mt-3">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Setor/Loja</th>
                    <th>Descrição</th>
                    <th>Marca/Modelo</th>
                    <th>Nº Série/Patrimônio</th>
                    <th>Estado</th>
                    <th>Qtd</th>
                    <th>Foto</th>
                    <th>Data</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['setor']) ?></td>
                    <td><?= htmlspecialchars($row['descricao']) ?></td>
                    <td><?= htmlspecialchars($row['marca_modelo']) ?></td>
                    <td><?= htmlspecialchars($row['num_serie']) ?></td>
                    <td><?= htmlspecialchars($row['conservacao']) ?></td>
                    <td>
                        <?php
                        // Mostra 0 se for zero, traço só se for NULL ou vazio
                        if ($row['quantidade'] !== null && $row['quantidade'] !== "") {
                            echo (int)$row['quantidade'];
                        } else {
                            echo '<span class="text-muted">-</span>';
                        }
                        ?>
                    </td>
                    <td>
                        <?php if ($row['foto']) : ?>
                            <a href="uploads/<?= htmlspecialchars($row['foto']) ?>" target="_blank">
                                <img src="uploads/<?= htmlspecialchars($row['foto']) ?>" class="foto-thumb" alt="Foto">
                            </a>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td><?= date('d/m/Y H:i', strtotime($row['data_registro'])) ?></td>
                    <td>
                        <a class="btn btn-sm btn-info" href="inventario.php?ver=<?= $row['id'] ?>">Ver</a>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
</body>
</html>