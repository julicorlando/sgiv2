<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Mapa Interativo da Planta</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        html, body { height:100%; margin:0; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; min-height:100vh; }
        #mainbox { max-width:1020px; margin:30px auto 70px auto; background:#fff; border-radius:15px; box-shadow:0 0 12px #0002; padding:18px 18px 30px 18px;}
        #container-zoom-area {
            position:relative;
            display:block;
            margin:auto;
            background:#eee;
            border-radius:10px;
            box-shadow:0 0 6px #0002;
            width:100%;
            max-width:95vw;
            min-width:220px;
            min-height:220px;
            height:80vh;
            overflow:hidden;
            touch-action:none;
        }
        #container-zoom-area.fullscreen {
            width:100vw !important;
            max-width:100vw !important;
            height:100vh !important;
            min-height:100vh !important;
            border-radius:0;
            margin:0;
            box-shadow:none;
            background:#222;
            z-index:999;
        }
        #zoomArea {
            position:relative;
            width:100%;
            height:100%;
            display:block;
            transform-origin: top left;
            transition: transform 0.2s;
            cursor: grab;
        }
        #zoomArea.dragging { cursor: grabbing; }
        #planta {
            position: absolute;
            left: 0; top: 0;
            width:100%;
            height:100%;
            object-fit: contain;
            border-radius:10px;
            user-select:none;
            pointer-events:auto;
            background:#fff;
            display:block;
        }
        .ponto {
            position: absolute;
            border-radius:50%; 
            cursor: pointer;
            border:2px solid #fff;
            box-shadow: 0 0 5px #0006;
            display:flex;
            align-items:center;
            justify-content:center;
            font-weight:bold;
            z-index:2;
            transition: left 0.1s, top 0.1s, width 0.1s, height 0.1s;
        }
        .vermelho { background: red !important; color:#fff; }
        .amarelo { background: gold !important; color:#000; }
        .popup-ponto {
            position: absolute;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 16px #0004;
            padding: 14px 18px 12px 18px;
            min-width: 180px;
            max-width: 300px;
            font-size: 1.08em;
            z-index: 999;
            border: 1px solid #ccc;
            animation: fadeIn .15s;
        }
        @keyframes fadeIn { from {opacity:0; transform:scale(0.98);} to {opacity:1; transform:scale(1);} }
        .popup-desc {
            margin-bottom: 12px;
            color: #222;
            word-break:break-word;
        }
        .status-btns-popup {
            display: flex;
            gap: 6px;
        }
        .status-btn-popup {
            border: 1px solid #aaa;
            border-radius: 4px;
            background: #eee;
            color: #333;
            padding: 2px 9px;
            font-size: 1em;
            cursor: pointer;
        }
        .status-btn-popup.selected,
        .status-btn-popup:hover {
            border: 2px solid #333;
            background: #ddd;
        }
        .popup-close {
            position: absolute; top:7px; right:10px; color: #888; background: none; border: none; font-size: 1.3em; cursor:pointer;
        }
        .ponto-input-wrap {
            position: absolute;
            width: 0; height: 0;
            z-index: 100;
        }
        .ponto-input {
            position: absolute;
            min-width: 120px;
            max-width: 180px;
            font-size: 1em;
            padding: 2px 6px;
            border-radius: 6px;
            border: 1px solid #aaa;
            z-index: 10;
            background: #fff;
            box-shadow: 0 2px 6px #0001;
            resize: none;
            outline: none;
            pointer-events: auto;
        }
        .status-btns {
            display: inline-flex;
            gap: 4px;
            margin-left: 8px;
            vertical-align: middle;
        }
        .status-btn {
            border: 1px solid #aaa;
            border-radius: 4px;
            background: #eee;
            color: #333;
            padding: 2px 7px;
            font-size: 0.96em;
            cursor: pointer;
        }
        .status-btn.selected,
        .status-btn:hover {
            border: 2px solid #222;
            background: #ddd;
        }
        @media (max-width:700px) {
            #mainbox { max-width:100%; padding:2vw; }
            #container-zoom-area { height:62vw; }
        }
    </style>
</head>
<body>
    <?php include 'includes/menu.php'; ?>
    <div id="mainbox">
    <h3 style="text-align:center;">Mapa Estratégico Shopping Carpina</h3>
    <div id="container-zoom-area">
        <div class="zoom-controls" id="zoomControls">
            <button class="zoom-btn" id="zoomMais" tabindex="-1" title="Aumentar Zoom" type="button">+</button>
            <button class="zoom-btn" id="zoomMenos" tabindex="-1" title="Diminuir Zoom" type="button">-</button>
            <button class="fullscreen-btn" id="fullscreenBtn" tabindex="-1" title="Tela Cheia" type="button">&#x26F6;</button>
            <span id="zoomLabel"></span>
        </div>
        <div id="zoomArea">
            <img src="planta.jpg" id="planta" />
        </div>
    </div>
    </div>
    <script>
    let pontos = [];
    const zoomArea = document.getElementById('zoomArea');
    const planta = document.getElementById('planta');
    const containerZoom = document.getElementById('container-zoom-area');
    let zoom = 1.0;
    let imgNaturalWidth = null, imgNaturalHeight = null;

    // Pan variables
    let panX = 0, panY = 0;
    let isDragging = false;
    let lastDragX = 0, lastDragY = 0;

    function setZoomLabel() {
        document.getElementById('zoomLabel').innerText = `Zoom: ${(zoom*100).toFixed(0)}%`;
    }

    function updateTransform() {
        zoomArea.style.transform = `translate(${panX}px,${panY}px) scale(${zoom})`;
    }

    function alterarZoom(fator) {
        const rect = containerZoom.getBoundingClientRect();
        const centerX = rect.width / 2 - panX;
        const centerY = rect.height / 2 - panY;
        const oldZoom = zoom;
        zoom *= fator;
        if (zoom < 0.1) zoom = 0.1;
        if (zoom > 8) zoom = 8;
        panX -= centerX * (zoom/oldZoom - 1);
        panY -= centerY * (zoom/oldZoom - 1);
        updateTransform();
        setZoomLabel();
        renderizarPontos();
    }

    function carregarPontos() {
        fetch('api_pontos.php').then(r=>r.json()).then(data => {
            pontos = data;
            renderizarPontos();
        });
    }

    function renderizarPontos() {
        document.querySelectorAll('.ponto, .ponto-input-wrap, .popup-ponto').forEach(e=>e.remove());
        let rect = planta.getBoundingClientRect();
        let refSize = Math.min(rect.width, rect.height);
        let pontoPx = Math.max(18, refSize*0.035);
        pontos.forEach(pt => {
            if(pt.status === "finalizado") return;
            const b = document.createElement('div');
            b.className = 'ponto ' + (pt.status==='novo'?'vermelho':'amarelo');
            b.style.width = b.style.height = pontoPx+"px";
            b.style.left = `calc(${(pt.x*100).toFixed(4)}% - ${pontoPx/2}px)`;
            b.style.top  = `calc(${(pt.y*100).toFixed(4)}% - ${pontoPx/2}px)`;
            b.title = pt.descricao || '';
            b.onclick = function(ev){
                ev.stopPropagation();
                showPopupPonto(pt, b, pontoPx);
            };
            zoomArea.appendChild(b);
        });
    }

    // Campo de texto diretamente no mapa ao clicar
    planta.onclick = function(e) {
        const zoomControls = document.getElementById('zoomControls');
        if (zoomControls.contains(e.target)) return;
        if (isDragging) return;
        let rect = planta.getBoundingClientRect();
        let x = (e.clientX - rect.left) / rect.width;
        let y = (e.clientY - rect.top) / rect.height;
        if(x < 0 || x > 1 || y < 0 || y > 1) return;
        let pontoRefSize = Math.max(18, Math.min(rect.width, rect.height)*0.035);
        let wrap = document.createElement('div');
        wrap.className = "ponto-input-wrap";
        wrap.style.position = "absolute";
        wrap.style.left = `calc(${(x*100).toFixed(4)}% - ${pontoRefSize/2}px)`;
        wrap.style.top  = `calc(${(y*100).toFixed(4)}% - ${pontoRefSize/2}px)`;
        wrap.style.width = "0";
        wrap.style.height = "0";
        wrap.style.zIndex = 100;

        let input = document.createElement('input');
        input.type = "text";
        input.className = "ponto-input";
        input.placeholder = "Informe a solicitação";
        input.style.left = "30px";
        input.style.top = "0";
        wrap.appendChild(input);
        let btns = document.createElement("span");
        btns.className = "status-btns";
        let statusAtual = "novo";
        ["novo","andamento","finalizado"].forEach(st=>{
            let btn = document.createElement("button");
            btn.className = "status-btn" + (st==="novo"?" selected":"");
            btn.innerText = st==="novo"?"Pendente":(st==="andamento"?"Andamento":"Finalizado");
            btn.onclick = function(ev){
                ev.preventDefault(); ev.stopPropagation();
                statusAtual = st;
                Array.from(btns.children).forEach(b=>b.classList.remove("selected"));
                btn.classList.add("selected");
                if(statusAtual=="finalizado"){
                    wrap.remove();
                }
            };
            btns.appendChild(btn);
        });
        wrap.appendChild(btns);

        input.onkeydown = function(ev){
            if(ev.key === "Enter"){
                ev.preventDefault();
                salvarNovoPonto();
            }
            if(ev.key === "Escape"){
                wrap.remove();
            }
        };

        function salvarNovoPonto(){
            let descricao = input.value.trim();
            if(descricao && statusAtual!=="finalizado"){
                fetch('api_pontos.php', {
                    method:'POST',
                    headers:{'Content-Type':'application/json'},
                    body: JSON.stringify({x, y, descricao, status: statusAtual})
                }).then(()=>{
                    wrap.remove();
                    carregarPontos();
                });
            }else{
                wrap.remove();
            }
        }
        input.onblur = function(){
            setTimeout(function(){
                if(document.activeElement !== input) wrap.remove();
            },120);
        }
        zoomArea.appendChild(wrap);
        input.focus();
    };

    // Mostra popup ao clicar no ponto
    function showPopupPonto(pt, pontoDiv, pontoPx){
        // Remove outros popups
        document.querySelectorAll('.popup-ponto').forEach(e=>e.remove());

        // Cria popup
        let pop = document.createElement("div");
        pop.className = "popup-ponto";
        pop.innerHTML = `
            <button class="popup-close" title="Fechar" onclick="this.parentElement.remove();event.stopPropagation();">&times;</button>
            <div class="popup-desc">${pt.descricao ? escapeHtml(pt.descricao) : "<i>Sem descrição</i>"}</div>
            <div class="status-btns-popup">
                <button class="status-btn-popup${pt.status==='novo' ? " selected" : ""}">Pendente</button>
                <button class="status-btn-popup${pt.status==='andamento' ? " selected" : ""}">Andamento</button>
                <button class="status-btn-popup${pt.status==='finalizado' ? " selected" : ""}">Finalizado</button>
            </div>
        `;
        // Posicionar popup à direita do ponto
        let pontoRect = pontoDiv.getBoundingClientRect();
        let areaRect = zoomArea.getBoundingClientRect();
        // Preferencialmente à direita, mas se não couber vai à esquerda
        let left = pontoRect.left - areaRect.left + pontoPx + 10;
        if(left + 240 > areaRect.width) left = pontoRect.left - areaRect.left - 240;
        let top = pontoRect.top - areaRect.top + pontoPx/2 - 30;
        pop.style.left = left+"px";
        pop.style.top = top+"px";

        // Eventos de status
        let statusBtns = pop.querySelectorAll('.status-btn-popup');
        ["novo","andamento","finalizado"].forEach((st,idx)=>{
            statusBtns[idx].onclick = function(ev){
                ev.preventDefault();
                fetch('api_pontos.php', {
                    method:'POST',
                    headers:{'Content-Type':'application/json'},
                    body: JSON.stringify({id: pt.id, status: st})
                }).then(()=>{pop.remove();carregarPontos();});
            };
        });

        // Remove popup ao clicar fora
        setTimeout(()=>{
            function removerPop(ev){
                if(!pop.contains(ev.target)){
                    pop.remove();
                    document.removeEventListener("mousedown",removerPop);
                }
            }
            document.addEventListener("mousedown",removerPop);
        },100);
        zoomArea.appendChild(pop);
    }
    function escapeHtml(unsafe){
        return unsafe.replace(/[<>&"]/g, function(m){
            return ({
                '<':'&lt;',
                '>':'&gt;',
                '&':'&amp;',
                '"':'&quot;'
            })[m];
        });
    }

    document.getElementById('zoomMais').onclick = function(e){ e.stopPropagation(); alterarZoom(1.1); };
    document.getElementById('zoomMenos').onclick = function(e){ e.stopPropagation(); alterarZoom(1/1.1); };

    function toggleFullscreen() {
        if (!document.fullscreenElement) {
            if (containerZoom.requestFullscreen) containerZoom.requestFullscreen();
            else if (containerZoom.webkitRequestFullscreen) containerZoom.webkitRequestFullscreen();
            else if (containerZoom.mozRequestFullScreen) containerZoom.mozRequestFullScreen();
            else if (containerZoom.msRequestFullscreen) containerZoom.msRequestFullscreen();
        } else {
            if (document.exitFullscreen) document.exitFullscreen();
            else if (document.webkitExitFullscreen) document.webkitExitFullscreen();
            else if (document.mozCancelFullScreen) document.mozCancelFullScreen();
            else if (document.msExitFullscreen) document.msExitFullscreen();
        }
    }
    document.getElementById('fullscreenBtn').onclick = function(e){
        e.stopPropagation();
        toggleFullscreen();
    };

    function fullscreenChange() {
        if (document.fullscreenElement === containerZoom) {
            containerZoom.classList.add('fullscreen');
        } else {
            containerZoom.classList.remove('fullscreen');
        }
        ajustarImagemNaTela();
    }
    document.addEventListener('fullscreenchange', fullscreenChange);
    document.addEventListener('webkitfullscreenchange', fullscreenChange);
    document.addEventListener('mozfullscreenchange', fullscreenChange);
    document.addEventListener('MSFullscreenChange', fullscreenChange);

    function ajustarImagemNaTela() {
        setZoomLabel();
        renderizarPontos();
    }

    function onPointerDown(e) {
        if (e.button !== undefined && e.button !== 0) return;
        isDragging = true;
        zoomArea.classList.add('dragging');
        lastDragX = (e.touches ? e.touches[0].clientX : e.clientX);
        lastDragY = (e.touches ? e.touches[0].clientY : e.clientY);
        e.preventDefault();
    }
    function onPointerMove(e) {
        if (!isDragging) return;
        const currX = (e.touches ? e.touches[0].clientX : e.clientX);
        const currY = (e.touches ? e.touches[0].clientY : e.clientY);
        panX += currX - lastDragX;
        panY += currY - lastDragY;
        updateTransform();
        lastDragX = currX;
        lastDragY = currY;
    }
    function onPointerUp(e) {
        isDragging = false;
        zoomArea.classList.remove('dragging');
    }
    zoomArea.addEventListener('mousedown', onPointerDown);
    window.addEventListener('mousemove', onPointerMove);
    window.addEventListener('mouseup', onPointerUp);
    zoomArea.addEventListener('touchstart', onPointerDown, {passive:false});
    window.addEventListener('touchmove', onPointerMove, {passive:false});
    window.addEventListener('touchend', onPointerUp);

    containerZoom.addEventListener('mousedown', function(e){ e.preventDefault(); });
    window.addEventListener('resize', ajustarImagemNaTela);

    planta.onload = function() {
        imgNaturalWidth = planta.naturalWidth;
        imgNaturalHeight = planta.naturalHeight;
        ajustarImagemNaTela();
    };

    carregarPontos();
    </script>
    <?php include 'includes/footer.php'; ?>
</body>
</html>