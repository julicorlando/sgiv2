<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
include 'includes/db.php';
include 'includes/logger.php';

// Busca funcionários ativos (mostra NOME, não usuário)
$funcionarios = [];
$res_func = $conn->query("SELECT id, nome FROM usuarios WHERE tipo='funcionario' AND status='ativo'");
while ($row = $res_func->fetch_assoc()) {
    $funcionarios[] = $row;
}

$mensagem = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $natureza = $_POST['natureza'];
    $local = $_POST['local'];
    $acao = $_POST['acao'];
    $anotacoes = $_POST['anotacoes'];
    $status = $_POST['status'];
    $usuario_id = $_SESSION['usuario_id'];
    $data_programada = !empty($_POST['data_programada']) ? $_POST['data_programada'] : null;
    $codigo_aparelho = (isset($_POST['codigo_aparelho']) && $natureza == 'Ar-condicionado') ? trim($_POST['codigo_aparelho']) : null;

    // Atribuição de funcionário (aleatório ou selecionado)
    $funcionario_id = $_POST['funcionario_id'] ?? '';
    if ($funcionario_id === 'ALEATORIO') {
        $res = $conn->query("SELECT id FROM usuarios WHERE tipo='funcionario' AND status='ativo' ORDER BY RAND() LIMIT 1");
        $linha = $res->fetch_assoc();
        $funcionario_id = $linha ? $linha['id'] : null;
    } else {
        $funcionario_id = intval($funcionario_id);
    }

    if ($natureza == "Ar-condicionado") {
        $sql = "INSERT INTO manutencao (natureza, local, acao, anotacoes, status, usuario_id, data_programada, codigo_aparelho, funcionario_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssssi", $natureza, $local, $acao, $anotacoes, $status, $usuario_id, $data_programada, $codigo_aparelho, $funcionario_id);
    } else {
        $sql = "INSERT INTO manutencao (natureza, local, acao, anotacoes, status, usuario_id, data_programada, funcionario_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssi", $natureza, $local, $acao, $anotacoes, $status, $usuario_id, $data_programada, $funcionario_id);
    }

    if ($stmt->execute()) {
        $manutencao_id = $stmt->insert_id;
        $mensagem = '<div class="alert alert-success">Registro salvo com sucesso!</div>';

        // Fotos
        if (isset($_FILES['fotos']) && count($_FILES['fotos']['name']) > 0 && $_FILES['fotos']['name'][0] != "") {
            for ($i = 0; $i < count($_FILES['fotos']['name']); $i++) {
                if ($_FILES['fotos']['error'][$i] === UPLOAD_ERR_OK) {
                    $ext = pathinfo($_FILES['fotos']['name'][$i], PATHINFO_EXTENSION);
                    $novo_nome = uniqid('manut_', true) . '.' . strtolower($ext);
                    $destino = 'uploads/' . $novo_nome;
                    if (move_uploaded_file($_FILES['fotos']['tmp_name'][$i], $destino)) {
                        $stmt_foto = $conn->prepare("INSERT INTO manutencao_fotos (manutencao_id, caminho) VALUES (?, ?)");
                        $stmt_foto->bind_param("is", $manutencao_id, $destino);
                        $stmt_foto->execute();
                        $stmt_foto->close();
                    }
                }
            }
        }

        // Log
        $log = "Manutenção registrada: $natureza em $local por usuário ID $usuario_id";
        $stmt_log = $conn->prepare("INSERT INTO manutencao_logs (manutencao_id, log, data) VALUES (?, ?, NOW())");
        $stmt_log->bind_param("is", $manutencao_id, $log);
        $stmt_log->execute();
        $stmt_log->close();

    } else {
        $mensagem = '<div class="alert alert-danger">Erro ao salvar: ' . $conn->error . '</div>';
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Formulário de Manutenção</title>
    <link rel="stylesheet" href="assets/bootstrap.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script>
    function toggleCodigoAparelho() {
        var natureza = document.getElementById('natureza').value;
        var divCodigo = document.getElementById('div-codigo-ar');
        if (natureza === 'Ar-condicionado') {
            divCodigo.style.display = 'block';
            document.getElementById('codigo_aparelho').required = true;
        } else {
            divCodigo.style.display = 'none';
            document.getElementById('codigo_aparelho').required = false;
        }
    }
    window.onload = function() {
        toggleCodigoAparelho();
        document.getElementById('natureza').addEventListener('change', toggleCodigoAparelho);
    };
    </script>
</head>
<body>
<?php include 'includes/menu.php'; ?>
<div class="container">
    <h2>Formulário de Inspeção - Manutenção</h2>
    <?php if ($mensagem) echo $mensagem; ?>
    <form method="post" enctype="multipart/form-data" class="mt-4">
        <div class="mb-3">
            <label class="form-label">Natureza</label>
            <select class="form-select" name="natureza" id="natureza" required>
                <option value="">Selecione</option>
                <option>Infra de Rede</option>
                <option>Elétrica</option>
                <option>Hidráulica</option>
                <option>Predial</option>
                <option>PCI</option>
                <option>Ar-condicionado</option>
                <option>Outros</option>
            </select>
        </div>
        <div class="mb-3" id="div-codigo-ar" style="display:none;">
            <label class="form-label">Código do Aparelho (ar-condicionado)</label>
            <input type="text" class="form-control" name="codigo_aparelho" id="codigo_aparelho" maxlength="50" autocomplete="off">
        </div>
        <div class="mb-3">
            <label class="form-label">Local/Equipamento</label>
            <input type="text" class="form-control" name="local" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Ação</label>
            <select class="form-select" name="acao" required>
                <option value="">Selecione</option>
                <option>Implantar</option>
                <option>Corrigir</option>
                <option>Substituir</option>
                <option>Limpar</option>
                <option>Pintar</option>
                <option>Outros</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Funcionário Responsável</label>
            <select class="form-select" name="funcionario_id" required>
                <option value="">Selecione</option>
                <option value="ALEATORIO">Atribuir aleatoriamente</option>
                <?php foreach ($funcionarios as $f): ?>
                    <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Anotações</label>
            <textarea class="form-control" name="anotacoes"></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Status</label>
            <select class="form-select" name="status" required>
                <option>Aberto</option>
                <option>Em andamento</option>
                <option>Pendente</option>
                <option>Finalizado</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Data Programada</label>
            <input type="date" class="form-control" name="data_programada">
        </div>
        <div class="mb-3">
            <label class="form-label">Fotos (opcional, pode selecionar várias)</label>
            <input type="file" class="form-control" name="fotos[]" accept="image/*" multiple>
        </div>
        <button type="submit" class="btn btn-success">Salvar</button>
        <a href="imprimir.php?tipo=manutencao" class="btn btn-primary" target="_blank">Gerar Impressão</a>
    </form>
</div>
<?php include 'includes/footer.php'; ?>
</body>
</html>