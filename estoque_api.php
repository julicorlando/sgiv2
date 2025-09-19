<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['sucesso' => false, 'erro' => 'Não autenticado']);
    exit;
}

include 'includes/db.php';

header('Content-Type: application/json');

$acao = $_POST['acao'] ?? '';

if ($acao === 'buscar_produto') {
    $codigo_barras = trim($_POST['codigo_barras'] ?? '');
    
    if (empty($codigo_barras)) {
        echo json_encode(['sucesso' => false, 'erro' => 'Código de barras é obrigatório']);
        exit;
    }
    
    $stmt = $conn->prepare("SELECT id, codigo_barras, nome_produto, quantidade_atual, ativo FROM estoque_produtos WHERE codigo_barras = ?");
    $stmt->bind_param("s", $codigo_barras);
    $stmt->execute();
    $result = $stmt->get_result();
    $produto = $result->fetch_assoc();
    $stmt->close();
    
    if ($produto) {
        echo json_encode([
            'sucesso' => true,
            'produto' => [
                'id' => $produto['id'],
                'codigo_barras' => $produto['codigo_barras'],
                'nome_produto' => $produto['nome_produto'],
                'quantidade_atual' => (int)$produto['quantidade_atual'],
                'ativo' => (bool)$produto['ativo']
            ]
        ]);
    } else {
        echo json_encode(['sucesso' => false, 'erro' => 'Produto não encontrado']);
    }
    
} else {
    echo json_encode(['sucesso' => false, 'erro' => 'Ação não reconhecida']);
}
?>