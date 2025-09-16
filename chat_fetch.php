<?php
// Parâmetro obrigatório: data (YYYY-MM-DD)
$date = isset($_GET['data']) ? $_GET['data'] : date('Y-m-d');
$file = __DIR__ . "/chat_ti_{$date}.txt";
header("Content-Type: application/json; charset=utf-8");

function ler_mensagens($arquivo) {
    if (!file_exists($arquivo)) return [];
    $linhas = file($arquivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $msgs = [];
    foreach ($linhas as $linha) {
        $partes = explode('|', $linha, 3);
        if (count($partes) === 3) {
            $msgs[] = [
                'usuario' => $partes[0],
                'hora' => $partes[1],
                'mensagem' => $partes[2],
            ];
        }
    }
    return $msgs;
}

echo json_encode(ler_mensagens($file));