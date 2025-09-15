<?php
session_start();
$senha_correta = '07052025'; // Troque para a senha desejada
date_default_timezone_set('America/Sao_Paulo');

// Logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    unset($_SESSION['ti_auth']);
    header('Location: ti_chat.php');
    exit;
}

// Processa login
$erro = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['senha'])) {
    if ($_POST['senha'] === $senha_correta) {
        $_SESSION['ti_auth'] = true;
        header('Location: ti_chat.php');
        exit;
    } else {
        $erro = true;
    }
}

// Funções
function escrever_mensagem($arquivo, $usuario, $mensagem) {
    $hora = date('d/m/Y H:i');
    $linha = $usuario . '|' . $hora . '|' . str_replace(["\r","\n"], ['','<br>'], trim($mensagem));
    file_put_contents($arquivo, $linha.PHP_EOL, FILE_APPEND | LOCK_EX);
}
function listar_chats() {
    $arquivos = glob(__DIR__ . "/chat_ti_*.txt");
    rsort($arquivos); // mais novos primeiro
    $datas = [];
    foreach ($arquivos as $arq) {
        if (preg_match('/chat_ti_(\d{4}-\d{2}-\d{2})\.txt$/', $arq, $m)) {
            $datas[] = $m[1];
        }
    }
    return $datas;
}

// Se autenticado, mostra seleção de data e chat correspondente
if (isset($_SESSION['ti_auth']) && $_SESSION['ti_auth'] === true) {
    $datas_disponiveis = listar_chats();
    $data_selecionada = isset($_GET['data']) && in_array($_GET['data'], $datas_disponiveis)
        ? $_GET['data'] : (count($datas_disponiveis) ? $datas_disponiveis[0] : date('Y-m-d'));
    $chat_arquivo = __DIR__ . "/chat_ti_{$data_selecionada}.txt";

    // Recebe mensagem nova
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nova_mensagem'])) {
        $usuario = 'T.I.';
        $mensagem = trim($_POST['nova_mensagem']);
        if ($mensagem !== '') {
            escrever_mensagem($chat_arquivo, $usuario, $mensagem);
        }
        header('Location: ti_chat.php?data=' . urlencode($data_selecionada));
        exit;
    }
    ?>
    <!DOCTYPE html>
    <html lang="pt-br">
    <head>
        <meta charset="utf-8">
        <title>Chat T.I.</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <style>
            body { font-family: Arial,sans-serif; background: #f7f7f7; margin:0; min-height:100vh;}
            .chat-main { max-width: 540px; margin: 40px auto; background: #fff; border-radius: 12px; box-shadow:0 0 10px #0002; padding:24px; }
            h2 { text-align:center; }
            .chat-box { background: #f5f5fa; border-radius: 8px; min-height: 220px; padding: 14px 10px; max-height: 340px; overflow-y: auto; margin-bottom:18px; }
            .msg { margin-bottom:13px; }
            .msg-ti { background:#e3f8ff; border-radius:6px; padding:6px 13px; display:inline-block;}
            .msg-user { background:#f6e7ff; border-radius:6px; padding:6px 13px; display:inline-block;}
            .msg-info { font-size:0.93em;color:#555; margin-bottom:2px;}
            form.chat-form { display:flex; gap:10px; margin-top: 10px;}
            form.chat-form textarea { flex:1; font-size:1em; border-radius:6px; border:1px solid #bbb; padding:7px; min-height:34px; resize:vertical;}
            form.chat-form button { background: #0074d9; color: #fff; border: none; border-radius: 6px; padding: 7px 22px; font-size: 1em; cursor: pointer;}
            form.chat-form button:hover { background: #005fa3;}
            .logout-btn { margin-top: 22px; display: block; text-align:center;}
            .logout-btn button { background: #0074d9; color: #fff; border: none; border-radius: 6px; padding: 8px 28px; font-size: 1em; cursor: pointer;}
            .logout-btn button:hover { background: #005fa3;}
            .date-select { text-align:center; margin-bottom: 16px; }
            .date-select select { font-size:1em; border-radius:6px; padding:4px 9px; border:1px solid #bbb;}
        </style>
    </head>
    <body>
        <div class="chat-main">
            <h2>Chat Direto com o T.I.</h2>
            <div class="date-select">
                <form method="get" style="display:inline;">
                    <label for="data">Selecionar data do chat:</label>
                    <select name="data" id="data" onchange="this.form.submit()">
                        <?php foreach ($datas_disponiveis as $data): ?>
                            <option value="<?= htmlspecialchars($data) ?>" <?= $data===$data_selecionada?'selected':'' ?>>
                                <?= date('d/m/Y', strtotime($data)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            <div class="chat-box" id="chat-box">
                <div style="text-align:center;color:#888;">Carregando mensagens...</div>
            </div>
            <form method="post" class="chat-form" autocomplete="off">
                <textarea name="nova_mensagem" required placeholder="Digite sua resposta..." maxlength="1000"></textarea>
                <button type="submit">Enviar</button>
            </form>
            <form method="post" class="logout-btn">
                <button name="logout" type="submit">Sair</button>
            </form>
        </div>
        <script>
        let lastContent = "";
        function atualizarChat() {
            fetch('chat_fetch.php?data=<?= $data_selecionada ?>')
                .then(r=>r.json())
                .then(msgs=>{
                    let html = "";
                    if(!msgs.length) {
                        html = '<div style="text-align:center;color:#888;">Nenhuma mensagem ainda para esta data.</div>';
                    } else {
                        html = msgs.map(function(msg){
                            let tipo = (msg.usuario === "T.I.") ? "msg-ti" : "msg-user";
                            let safeUser = msg.usuario.replace(/</g, "&lt;").replace(/>/g, "&gt;");
                            let safeMsg = msg.mensagem.replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/<br>/g, "<br>");
                            return `
                            <div class="msg">
                                <div class="msg-info"><strong>${safeUser}</strong> <span style="color:#aaa;">em ${msg.hora}</span></div>
                                <div class="${tipo}">${safeMsg}</div>
                            </div>
                            `;
                        }).join("");
                    }
                    if (lastContent !== html) {
                        document.getElementById('chat-box').innerHTML = html;
                        let cb = document.getElementById('chat-box');
                        cb.scrollTop = cb.scrollHeight;
                        lastContent = html;
                    }
                });
        }
        setInterval(atualizarChat, 3000);
        atualizarChat();
        </script>
    </body>
    </html>
    <?php
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Login Chat T.I.</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: Arial,sans-serif; background: #f7f7f7; margin:0; min-height:100vh;}
        .login-main { max-width: 350px; margin: 80px auto; background: #fff; border-radius: 12px; box-shadow:0 0 14px #0002; padding:30px 24px; }
        h2 { text-align:center; }
        label { display:block; margin-bottom: 10px; }
        input[type="password"] { width:96%; padding:8px; font-size:1em; border-radius:6px; border:1px solid #bbb; }
        button { margin-top:18px; padding:8px 22px; border-radius:6px; background:#0074d9; color:#fff; border:none; font-size:1em; cursor:pointer; width:100%;}
        button:hover { background:#005fa3;}
        .erro { color: #c00; margin-top: 12px; text-align:center;}
    </style>
</head>
<body>
    <div class="login-main">
        <h2>Acesso ao Chat T.I.</h2>
        <form method="post" autocomplete="off">
            <label>Senha de acesso:
                <input type="password" name="senha" required autofocus autocomplete="current-password">
            </label>
            <button type="submit">Entrar</button>
            <?php if ($erro): ?>
                <div class="erro">Senha incorreta!</div>
            <?php endif; ?>
        </form>
    </div>
</body>
</html>