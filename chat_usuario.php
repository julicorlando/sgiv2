<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
date_default_timezone_set('America/Sao_Paulo');

// Gera o arquivo de chat por data
$hoje = date('Y-m-d');
$chat_arquivo = __DIR__ . "/chat_ti_{$hoje}.txt";

// Nome do usuário igual ao menu
$usuario = isset($_SESSION['usuario_nome']) ? $_SESSION['usuario_nome'] : 'Usuário';

function escrever_mensagem($arquivo, $usuario, $mensagem) {
    $hora = date('d/m/Y H:i');
    $linha = $usuario . '|' . $hora . '|' . str_replace(["\r","\n"], ['','<br>'], trim($mensagem));
    file_put_contents($arquivo, $linha.PHP_EOL, FILE_APPEND | LOCK_EX);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nova_mensagem'])) {
    $mensagem = trim($_POST['nova_mensagem']);
    if ($mensagem !== '') {
        escrever_mensagem($chat_arquivo, $usuario, $mensagem);
    }
    header('Location: chat_usuario.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Chat com o T.I.</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: Arial,sans-serif; background: #f7f7f7; margin:0; min-height:100vh;}
        .chat-main { max-width: 480px; margin: 10px auto; background: #fff; border-radius: 12px; box-shadow:0 0 10px #0002; padding:12px; }
        h3 { text-align:center; margin-top:0;}
        .chat-user { text-align:center; color:#0074d9; font-size:1.12em; margin-bottom:8px; }
        .chat-box { background: #f5f5fa; border-radius: 8px; min-height: 180px; padding: 8px 6px; max-height: 260px; overflow-y: auto; margin-bottom:10px; }
        .msg { margin-bottom:11px; }
        .msg-ti { background:#e3f8ff; border-radius:6px; padding:5px 11px; display:inline-block;}
        .msg-user { background:#f6e7ff; border-radius:6px; padding:5px 11px; display:inline-block;}
        .msg-info { font-size:0.93em;color:#555; margin-bottom:2px;}
        form.chat-form { display:flex; gap:8px; margin-top: 6px;}
        form.chat-form textarea { flex:1; font-size:1em; border-radius:6px; border:1px solid #bbb; padding:6px; min-height:28px; resize:vertical;}
        form.chat-form button { background: #0074d9; color: #fff; border: none; border-radius: 6px; padding: 6px 16px; font-size: 1em; cursor: pointer;}
        form.chat-form button:hover { background: #005fa3;}
    </style>
</head>
<body>
    <div class="chat-main">
        <h3>Chat com o T.I.</h3>
        <div class="chat-user">
            👤 Você está logado como <strong><?= htmlspecialchars($usuario) ?></strong>
        </div>
        <div class="chat-box" id="chat-box">
            <div style="text-align:center;color:#888;">Carregando mensagens...</div>
        </div>
        <form method="post" class="chat-form" autocomplete="off">
            <textarea name="nova_mensagem" required placeholder="Digite sua mensagem..." maxlength="700"></textarea>
            <button type="submit">Enviar</button>
        </form>
    </div>
    <script>
    let lastContent = "";
    function atualizarChat() {
        fetch('chat_fetch.php?data=<?= $hoje ?>')
            .then(r=>r.json())
            .then(msgs=>{
                let html = "";
                if(!msgs.length) {
                    html = '<div style="text-align:center;color:#888;">Nenhuma mensagem ainda.</div>';
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