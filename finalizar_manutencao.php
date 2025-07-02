<?php
include 'includes/db.php';

// Função para escapar HTML
function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// Mensagens
$sucesso = '';
$erro = '';

// Processar finalização se enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finalizar_id'])) {
    $manutencao_id = intval($_POST['finalizar_id']);
    $codigo_aparelho = isset($_POST['codigo_aparelho']) ? trim($_POST['codigo_aparelho']) : '';

    // Buscar o código correto do aparelho no banco
    $stmt = $conn->prepare("SELECT codigo_aparelho FROM manutencao WHERE id = ?");
    $stmt->bind_param("i", $manutencao_id);
    $stmt->execute();
    $stmt->bind_result($codigo_correto);
    $stmt->fetch();
    $stmt->close();

    if (!$codigo_correto) {
        $erro = "Manutenção não encontrada ou não possui código cadastrado!";
    } elseif ($codigo_aparelho === '') {
        $erro = "Por favor, informe o código do aparelho para finalizar!";
    } elseif (strtolower(trim($codigo_aparelho)) !== strtolower(trim($codigo_correto))) {
        $erro = "Código do aparelho incorreto!";
    } else {
        // Salvar fotos, se houver
        if (isset($_FILES['fotos']) && $_FILES['fotos']['name'][0] != "") {
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

        // Salvar assinatura se enviada
        if (isset($_POST['assinatura']) && $_POST['assinatura'] != '') {
            $data = $_POST['assinatura'];
            if (preg_match('/^data:image\/png;base64,/', $data)) {
                $data = str_replace('data:image/png;base64,', '', $data);
                $data = str_replace(' ', '+', $data);
                $data = base64_decode($data);
                $nome_arquivo = 'uploads/assinatura_' . uniqid() . '.png';
                file_put_contents($nome_arquivo, $data);
                // Opcional: salvar caminho em uma coluna no banco, caso deseje associar
            }
        }

        // Atualiza status para Finalizado
        $stmt = $conn->prepare("UPDATE manutencao SET status='Finalizado' WHERE id=?");
        $stmt->bind_param("i", $manutencao_id);
        $stmt->execute();
        $stmt->close();

        $sucesso = "Manutenção finalizada com sucesso!";
    }
}

// Buscar todas as manutenções de ar-condicionado
$sql = "SELECT id, local, anotacoes, status, 
        DATE_FORMAT(data_criacao, '%d/%m/%Y %H:%i') as data_criacao
        FROM manutencao 
        WHERE natureza = 'Ar-condicionado'
        ORDER BY 
            FIELD(status, 'Aberto', 'Em andamento', 'Pendente', 'Finalizado'), 
            data_criacao DESC";
$manuts = $conn->query($sql);

// Para o JS funcionar em todos os formulários, precisamos dos IDs:
$manuts_rows = [];
if ($manuts) {
    while ($row = $manuts->fetch_assoc()) {
        $manuts_rows[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Finalizar Manutenção de Ar-condicionado</title>
    <link rel="stylesheet" href="assets/bootstrap.min.css">
    <style>
        .table-striped>tbody>tr.pending {background-color: #fff3cd;}
        .finalizar-form {border:1px solid #ccc; border-radius:7px; padding:16px; margin-bottom:24px;}
        .assinatura-canvas {border:1px solid #888; border-radius:5px; background:#fff;}
    </style>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.5/dist/signature_pad.umd.min.js"></script>
    <script>
    // Expande/colapsa o formulário ao clicar em Finalizar
    document.addEventListener('DOMContentLoaded', function() {
        let buttons = document.querySelectorAll('[data-bs-toggle="collapse"]');
        buttons.forEach(function(btn) {
            btn.addEventListener('click', function() {
                let target = document.querySelector(this.getAttribute('data-bs-target'));
                if(target.classList.contains('show')){
                    target.classList.remove('show');
                } else {
                    // fecha todos os outros abertos
                    document.querySelectorAll('.collapse.show').forEach(function(el){
                        el.classList.remove('show');
                    });
                    target.classList.add('show');
                }
            });
        });

        // Inicializa SignaturePad para todos os canvas de assinatura
        window.signaturePads = {};
        <?php foreach($manuts_rows as $row): ?>
        var canvas<?= $row['id'] ?> = document.getElementById('signature<?= $row['id'] ?>');
        if(canvas<?= $row['id'] ?>){
            window.signaturePads[<?= $row['id'] ?>] = new SignaturePad(canvas<?= $row['id'] ?>, {backgroundColor: "#fff"});
        }
        <?php endforeach; ?>
    });

    function limparAssinatura(canvas_id) {
        var id = canvas_id.replace('signature','');
        if(window.signaturePads && window.signaturePads[id]){
            window.signaturePads[id].clear();
        }
    }

    function enviaAssinatura(form) {
        var id = form.finalizar_id.value;
        var pad = window.signaturePads[id];
        if (pad && !pad.isEmpty()) {
            form.assinatura.value = pad.toDataURL();
        } else {
            alert("Por favor, assine no campo de assinatura.");
            return false;
        }
        return true;
    }
    </script>
</head>
<body>
<div class="container mt-4">
    <h2>Finalizar Manutenção - Ar-condicionado</h2>
    <?php if ($sucesso): ?>
        <div class="alert alert-success"><?= $sucesso ?></div>
    <?php elseif ($erro): ?>
        <div class="alert alert-danger"><?= $erro ?></div>
    <?php endif; ?>

    <table class="table table-striped align-middle">
        <thead>
            <tr>
                <th>Local</th>
                <th>Anotações</th>
                <th>Status</th>
                <th>Data</th>
                <th>Ação</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($manuts_rows as $row): ?>
            <tr class="<?= $row['status'] !== 'Finalizado' ? 'pending' : '' ?>">
                <td><?= h($row['local']) ?></td>
                <td><?= h($row['anotacoes']) ?></td>
                <td>
                    <?php
                    $badge = "secondary";
                    if ($row['status'] === 'Aberto') $badge = "warning";
                    elseif ($row['status'] === 'Em andamento') $badge = "info";
                    elseif ($row['status'] === 'Pendente') $badge = "danger";
                    elseif ($row['status'] === 'Finalizado') $badge = "success";
                    ?>
                    <span class="badge bg-<?= $badge ?>">
                        <?= h($row['status']) ?>
                    </span>
                </td>
                <td><?= h($row['data_criacao']) ?></td>
                <td>
                    <?php if($row['status'] !== 'Finalizado'): ?>
                    <button type="button" class="btn btn-outline-success btn-sm"
                        data-bs-toggle="collapse"
                        data-bs-target="#finaliza<?= $row['id'] ?>">
                        Finalizar
                    </button>
                    <?php else: ?>
                    <span class="text-muted">-</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php if($row['status'] !== 'Finalizado'): ?>
            <tr class="collapse" id="finaliza<?= $row['id'] ?>">
                <td colspan="5">
                    <form method="post" enctype="multipart/form-data" class="finalizar-form" onsubmit="return enviaAssinatura(this)">
                        <input type="hidden" name="finalizar_id" value="<?= $row['id'] ?>">
                        <div class="mb-2">
                            <label class="form-label">Informe o código que está no aparelho:</label>
                            <input type="text" name="codigo_aparelho" class="form-control" required autocomplete="off">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Anexe fotos da manutenção (opcional):</label>
                            <input type="file" name="fotos[]" accept="image/*" multiple class="form-control">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Assinatura do responsável:</label><br>
                            <canvas class="assinatura-canvas" id="signature<?= $row['id'] ?>" width="300" height="100" style="touch-action: none;"></canvas>
                            <input type="hidden" name="assinatura" id="assinatura_field<?= $row['id'] ?>">
                            <button type="button" class="btn btn-sm btn-secondary mt-1" onclick="limparAssinatura('signature<?= $row['id'] ?>')">Limpar</button>
                        </div>
                        <button type="submit" class="btn btn-success mt-2">Finalizar Manutenção</button>
                    </form>
                </td>
            </tr>
            <?php endif; ?>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<script src="assets/bootstrap.bundle.min.js"></script>
</body>
</html>