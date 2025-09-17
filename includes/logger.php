<?php
// Sistema de logs para alterações sensíveis
function log_change($conn, $usuario_id, $tabela, $registro_id, $campo_alterado, $valor_anterior, $valor_novo) {
    // Criar tabela se não existir
    $conn->query("CREATE TABLE IF NOT EXISTS system_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT NOT NULL,
        tabela VARCHAR(50) NOT NULL,
        registro_id INT NOT NULL,
        campo_alterado VARCHAR(100) NOT NULL,
        valor_anterior TEXT,
        valor_novo TEXT,
        data_alteracao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_data (data_alteracao),
        INDEX idx_tabela (tabela),
        INDEX idx_usuario (usuario_id)
    )");
    
    // Inserir log
    $stmt = $conn->prepare("INSERT INTO system_logs (usuario_id, tabela, registro_id, campo_alterado, valor_anterior, valor_novo) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isisss", $usuario_id, $tabela, $registro_id, $campo_alterado, $valor_anterior, $valor_novo);
    $stmt->execute();
    $stmt->close();
}

function get_user_name_by_id($conn, $user_id) {
    if (!$user_id) return 'Não atribuído';
    $stmt = $conn->prepare("SELECT nome FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($nome);
    $stmt->fetch();
    $stmt->close();
    return $nome ?: 'Usuário não encontrado';
}
?>