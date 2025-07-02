<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
include 'includes/db.php';

$mensagem = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $local = $_POST['local'];
    $acao = $_POST['acao'];
    $anotacoes = $_POST['anotacoes'];
    $status = $_POST['status'];
    $usuario_id = $_SESSION['usuario_id'];

    // 1. Insere o registro principal sem fotos
    $sql = "INSERT INTO limpeza (local, acao, anotacoes, status, usuario_id) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $local, $acao, $anotacoes, $status, $usuario_id);

    if ($stmt->execute()) {
        $limpeza_id = $stmt->insert_id;
        $mensagem = '<div class="alert alert-success">Registro salvo com sucesso!</div>';

        // 2. Salva os anexos (fotos) em uma tabela separada
        if (isset($_FILES['fotos']) && count($_FILES['fotos']['name']) > 0) {
            for ($i = 0; $i < count($_FILES['fotos']['name']); $i++) {
                if ($_FILES['fotos']['error'][$i] === UPLOAD_ERR_OK) {
                    $ext = pathinfo($_FILES['fotos']['name'][$i], PATHINFO_EXTENSION);
                    $novo_nome = uniqid('limp_', true) . '.' . strtolower($ext);
                    $destino = 'uploads/' . $novo_nome;
                    if (move_uploaded_file($_FILES['fotos']['tmp_name'][$i], $destino)) {
                        // Grava o caminho na tabela limpeza_fotos
                        $stmt_foto = $conn->prepare("INSERT INTO limpeza_fotos (limpeza_id, caminho) VALUES (?, ?)");
                        $stmt_foto->bind_param("is", $limpeza_id, $destino);
                        $stmt_foto->execute();
                        $stmt_foto->close();
                    }
                }
            }
        }
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
    <title>Formulário de Limpeza</title>
    <link rel="stylesheet" href="assets/bootstrap.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
<?php include 'includes/menu.php'; ?>
<div class="container">
    <h2>Formulário de Inspeção - Limpeza</h2>
    <?php if ($mensagem) echo $mensagem; ?>
    <form method="post" enctype="multipart/form-data" class="mt-4">
        <div class="mb-3">
            <label class="form-label">Local</label>
            <select class="form-select" name="local" required>
                <option value="">Selecione</option>
                <option>Banheiro Feminino Mall</option>
                <option>Banheiro Masculino Mall</option>
                <option>Mall - Corredor Guichê</option>
                <option>Entrada Principal</option>
                <option>Entrada Triangular</option>
                <option>Corredor Lojas Lado Direito</option>
                <option>Corredor Lojas Lado Esquerdo</option>
                <option>Ponte do Lago</option>
                <option>Estacionamento</option>
                <option>Corredores de Serviço (Praça ou Administrativo)</option>
                <option>Praça de Alimentação</option>
                <option>Banheiro Feminino Praça</option>
                <option>Banheiro Masculino Praça</option>
                <option>Outros</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Ação</label>
            <select class="form-select" name="acao" required>
                <option value="">Selecione</option>
                <option>Corrigir</option>
                <option>Substituir</option>
                <option>Limpar</option>
                <option>Pintar</option>
                <option>Outros</option>
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
            <label class="form-label">Fotos (opcional, pode selecionar várias)</label>
            <input type="file" class="form-control" name="fotos[]" accept="image/*" multiple>
        </div>
        <button type="submit" class="btn btn-success">Salvar</button>
        <a href="imprimir.php?tipo=limpeza" class="btn btn-primary" target="_blank">Gerar Impressão</a>
    </form>
</div>
<?php include 'includes/footer.php'; ?>
</body>
</html>