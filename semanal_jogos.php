<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Função utilitária para salvar jogos
function salvar_jogos($jogos) {
    file_put_contents('jogos.json', json_encode($jogos, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
}

// Salvar novo jogo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_game'])) {
    $jogos = file_exists('jogos.json') ? json_decode(file_get_contents('jogos.json'), true) : [];
    $novo = [
        'id' => uniqid(),
        'data' => $_POST['data'],
        'dia_semana' => $_POST['dia_semana'],
        'hora' => $_POST['hora'],
        'canal' => $_POST['canal'],
        'time1' => $_POST['time1'],
        'escudo1' => '',
        'time2' => $_POST['time2'],
        'escudo2' => '',
        'exibido' => false,
        'usuario_exibiu' => null
    ];
    // Upload dos escudos
    $uploadPath = 'escudos/';
    if (!is_dir($uploadPath)) mkdir($uploadPath, 0777, true);
    foreach (['escudo1', 'escudo2'] as $esc) {
        if (isset($_FILES[$esc]) && is_uploaded_file($_FILES[$esc]['tmp_name'])) {
            $file = $_FILES[$esc];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $name = uniqid() . '.' . $ext;
                if (move_uploaded_file($file['tmp_name'], $uploadPath.$name)) {
                    $novo[$esc] = $uploadPath.$name;
                }
            }
        }
    }
    $jogos[] = $novo;
    salvar_jogos($jogos);
    header("Location: semanal_jogos.php?lista=1");
    exit;
}

// Editar jogo existente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_game'])) {
    $jogos = file_exists('jogos.json') ? json_decode(file_get_contents('jogos.json'), true) : [];
    foreach ($jogos as &$jogo) {
        if ($jogo['id'] === $_POST['edit_id']) {
            $jogo['data'] = $_POST['data'];
            $jogo['dia_semana'] = $_POST['dia_semana'];
            $jogo['hora'] = $_POST['hora'];
            $jogo['canal'] = $_POST['canal'];
            $jogo['time1'] = $_POST['time1'];
            $jogo['time2'] = $_POST['time2'];
            // Uploads de escudo, se enviados
            $uploadPath = 'escudos/';
            if (!is_dir($uploadPath)) mkdir($uploadPath, 0777, true);
            foreach (['escudo1', 'escudo2'] as $esc) {
                if (isset($_FILES[$esc]) && is_uploaded_file($_FILES[$esc]['tmp_name'])) {
                    $file = $_FILES[$esc];
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                        $name = uniqid() . '.' . $ext;
                        if (move_uploaded_file($file['tmp_name'], $uploadPath.$name)) {
                            $jogo[$esc] = $uploadPath.$name;
                        }
                    }
                }
            }
        }
    }
    unset($jogo);
    salvar_jogos($jogos);
    header("Location: semanal_jogos.php?lista=1");
    exit;
}

// Marcar como exibido
if (isset($_GET['exibir']) && $_GET['exibir']) {
    $id = $_GET['exibir'];
    $jogos = file_exists('jogos.json') ? json_decode(file_get_contents('jogos.json'), true) : [];
    foreach ($jogos as &$jogo) {
        if ($jogo['id'] === $id) {
            $jogo['exibido'] = true;
            // Salva o login do usuário que exibiu
            $jogo['usuario_exibiu'] = isset($_SESSION['login']) ? $_SESSION['login'] : (isset($_SESSION['nome']) ? $_SESSION['nome'] : 'Desconhecido');
        }
    }
    unset($jogo);
    salvar_jogos($jogos);
    header("Location: semanal_jogos.php?lista=1");
    exit;
}

// Listar só exibidos?
$apenas_exibidos = (isset($_GET['exibidos']) && $_GET['exibidos']);

// Carregar lista de jogos
$jogos = file_exists('jogos.json') ? json_decode(file_get_contents('jogos.json'), true) : [];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<title>Lista Semanal de Jogos</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body { font-family: Arial,sans-serif; background: #f7f7f7; margin:0; min-height:100vh;}
.mainbox { max-width: 700px; margin: 30px auto; background: #fff; border-radius: 12px; box-shadow:0 0 10px #0002; padding:24px;}
h2 { text-align:center; }
.jogos-lista { margin-top: 32px; }
.jogo { display:flex;align-items:center; justify-content:space-between; background:#f9f9f9; padding:14px 12px; border-radius:7px; margin-bottom:14px; box-shadow:0 2px 7px #0001;}
.jogo-info { flex:1; min-width: 120px;}
.jogo-canal { font-size:0.97em; color:#444; margin-bottom:2px;}
.jogo-data { font-weight:bold; }
.jogo-hora { color:#0074d9; margin-left:9px;}
.jogo-times { display:flex; align-items:center; gap:12px;}
.jogo-time { display:flex; align-items:center; gap:5px;}
.jogo-time img { width:28px; height:28px; border-radius:50%; object-fit:cover; border:1px solid #eee;}
.add-box { max-width:540px; margin:30px auto 0 auto; background:#f5f7ff; border-radius:8px; padding:22px 18px 16px 18px; box-shadow:0 2px 8px #0001;}
.add-box label {display:block; margin-top:10px;}
.add-box input[type="text"],.add-box input[type="date"],.add-box input[type="time"] { width:90%; padding:5px; font-size:1em; border-radius:6px; border:1px solid #bbb; margin-top:3px;}
.add-box select { width:93%;padding:5px;border-radius:6px; border:1px solid #bbb; font-size:1em;}
.add-box input[type="file"] {margin-top:3px;}
.add-box button { margin-top:18px; padding:8px 23px; border-radius:6px; background:#0074d9; color:#fff; border:none; font-size:1.07em; cursor:pointer;}
.add-box button:hover { background:#005fa3; }
.jogo-actions { display: flex; gap: 8px; }
.jogo-actions a, .jogo-actions button {
    font-size: 0.97em; padding: 5px 14px; border-radius: 5px; border: none; cursor: pointer; 
    background: #ececec; color: #222; text-decoration: none;
    transition: background .2s;
}
.jogo-actions a:hover, .jogo-actions button:hover { background: #e0e0e0; }
.jogo-actions .exibido { background: #a5e5a5; color: #145421; }
.imprimir-btn {
    padding: 4px 18px;
    background: #444;
    color: #fff;
    font-size: 1em;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    margin-left: 8px;
    transition: background .2s;
    display: inline-block;
}
.imprimir-btn:hover { background: #222; }
@media (max-width:700px) {
    .mainbox { padding:2vw; }
    .add-box { padding:2vw; }
}
@media print {
    body, html {
        background: #fff !important;
        color: #222;
    }
    .mainbox {
        box-shadow: none !important;
        background: #fff !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    .add-box, .imprimir-btn, .mainbox > div:first-child, .mainbox > h2, .mainbox > .add-box, .mainbox > form {
        display: none !important;
    }
    .jogos-lista {
        margin-top: 0 !important;
    }
    .jogo {
        box-shadow: none !important;
        background: #fff !important;
        border-bottom: 1px solid #eee !important;
        page-break-inside: avoid;
    }
    .jogo-actions {
        display: none !important;
    }
}
</style>
<script>
function imprimirLista() {
    window.print();
}
</script>
</head>
<body>
<?php include 'includes/menu.php'; ?>
<div class="mainbox">
    <h2>Lista Semanal de Jogos</h2>
    <div style="text-align:center;margin-bottom:18px;">
        <a href="semanal_jogos.php?lista=1" style="margin-right:18px;">Ver Lista</a>
        <a href="semanal_jogos.php?add=1" style="margin-right:18px;">Adicionar Jogo</a>
        <a href="semanal_jogos.php?exibidos=1">Ver apenas exibidos</a>
        <?php if (!$apenas_exibidos && !isset($_GET['add']) && !isset($_GET['edit'])): ?>
            <button class="imprimir-btn" onclick="imprimirLista()">Imprimir</button>
        <?php endif; ?>
    </div>
    <?php if (isset($_GET['add'])): ?>
    <form class="add-box" method="post" enctype="multipart/form-data">
        <h3>Adicionar novo jogo</h3>
        <label>Data: <input type="date" name="data" required></label>
        <label>Dia da Semana:
            <select name="dia_semana" required>
                <option value="Domingo">Domingo</option>
                <option value="Segunda">Segunda</option>
                <option value="Terça">Terça</option>
                <option value="Quarta">Quarta</option>
                <option value="Quinta">Quinta</option>
                <option value="Sexta">Sexta</option>
                <option value="Sábado">Sábado</option>
            </select>
        </label>
        <label>Hora: <input type="time" name="hora" required></label>
        <label>Canal de transmissão: <input type="text" name="canal" required></label>
        <div style="display:flex;gap:12px;align-items:center;margin-top:10px;">
            <div>
                <label>Time 1: <input type="text" name="time1" required></label>
                <label>Escudo Time 1: <input type="file" name="escudo1" accept="image/*"></label>
            </div>
            <span style="font-size:1.5em;margin-top:22px;">X</span>
            <div>
                <label>Time 2: <input type="text" name="time2" required></label>
                <label>Escudo Time 2: <input type="file" name="escudo2" accept="image/*"></label>
            </div>
        </div>
        <button name="add_game" type="submit">Adicionar Jogo</button>
    </form>
    <?php elseif (isset($_GET['edit'])):
        $edit_id = $_GET['edit'];
        $edit_jogo = null;
        foreach ($jogos as $j) { if ($j['id'] === $edit_id) { $edit_jogo = $j; break; } }
        if ($edit_jogo):
    ?>
    <form class="add-box" method="post" enctype="multipart/form-data">
        <h3>Editar jogo</h3>
        <input type="hidden" name="edit_id" value="<?=htmlspecialchars($edit_jogo['id'])?>">
        <label>Data: <input type="date" name="data" required value="<?=htmlspecialchars($edit_jogo['data'])?>"></label>
        <label>Dia da Semana:
            <select name="dia_semana" required>
                <?php foreach(['Domingo','Segunda','Terça','Quarta','Quinta','Sexta','Sábado'] as $dia): ?>
                    <option value="<?=htmlspecialchars($dia)?>" <?=($edit_jogo['dia_semana']==$dia)?'selected':''?>><?=htmlspecialchars($dia)?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Hora: <input type="time" name="hora" required value="<?=htmlspecialchars($edit_jogo['hora'])?>"></label>
        <label>Canal de transmissão: <input type="text" name="canal" required value="<?=htmlspecialchars($edit_jogo['canal'])?>"></label>
        <div style="display:flex;gap:12px;align-items:center;margin-top:10px;">
            <div>
                <label>Time 1: <input type="text" name="time1" required value="<?=htmlspecialchars($edit_jogo['time1'])?>"></label>
                <label>Escudo Time 1: <input type="file" name="escudo1" accept="image/*">
                <?php if (!empty($edit_jogo['escudo1']) && file_exists($edit_jogo['escudo1'])): ?>
                    <img src="<?=htmlspecialchars($edit_jogo['escudo1'])?>" alt="Escudo 1" style="width:26px;vertical-align:middle;">
                <?php endif; ?>
                </label>
            </div>
            <span style="font-size:1.5em;margin-top:22px;">X</span>
            <div>
                <label>Time 2: <input type="text" name="time2" required value="<?=htmlspecialchars($edit_jogo['time2'])?>"></label>
                <label>Escudo Time 2: <input type="file" name="escudo2" accept="image/*">
                <?php if (!empty($edit_jogo['escudo2']) && file_exists($edit_jogo['escudo2'])): ?>
                    <img src="<?=htmlspecialchars($edit_jogo['escudo2'])?>" alt="Escudo 2" style="width:26px;vertical-align:middle;">
                <?php endif; ?>
                </label>
            </div>
        </div>
        <button name="edit_game" type="submit">Salvar Alterações</button>
    </form>
    <?php endif; ?>
    <?php else: ?>
    <div class="jogos-lista">
        <?php
        // Só mostra os jogos não exibidos, a não ser que esteja na tela de exibidos
        $lista = $apenas_exibidos
            ? array_filter($jogos, fn($j)=>!empty($j['exibido']))
            : array_filter($jogos, fn($j)=>empty($j['exibido']));
        if (empty($lista)): ?>
            <div style="text-align:center;color:#888;">
                <?= $apenas_exibidos ? "Nenhum jogo marcado como exibido." : "Nenhum jogo cadastrado para esta semana." ?>
            </div>
        <?php else: ?>
            <?php foreach ($lista as $jogo): ?>
                <div class="jogo">
                    <div class="jogo-info">
                        <div class="jogo-data"><?=htmlspecialchars($jogo['data'])?> (<?=htmlspecialchars($jogo['dia_semana'])?>)
                            <span class="jogo-hora"><?=htmlspecialchars($jogo['hora'])?></span>
                        </div>
                        <div class="jogo-canal">Canal: <?=htmlspecialchars($jogo['canal'])?></div>
                    </div>
                    <div class="jogo-times">
                        <div class="jogo-time">
                            <?php if (!empty($jogo['escudo1']) && file_exists($jogo['escudo1'])): ?>
                                <img src="<?=htmlspecialchars($jogo['escudo1'])?>" alt="Escudo 1">
                            <?php endif; ?>
                            <span><?=htmlspecialchars($jogo['time1'])?></span>
                        </div>
                        <span style="font-weight:bold;font-size:1.13em;">x</span>
                        <div class="jogo-time">
                            <span><?=htmlspecialchars($jogo['time2'])?></span>
                            <?php if (!empty($jogo['escudo2']) && file_exists($jogo['escudo2'])): ?>
                                <img src="<?=htmlspecialchars($jogo['escudo2'])?>" alt="Escudo 2">
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="jogo-actions">
                        <?php if (!$apenas_exibidos): ?>
                            <a href="semanal_jogos.php?edit=<?=htmlspecialchars($jogo['id'])?>">Editar</a>
                            <a href="semanal_jogos.php?exibir=<?=htmlspecialchars($jogo['id'])?>" class="exibido">Marcar como exibido</a>
                        <?php else: ?>
                            <span style="color:#145421;background:#a5e5a5;padding:4px 10px;border-radius:5px;">
                                Exibido
                                <?php if (!empty($jogo['usuario_exibiu'])): ?>
                                    <br><span style="font-size:0.96em;color:#1a2f13;">por <?=htmlspecialchars($jogo['usuario_exibiu'])?></span>
                                <?php endif; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
</body>
</html>