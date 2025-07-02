<?php
session_start();
include 'includes/db.php'; // Sua conexão mysqli $conn

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['pdf'])) {
    $nome_exibicao = trim($_POST['nome_exibicao'] ?? '');
    $arquivo = $_FILES['pdf'];
    if ($arquivo['error'] === UPLOAD_ERR_OK && strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION)) === 'pdf' && $nome_exibicao !== '') {
        $novo_nome = uniqid('pdf_', true) . '.pdf';
        $destino = "uploads/" . $novo_nome;
        if (move_uploaded_file($arquivo['tmp_name'], $destino)) {
            $stmt = $conn->prepare("INSERT INTO arquivos_pdf (nome_exibicao, caminho_arquivo) VALUES (?, ?)");
            $stmt->bind_param("ss", $nome_exibicao, $destino);
            $stmt->execute();
            $msg = "Arquivo enviado com sucesso!";
        } else {
            $msg = "Falha ao mover o arquivo.";
        }
    } else {
        $msg = "Arquivo inválido. Envie apenas PDFs e preencha o nome.";
    }
}

// Download handler
if (isset($_GET['download'])) {
    $id = intval($_GET['download']);
    $stmt = $conn->prepare("SELECT caminho_arquivo, nome_exibicao FROM arquivos_pdf WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($caminho, $nome_exibicao);
    if ($stmt->fetch() && file_exists($caminho)) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="'.basename($nome_exibicao).'.pdf"');
        readfile($caminho);
        exit;
    } else {
        $msg = "Arquivo não encontrado.";
    }
}

$result = $conn->query("SELECT id, nome_exibicao, data_upload FROM arquivos_pdf ORDER BY data_upload DESC");
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Upload de PDFs</title>
    <link rel="stylesheet" href="assets/bootstrap.min.css">
    <style>
        body { background: #f8f9fa; }
        .upload-box { background: #fff; border-radius: 12px; padding: 24px; max-width: 450px; margin: 40px auto; box-shadow: 0 0 10px rgba(0,0,0,0.08);}
        .table-box { background: #fff; border-radius: 12px; padding: 24px; margin: 40px auto; box-shadow: 0 0 10px rgba(0,0,0,0.08);}
    </style>
</head>
<body>
<?php include 'includes/menu.php'; ?>
<div class="upload-box">
    <h3>Enviar novo PDF</h3>
    <?php if ($msg) echo "<div class='alert alert-info'>$msg</div>"; ?>
    <form method="post" enctype="multipart/form-data">
        <div class="mb-3">
            <label>Nome para exibição (Ex: CPRH Janeiro de 202x):</label>
            <input type="text" name="nome_exibicao" class="form-control" maxlength="255" required>
        </div>
        <div class="mb-3">
            <label>Escolha o PDF:</label>
            <input type="file" name="pdf" accept="application/pdf" class="form-control" required>
        </div>
        <button class="btn btn-primary" type="submit">Upload</button>
    </form>
</div>

<div class="table-box" style="max-width:800px;">
    <h4>Arquivos enviados</h4>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Nome para exibição</th>
                <th>Download</th>
            </tr>
        </thead>
        <tbody>
        <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['nome_exibicao']) ?></td>
                <td>
                    <a href="?download=<?= $row['id'] ?>" class="btn btn-success btn-sm">Download</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
<?php include 'includes/footer.php'; ?>
</body>
</html>