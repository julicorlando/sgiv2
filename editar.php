<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

include 'includes/db.php';

// Descobre se é manutenção, limpeza ou TI
$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$sucesso = false;
$erro = '';

// Carrega registro
if ($tipo == 'manutencao') {
    $sql = "SELECT * FROM manutencao WHERE id = ?";
} elseif ($tipo == 'limpeza') {
    $sql = "SELECT * FROM limpeza WHERE id = ?";
} elseif ($tipo == 'ti') {
    $sql = "SELECT * FROM solicitacoes_ti WHERE id = ?";
} else {
    die("Tipo não especificado.");
}

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$registro = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$registro) {
    die("Registro não encontrado.");
}

// Se finalizado, não permite edição
if ($registro['status'] == 'Finalizado') {
    header("Location: historico.php?view={$tipo}&id={$id}");
    exit;
}

// Se post, atualiza dados
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($tipo == 'manutencao') {
        $natureza = $_POST['natureza'];
        $local = $_POST['local'];
        $acao = $_POST['acao'];
        $anotacoes = $_POST['anotacoes'];
        $status = $_POST['status'];

        // Atualiza o registro principal
        $sql = "UPDATE manutencao SET natureza=?, local=?, acao=?, anotacoes=?, status=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssi", $natureza, $local, $acao, $anotacoes, $status, $id);
        $sucesso = $stmt->execute();
        $stmt->close();

        // Processa anexos (fotos)
        if (isset($_FILES['fotos']) && count($_FILES['fotos']['name']) > 0 && $_FILES['fotos']['name'][0] != "") {
            for ($i = 0; $i < count($_FILES['fotos']['name']); $i++) {
                if ($_FILES['fotos']['error'][$i] === UPLOAD_ERR_OK) {
                    $ext = pathinfo($_FILES['fotos']['name'][$i], PATHINFO_EXTENSION);
                    $novo_nome = uniqid('manut_', true) . '.' . strtolower($ext);
                    $destino = 'uploads/' . $novo_nome;
                    if (move_uploaded_file($_FILES['fotos']['tmp_name'][$i], $destino)) {
                        $stmt_foto = $conn->prepare("INSERT INTO manutencao_fotos (manutencao_id, caminho) VALUES (?, ?)");
                        $stmt_foto->bind_param("is", $id, $destino);
                        $stmt_foto->execute();
                        $stmt_foto->close();
                    }
                }
            }
        }

    } elseif ($tipo == 'limpeza') {
        $local = $_POST['local'];
        $acao = $_POST['acao'];
        $anotacoes = $_POST['anotacoes'];
        $status = $_POST['status'];

        $sql = "UPDATE limpeza SET local=?, acao=?, anotacoes=?, status=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssi", $local, $acao, $anotacoes, $status, $id);
        $sucesso = $stmt->execute();
        $stmt->close();

        // Processa anexos (fotos)
        if (isset($_FILES['fotos']) && count($_FILES['fotos']['name']) > 0 && $_FILES['fotos']['name'][0] != "") {
            for ($i = 0; $i < count($_FILES['fotos']['name']); $i++) {
                if ($_FILES['fotos']['error'][$i] === UPLOAD_ERR_OK) {
                    $ext = pathinfo($_FILES['fotos']['name'][$i], PATHINFO_EXTENSION);
                    $novo_nome = uniqid('limp_', true) . '.' . strtolower($ext);
                    $destino = 'uploads/' . $novo_nome;
                    if (move_uploaded_file($_FILES['fotos']['tmp_name'][$i], $destino)) {
                        $stmt_foto = $conn->prepare("INSERT INTO limpeza_fotos (limpeza_id, caminho) VALUES (?, ?)");
                        $stmt_foto->bind_param("is", $id, $destino);
                        $stmt_foto->execute();
                        $stmt_foto->close();
                    }
                }
            }
        }
    } elseif ($tipo == 'ti') {
        $titulo = $_POST['titulo'];
        $descricao = $_POST['descricao'];
        $status = $_POST['status'];

        $sql = "UPDATE solicitacoes_ti SET titulo=?, descricao=?, status=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $titulo, $descricao, $status, $id);
        $sucesso = $stmt->execute();
        $stmt->close();
    }

    if ($sucesso) {
        header("Location: historico.php?view={$tipo}&id={$id}");
        exit;
    } else {
        $erro = "Erro ao atualizar registro.";
    }
}

// Carrega fotos já anexadas
$fotos = [];
if ($tipo == 'manutencao') {
    $qf = $conn->prepare("SELECT id, caminho FROM manutencao_fotos WHERE manutencao_id = ?");
    $qf->bind_param("i", $id);
    $qf->execute();
    $res_fotos = $qf->get_result();
    while ($row = $res_fotos->fetch_assoc()) $fotos[] = $row;
    $qf->close();
} elseif ($tipo == 'limpeza') {
    $qf = $conn->prepare("SELECT id, caminho FROM limpeza_fotos WHERE limpeza_id = ?");
    $qf->bind_param("i", $id);
    $qf->execute();
    $res_fotos = $qf->get_result();
    while ($row = $res_fotos->fetch_assoc()) $fotos[] = $row;
    $qf->close();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar <?= ucfirst($tipo) ?></title>
    <link rel="stylesheet" href="assets/bootstrap.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        .foto-thumb { max-width: 120px; max-height: 120px; border-radius: 6px; margin: 5px;}
    </style>
</head>
<body>
<?php include 'includes/menu.php'; ?>
<div class="container mt-4">
    <h2>Editar <?= ucfirst($tipo) ?></h2>
    <?php if ($erro): ?>
        <div class="alert alert-danger"><?= $erro ?></div>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data" class="mt-4">
    <?php if ($tipo == 'manutencao'): ?>
        <div class="mb-3">
            <label class="form-label">Natureza</label>
            <select class="form-select" name="natureza" required>
                <?php foreach (['Elétrica','Hidráulica','Predial','PCI','Outros'] as $opt): ?>
                    <option <?= ($registro['natureza']==$opt?'selected':'') ?>><?= $opt ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php endif; ?>
    <?php if ($tipo == 'manutencao' || $tipo == 'limpeza'): ?>
        <div class="mb-3">
            <label class="form-label">Local<?= $tipo=='manutencao' ? '/Equipamento' : '' ?></label>
            <?php if ($tipo=='manutencao'): ?>
                <input type="text" class="form-control" name="local" value="<?= htmlspecialchars($registro['local']) ?>" required>
            <?php else: ?>
                <select class="form-select" name="local" required>
                    <?php 
                    $locais = [
                        'Banheiro Feminino Mall',
                        'Banheiro Masculino Mall',
                        'Mall - Corredor Guichê',
                        'Entrada Principal',
                        'Entrada Triangular',
                        'Corredor Lojas Lado Direito',
                        'Corredor Lojas Lado Esquerdo',
                        'Ponte do Lago',
                        'Estacionamento',
                        'Corredores de Serviço (Praça ou Administrativo)',
                        'Praça de Alimentação',
                        'Banheiro Feminino Praça',
                        'Banheiro Masculino Praça',
                        'Outros'
                    ];
                    foreach ($locais as $opt): ?>
                        <option <?= ($registro['local']==$opt?'selected':'') ?>><?= $opt ?></option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
        </div>
        <div class="mb-3">
            <label class="form-label">Ação</label>
            <select class="form-select" name="acao" required>
                <?php foreach (['Corrigir','Substituir','Limpar','Pintar','Outros'] as $opt): ?>
                    <option <?= ($registro['acao']==$opt?'selected':'') ?>><?= $opt ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Anotações</label>
            <textarea class="form-control" name="anotacoes"><?= htmlspecialchars($registro['anotacoes']) ?></textarea>
        </div>
    <?php elseif ($tipo == 'ti'): ?>
        <div class="mb-3">
            <label class="form-label">Título</label>
            <input type="text" class="form-control" name="titulo" value="<?= htmlspecialchars($registro['titulo']) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Descrição</label>
            <textarea class="form-control" name="descricao" required><?= htmlspecialchars($registro['descricao']) ?></textarea>
        </div>
    <?php endif; ?>
        <div class="mb-3">
            <label class="form-label">Status</label>
            <select class="form-select" name="status" required>
                <?php foreach (['Aberto','Em andamento','Pendente','Finalizado'] as $opt): ?>
                    <option <?= ($registro['status']==$opt?'selected':'') ?>><?= $opt ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php if ($tipo == 'manutencao' || $tipo == 'limpeza'): ?>
        <div class="mb-3">
            <label class="form-label">Fotos (opcional, pode anexar várias)</label>
            <input type="file" class="form-control" name="fotos[]" accept="image/*" multiple>
            <?php if (count($fotos)): ?>
                <div class="mt-2">
                    <?php foreach ($fotos as $foto): ?>
                        <img src="<?= htmlspecialchars($foto['caminho']) ?>" class="foto-thumb" alt="Foto">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <button type="submit" class="btn btn-success">Salvar</button>
        <a href="historico.php?view=<?= $tipo ?>&id=<?= $id ?>" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
<?php include 'includes/footer.php'; ?>
</body>
</html>