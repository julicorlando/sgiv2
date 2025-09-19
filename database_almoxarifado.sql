-- Tabela para cadastro de produtos do almoxarifado
CREATE TABLE IF NOT EXISTS estoque_produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo_barras VARCHAR(255) UNIQUE NOT NULL,
    nome_produto VARCHAR(255) NOT NULL,
    quantidade_inicial INT NOT NULL DEFAULT 0,
    quantidade_atual INT NOT NULL DEFAULT 0,
    usuario_criacao INT NOT NULL,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ativo BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (usuario_criacao) REFERENCES usuarios(id)
);

-- Tabela para registrar movimentações do estoque (saídas)
CREATE TABLE IF NOT EXISTS estoque_movimentacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produto_id INT NOT NULL,
    tipo_movimentacao ENUM('saida', 'entrada', 'ajuste') DEFAULT 'saida',
    quantidade INT NOT NULL,
    usuario_id INT NOT NULL,
    data_movimentacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    observacao TEXT,
    FOREIGN KEY (produto_id) REFERENCES estoque_produtos(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Índices para melhor performance
CREATE INDEX idx_estoque_produtos_codigo_barras ON estoque_produtos(codigo_barras);
CREATE INDEX idx_estoque_produtos_nome ON estoque_produtos(nome_produto);
CREATE INDEX idx_estoque_movimentacoes_produto ON estoque_movimentacoes(produto_id);
CREATE INDEX idx_estoque_movimentacoes_usuario ON estoque_movimentacoes(usuario_id);
CREATE INDEX idx_estoque_movimentacoes_data ON estoque_movimentacoes(data_movimentacao);