<div class="container-fluid bg-light py-3 mt-5">
    <div class="text-center small text-muted">
        &copy; <?= date('Y') ?> Desenvolvido por Julio Orlando &mdash; Todos os direitos reservados &mdash; Shopping Carpina
    </div>
</div>

<!-- ...demais conteúdo do seu footer... -->

<!-- Botão flutuante de chat com o T.I. -->
<style>
#chat-ti-float-btn {
    position: fixed;
    right: 30px;
    bottom: 30px;
    background: #0074d9;
    color: #fff;
    border: none;
    border-radius: 50%;
    width: 60px; height: 60px;
    font-size: 2em;
    box-shadow: 0 4px 24px #0003;
    cursor: pointer;
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background .2s;
}
#chat-ti-float-btn:hover {
    background: #005fa3;
}
#chat-ti-popup {
    display: none;
    position: fixed;
    right: 30px;
    bottom: 100px;
    width: 340px;
    max-width: 97vw;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 24px #0003;
    z-index: 10000;
    overflow: hidden;
}
#chat-ti-popup-header {
    background: #0074d9;
    color: #fff;
    padding: 12px 18px;
    font-weight: bold;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
#chat-ti-popup-close {
    background: none;
    border: none;
    color: #fff;
    font-size: 1.3em;
    cursor: pointer;
    margin-left: 12px;
}
#chat-ti-popup-iframe {
    width: 100%;
    height: 420px;
    border: none;
    background: #f7fafe;
}
@media (max-width:500px) {
    #chat-ti-popup { width: 98vw; right: 1vw; }
    #chat-ti-float-btn { right: 10px; bottom: 10px; width: 54px; height: 54px; font-size: 1.6em;}
}
</style>

<button id="chat-ti-float-btn" title="Chat com o T.I.">
    💬
</button>

<div id="chat-ti-popup">
    <div id="chat-ti-popup-header">
        Chat com o T.I.
        <button id="chat-ti-popup-close" title="Fechar">&times;</button>
    </div>
    <iframe id="chat-ti-popup-iframe" src="http://10.0.18.153/operacoes/chat_usuario.php"></iframe>
</div>

<script>
document.getElementById('chat-ti-float-btn').onclick = function(){
    document.getElementById('chat-ti-popup').style.display = 'block';
};
document.getElementById('chat-ti-popup-close').onclick = function(){
    document.getElementById('chat-ti-popup').style.display = 'none';
};
// Fechar ao clicar fora do popup opcional
window.addEventListener('click', function(e){
    var popup = document.getElementById('chat-ti-popup');
    var btn = document.getElementById('chat-ti-float-btn');
    if (popup.style.display === 'block' && !popup.contains(e.target) && e.target !== btn) {
        popup.style.display = 'none';
    }
});
</script>