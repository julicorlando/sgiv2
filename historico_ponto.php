<?php
$pdo = new PDO("mysql:host=localhost;dbname=sistema_inspecao;charset=utf8", "root", "");
$id = intval($_GET['id'] ?? 0);

$ponto = $pdo->prepare("SELECT * FROM planta_pontos WHERE id=?");
$ponto->execute([$id]);
$ponto = $ponto->fetch();

$historico = $pdo->prepare("SELECT * FROM planta_ponto_historico WHERE ponto_id=? ORDER BY data ASC");
$historico->execute([$id]);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Histórico do Ponto</title>
    <style>
        body{font-family:Arial,sans-serif;background:#f9f9f9;}
        .main{max-width:600px;margin:30px auto;background:#fff;padding:24px 30px;border-radius:12px;box-shadow:0 0 10px #0001;}
        .hist{border-left:3px solid #0074d9;padding-left:14px;margin-bottom:18px;}
        .hist strong{color:#0074d9;}
        .voltar{margin-top:18px;display:inline-block;}
    </style>
</head>
<body>
    <?php include 'includes/menu.php'; ?>
    <div class="main">
        <h2>Histórico do Ponto #<?= htmlspecialchars($id) ?></h2>
        <div>
            <b>Descrição original:</b><br>
            <?= htmlspecialchars($ponto['descricao']) ?><br>
            <b>Status atual:</b> <?= htmlspecialchars($ponto['status']) ?>
        </div>
        <h3 style="margin-top:24px;">Eventos</h3>
        <?php foreach($historico as $h): ?>
            <div class="hist">
                <strong><?=date('d/m/Y H:i',strtotime($h['data']))?></strong> 
                <em>[<?=htmlspecialchars($h['acao'])?>]</em><br>
                <?=nl2br(htmlspecialchars($h['descricao']))?><br>
                <?php if($h['usuario']): ?>Usuário: <?=htmlspecialchars($h['usuario'])?><?php endif; ?>
            </div>
        <?php endforeach; ?>
        <a href="mapa.php" class="voltar">← Voltar ao mapa</a>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>