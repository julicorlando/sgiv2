<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
include 'includes/menu.php';
include 'includes/db.php';

$mensagem = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $usuario_upload = $_SESSION['usuario_nome'] ?? 'Desconhecido';

    if ($nome && $email && isset($_FILES['curriculo']) && $_FILES['curriculo']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['curriculo']['name'], PATHINFO_EXTENSION));
        $permitidas = ['pdf', 'doc', 'docx'];
        if (!in_array($ext, $permitidas)) {
            $mensagem = 'Formato de arquivo inválido. Envie PDF, DOC ou DOCX.';
        } else {
            if (!is_dir('uploads')) mkdir('uploads', 0777, true);
            $novo_nome = uniqid('curriculo_') . '.' . $ext;
            $destino = 'uploads/' . $novo_nome;
            if (move_uploaded_file($_FILES['curriculo']['tmp_name'], $destino)) {
                $stmt = $conn->prepare("INSERT INTO talentos_curriculos (nome, email, usuario_upload, arquivo) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $nome, $email, $usuario_upload, $novo_nome);
                if ($stmt->execute()) {
                    $mensagem = 'Currículo enviado com sucesso!';
                } else {
                    $mensagem = 'Erro ao salvar no banco. Tente novamente.';
                }
                $stmt->close();
            } else {
                $mensagem = 'Falha ao salvar o arquivo.';
            }
        }
    } else {
        $mensagem = 'Preencha todos os campos e envie um arquivo.';
    }
}
?>
<div class="container" style="max-width:600px;">
    <h2 class="mb-4">Upload de Currículo</h2>
    <?php if ($mensagem): ?>
        <div class="alert alert-info"><?= htmlspecialchars($mensagem) ?></div>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data" class="border p-4 rounded bg-light shadow-sm">
        <div class="mb-3">
            <label for="nome" class="form-label">Nome completo:</label>
            <input type="text" name="nome" id="nome" class="form-control" required maxlength="255">
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">E-mail:</label>
            <input type="email" name="email" id="email" class="form-control" required maxlength="255">
        </div>
        <div class="mb-3">
            <label for="curriculo" class="form-label">Arquivo do currículo (PDF, DOC, DOCX):</label>
            <input type="file" name="curriculo" id="curriculo" class="form-control" required accept=".pdf,.doc,.docx">
        </div>
        <button type="submit" class="btn btn-primary w-100">Enviar Currículo</button>
    </form>
</div>