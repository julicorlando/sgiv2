<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

include 'includes/db.php';

// Recupera o tipo do usuário
$tipo_usuario = 'padrao';
if (isset($_SESSION['usuario_id'])) {
    $stmt = $conn->prepare("SELECT tipo FROM usuarios WHERE id=?");
    $stmt->bind_param("i", $_SESSION['usuario_id']);
    $stmt->execute();
    $stmt->bind_result($tipo_usuario);
    $stmt->fetch();
    $stmt->close();
}

// Verifica se é administrador
if ($tipo_usuario !== 'administrador' && $tipo_usuario !== 'admin') {
    header('Location: index.php');
    exit;
}

// Filtros
$data_inicio = $_GET['data_inicio'] ?? date('Y-m-01');
$data_fim = $_GET['data_fim'] ?? date('Y-m-d');
$usuario_filtro = $_GET['usuario'] ?? '';
$produto_filtro = $_GET['produto'] ?? '';

// Query base para movimentações
$sql = "SELECT m.*, p.nome_produto, p.codigo_barras, u.nome as usuario_nome
        FROM estoque_movimentacoes m
        JOIN estoque_produtos p ON m.produto_id = p.id
        JOIN usuarios u ON m.usuario_id = u.id
        WHERE m.data_movimentacao >= ? AND m.data_movimentacao <= ?";

$params = [$data_inicio . ' 00:00:00', $data_fim . ' 23:59:59'];
$types = 'ss';

if ($usuario_filtro) {
    $sql .= " AND u.nome LIKE ?";
    $params[] = '%' . $usuario_filtro . '%';
    $types .= 's';
}

if ($produto_filtro) {
    $sql .= " AND p.nome_produto LIKE ?";
    $params[] = '%' . $produto_filtro . '%';
    $types .= 's';
}

$sql .= " ORDER BY m.data_movimentacao DESC";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$movimentacoes = $stmt->get_result();
$stmt->close();

// Configurar headers para download CSV
$filename = 'movimentacoes_estoque_' . $data_inicio . '_' . $data_fim . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Criar arquivo CSV
$output = fopen('php://output', 'w');

// Adicionar BOM para UTF-8
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Cabeçalhos do CSV
fputcsv($output, [
    'Data/Hora',
    'Tipo de Movimentação',
    'Produto',
    'Código de Barras',
    'Quantidade',
    'Usuário',
    'Observação'
], ';');

// Dados
while ($mov = $movimentacoes->fetch_assoc()) {
    $tipo_texto = '';
    switch($mov['tipo_movimentacao']) {
        case 'saida':
            $tipo_texto = 'Saída';
            break;
        case 'entrada':
            $tipo_texto = 'Entrada';
            break;
        case 'ajuste':
            $tipo_texto = 'Ajuste';
            break;
    }
    
    fputcsv($output, [
        date('d/m/Y H:i', strtotime($mov['data_movimentacao'])),
        $tipo_texto,
        $mov['nome_produto'],
        $mov['codigo_barras'],
        $mov['quantidade'],
        $mov['usuario_nome'],
        $mov['observacao'] ?: ''
    ], ';');
}

fclose($output);
exit;
?>