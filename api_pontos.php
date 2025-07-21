<?php
header('Content-Type: application/json');
$pdo = new PDO("mysql:host=localhost;dbname=sistema_inspecao;charset=utf8", "root", "");

// Salvar novo ponto
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (isset($data['x'], $data['y'], $data['descricao'])) {
        $stmt = $pdo->prepare("INSERT INTO planta_pontos (x, y, descricao, status) VALUES (?, ?, ?, 'novo')");
        $stmt->execute([$data['x'], $data['y'], $data['descricao']]);
        echo json_encode(['ok'=>true]);
        exit;
    }
    // Atualizar status
    if (isset($data['id'], $data['status'])) {
        if ($data['status'] === 'finalizado') {
            $stmt = $pdo->prepare("DELETE FROM planta_pontos WHERE id=?");
            $stmt->execute([$data['id']]);
        } else {
            $stmt = $pdo->prepare("UPDATE planta_pontos SET status=? WHERE id=?");
            $stmt->execute([$data['status'], $data['id']]);
        }
        echo json_encode(['ok'=>true]);
        exit;
    }
}

// Listar pontos
$pontos = $pdo->query("SELECT * FROM planta_pontos")->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($pontos);