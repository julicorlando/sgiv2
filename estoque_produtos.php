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

$mensagem = '';
$erro = '';

// Processar ações do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    
    if ($acao === 'adicionar') {
        $codigo_barras = trim($_POST['codigo_barras']);
        $nome_produto = trim($_POST['nome_produto']);
        $quantidade_inicial = intval($_POST['quantidade_inicial']);
        
        if (empty($codigo_barras) || empty($nome_produto) || $quantidade_inicial < 0) {
            $erro = 'Todos os campos são obrigatórios e a quantidade deve ser maior ou igual a zero.';
        } else {
            // Verificar se o código de barras já existe
            $stmt = $conn->prepare("SELECT id FROM estoque_produtos WHERE codigo_barras = ?");
            $stmt->bind_param("s", $codigo_barras);
            $stmt->execute();
            $resultado = $stmt->get_result();
            
            if ($resultado->num_rows > 0) {
                $erro = 'Este código de barras já está cadastrado.';
            } else {
                // Inserir novo produto
                $stmt = $conn->prepare("INSERT INTO estoque_produtos (codigo_barras, nome_produto, quantidade_inicial, quantidade_atual, usuario_criacao) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("ssiii", $codigo_barras, $nome_produto, $quantidade_inicial, $quantidade_inicial, $_SESSION['usuario_id']);
                
                if ($stmt->execute()) {
                    $produto_id = $conn->insert_id;
                    
                    // Registrar movimentação inicial se quantidade > 0
                    if ($quantidade_inicial > 0) {
                        $stmt_mov = $conn->prepare("INSERT INTO estoque_movimentacoes (produto_id, tipo_movimentacao, quantidade, usuario_id, observacao) VALUES (?, 'entrada', ?, ?, 'Estoque inicial')");
                        $stmt_mov->bind_param("iii", $produto_id, $quantidade_inicial, $_SESSION['usuario_id']);
                        $stmt_mov->execute();
                        $stmt_mov->close();
                    }
                    
                    $mensagem = 'Produto cadastrado com sucesso!';
                } else {
                    $erro = 'Erro ao cadastrar produto.';
                }
            }
            $stmt->close();
        }
    } elseif ($acao === 'excluir') {
        $produto_id = intval($_POST['produto_id']);
        
        // Verificar se o produto existe e se tem movimentações
        $stmt = $conn->prepare("SELECT COUNT(*) FROM estoque_movimentacoes WHERE produto_id = ?");
        $stmt->bind_param("i", $produto_id);
        $stmt->execute();
        $stmt->bind_result($total_movimentacoes);
        $stmt->fetch();
        $stmt->close();
        
        if ($total_movimentacoes > 0) {
            $erro = 'Não é possível excluir produtos que possuem movimentações. Use a opção de desativar.';
        } else {
            $stmt = $conn->prepare("DELETE FROM estoque_produtos WHERE id = ?");
            $stmt->bind_param("i", $produto_id);
            
            if ($stmt->execute()) {
                $mensagem = 'Produto excluído com sucesso!';
            } else {
                $erro = 'Erro ao excluir produto.';
            }
            $stmt->close();
        }
    } elseif ($acao === 'desativar') {
        $produto_id = intval($_POST['produto_id']);
        
        $stmt = $conn->prepare("UPDATE estoque_produtos SET ativo = FALSE WHERE id = ?");
        $stmt->bind_param("i", $produto_id);
        
        if ($stmt->execute()) {
            $mensagem = 'Produto desativado com sucesso!';
        } else {
            $erro = 'Erro ao desativar produto.';
        }
        $stmt->close();
    } elseif ($acao === 'ativar') {
        $produto_id = intval($_POST['produto_id']);
        
        $stmt = $conn->prepare("UPDATE estoque_produtos SET ativo = TRUE WHERE id = ?");
        $stmt->bind_param("i", $produto_id);
        
        if ($stmt->execute()) {
            $mensagem = 'Produto ativado com sucesso!';
        } else {
            $erro = 'Erro ao ativar produto.';
        }
        $stmt->close();
    }
}

// Buscar produtos cadastrados
$sql = "SELECT p.*, u.nome as usuario_nome FROM estoque_produtos p 
        LEFT JOIN usuarios u ON p.usuario_criacao = u.id 
        ORDER BY p.ativo DESC, p.nome_produto ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Produtos - Almoxarifado</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/quagga/0.12.1/quagga.min.js"></script>
    <style>
        .scanner-container {
            width: 100%;
            max-width: 400px;
            height: 300px;
            margin: 20px auto;
            border: 2px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            display: none;
        }
        .btn-scanner {
            background-color: #28a745;
            border-color: #28a745;
        }
        .btn-scanner:hover {
            background-color: #218838;
            border-color: #1e7e34;
        }
        .produto-inativo {
            opacity: 0.6;
            background-color: #f8f9fa;
        }
        .status-badge {
            font-size: 0.75em;
            padding: 0.25em 0.5em;
        }
    </style>
</head>
<body>
<?php include 'includes/menu.php'; ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <h2>🏪 Gestão de Produtos - Almoxarifado</h2>
            <p class="text-muted">Cadastro e gerenciamento de produtos do almoxarifado (apenas administradores)</p>
            
            <?php if ($mensagem): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($mensagem) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($erro): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($erro) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">📦 Cadastrar Novo Produto</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="acao" value="adicionar">
                        
                        <div class="mb-3">
                            <label for="codigo_barras" class="form-label">Código de Barras *</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="codigo_barras" name="codigo_barras" required maxlength="255">
                                <button type="button" class="btn btn-scanner" id="btnScanner">
                                    📷 Escanear
                                </button>
                            </div>
                            <div class="form-text">Digite ou escaneie o código de barras do produto</div>
                        </div>
                        
                        <div id="scanner-container" class="scanner-container">
                            <div id="scanner-controls" class="text-center p-2">
                                <button type="button" class="btn btn-sm btn-secondary" id="btnStopScanner">Parar Scanner</button>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="nome_produto" class="form-label">Nome do Produto *</label>
                            <input type="text" class="form-control" id="nome_produto" name="nome_produto" required maxlength="255">
                        </div>
                        
                        <div class="mb-3">
                            <label for="quantidade_inicial" class="form-label">Quantidade Inicial *</label>
                            <input type="number" class="form-control" id="quantidade_inicial" name="quantidade_inicial" required min="0" max="99999">
                            <div class="form-text">Quantidade de produtos a serem adicionados ao estoque</div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            ➕ Cadastrar Produto
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">📋 Produtos Cadastrados</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Produto</th>
                                    <th>Estoque</th>
                                    <th>Status</th>
                                    <th>Cadastrado</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result && $result->num_rows > 0): ?>
                                    <?php while ($produto = $result->fetch_assoc()): ?>
                                        <tr class="<?= !$produto['ativo'] ? 'produto-inativo' : '' ?>">
                                            <td>
                                                <code><?= htmlspecialchars($produto['codigo_barras']) ?></code>
                                            </td>
                                            <td><?= htmlspecialchars($produto['nome_produto']) ?></td>
                                            <td>
                                                <span class="badge <?= $produto['quantidade_atual'] > 0 ? 'bg-success' : 'bg-warning' ?>">
                                                    <?= $produto['quantidade_atual'] ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge status-badge <?= $produto['ativo'] ? 'bg-success' : 'bg-secondary' ?>">
                                                    <?= $produto['ativo'] ? 'Ativo' : 'Inativo' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small>
                                                    <?= date('d/m/Y', strtotime($produto['data_criacao'])) ?><br>
                                                    por <?= htmlspecialchars($produto['usuario_nome']) ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <?php if ($produto['ativo']): ?>
                                                        <form method="POST" class="d-inline" onsubmit="return confirm('Desativar este produto?')">
                                                            <input type="hidden" name="acao" value="desativar">
                                                            <input type="hidden" name="produto_id" value="<?= $produto['id'] ?>">
                                                            <button type="submit" class="btn btn-warning btn-sm" title="Desativar">
                                                                ⏸️
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="acao" value="ativar">
                                                            <input type="hidden" name="produto_id" value="<?= $produto['id'] ?>">
                                                            <button type="submit" class="btn btn-success btn-sm" title="Ativar">
                                                                ▶️
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                    
                                                    <form method="POST" class="d-inline" onsubmit="return confirm('Excluir permanentemente este produto? Esta ação não pode ser desfeita!')">
                                                        <input type="hidden" name="acao" value="excluir">
                                                        <input type="hidden" name="produto_id" value="<?= $produto['id'] ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm" title="Excluir">
                                                            🗑️
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">
                                            Nenhum produto cadastrado ainda.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnScanner = document.getElementById('btnScanner');
    const btnStopScanner = document.getElementById('btnStopScanner');
    const scannerContainer = document.getElementById('scanner-container');
    const codigoBarrasInput = document.getElementById('codigo_barras');
    
    let scannerActive = false;
    
    btnScanner.addEventListener('click', function() {
        if (scannerActive) return;
        
        scannerContainer.style.display = 'block';
        
        Quagga.init({
            inputStream: {
                name: "Live",
                type: "LiveStream",
                target: scannerContainer,
                constraints: {
                    width: 400,
                    height: 300,
                    facingMode: "environment"
                }
            },
            decoder: {
                readers: [
                    "code_128_reader",
                    "ean_reader",
                    "ean_8_reader",
                    "code_39_reader",
                    "code_39_vin_reader",
                    "codabar_reader",
                    "upc_reader",
                    "upc_e_reader",
                    "i2of5_reader"
                ]
            },
            locate: true,
            locator: {
                halfSample: true,
                patchSize: "medium"
            }
        }, function(err) {
            if (err) {
                console.error('Erro ao inicializar scanner:', err);
                alert('Erro ao inicializar o scanner. Verifique se a câmera está disponível.');
                scannerContainer.style.display = 'none';
                return;
            }
            
            console.log("Scanner inicializado");
            Quagga.start();
            scannerActive = true;
            btnScanner.textContent = '📷 Scanner Ativo';
            btnScanner.disabled = true;
        });
        
        Quagga.onDetected(function(result) {
            const code = result.codeResult.code;
            codigoBarrasInput.value = code;
            
            // Parar o scanner após detectar um código
            Quagga.stop();
            scannerContainer.style.display = 'none';
            scannerActive = false;
            btnScanner.textContent = '📷 Escanear';
            btnScanner.disabled = false;
            
            // Focar no próximo campo
            document.getElementById('nome_produto').focus();
        });
    });
    
    btnStopScanner.addEventListener('click', function() {
        if (scannerActive) {
            Quagga.stop();
            scannerContainer.style.display = 'none';
            scannerActive = false;
            btnScanner.textContent = '📷 Escanear';
            btnScanner.disabled = false;
        }
    });
});
</script>

</body>
</html>