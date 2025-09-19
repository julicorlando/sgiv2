<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

include 'includes/db.php';

// Recupera informações do usuário
$usuario_id = $_SESSION['usuario_id'];
$stmt = $conn->prepare("SELECT nome, tipo FROM usuarios WHERE id=?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$stmt->bind_result($nome_usuario, $tipo_usuario);
$stmt->fetch();
$stmt->close();

$mensagem = '';
$erro = '';

// Processar retirada de produto
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo_barras = trim($_POST['codigo_barras']);
    $quantidade = intval($_POST['quantidade']);
    $observacao = trim($_POST['observacao']);
    
    if (empty($codigo_barras) || $quantidade <= 0) {
        $erro = 'Código de barras e quantidade são obrigatórios. Quantidade deve ser maior que zero.';
    } else {
        // Buscar produto pelo código de barras
        $stmt = $conn->prepare("SELECT id, nome_produto, quantidade_atual, ativo FROM estoque_produtos WHERE codigo_barras = ?");
        $stmt->bind_param("s", $codigo_barras);
        $stmt->execute();
        $result = $stmt->get_result();
        $produto = $result->fetch_assoc();
        $stmt->close();
        
        if (!$produto) {
            $erro = 'Produto não encontrado com este código de barras.';
        } elseif (!$produto['ativo']) {
            $erro = 'Este produto está inativo e não pode ser retirado.';
        } elseif ($produto['quantidade_atual'] < $quantidade) {
            $erro = "Quantidade insuficiente em estoque. Disponível: {$produto['quantidade_atual']}";
        } else {
            // Iniciar transação
            $conn->begin_transaction();
            
            try {
                // Registrar movimentação de saída
                $stmt = $conn->prepare("INSERT INTO estoque_movimentacoes (produto_id, tipo_movimentacao, quantidade, usuario_id, observacao) VALUES (?, 'saida', ?, ?, ?)");
                $stmt->bind_param("iiis", $produto['id'], $quantidade, $usuario_id, $observacao);
                $stmt->execute();
                $stmt->close();
                
                // Atualizar quantidade do produto
                $nova_quantidade = $produto['quantidade_atual'] - $quantidade;
                $stmt = $conn->prepare("UPDATE estoque_produtos SET quantidade_atual = ? WHERE id = ?");
                $stmt->bind_param("ii", $nova_quantidade, $produto['id']);
                $stmt->execute();
                $stmt->close();
                
                $conn->commit();
                $mensagem = "Retirada registrada com sucesso! Produto: {$produto['nome_produto']}, Quantidade: {$quantidade}";
                
                // Limpar campos após sucesso
                $_POST = [];
                
            } catch (Exception $e) {
                $conn->rollback();
                $erro = 'Erro ao registrar retirada. Tente novamente.';
            }
        }
    }
}

// Buscar últimas retiradas do usuário atual
$sql_retiradas = "SELECT m.*, p.nome_produto, p.codigo_barras 
                  FROM estoque_movimentacoes m 
                  JOIN estoque_produtos p ON m.produto_id = p.id 
                  WHERE m.usuario_id = ? AND m.tipo_movimentacao = 'saida'
                  ORDER BY m.data_movimentacao DESC 
                  LIMIT 10";
$stmt = $conn->prepare($sql_retiradas);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$minhas_retiradas = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Retirada de Produtos - Almoxarifado</title>
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
        .produto-info {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
            display: none;
        }
        .produto-encontrado {
            display: block;
            border-color: #28a745;
            background-color: #d4edda;
        }
        .estoque-baixo {
            color: #dc3545;
            font-weight: bold;
        }
        .estoque-normal {
            color: #28a745;
            font-weight: bold;
        }
    </style>
</head>
<body>
<?php include 'includes/menu.php'; ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <h2>📦 Retirada de Produtos - Almoxarifado</h2>
            <p class="text-muted">Registre a retirada de produtos do estoque</p>
            
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
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">🛒 Registrar Retirada</h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="formRetirada">
                        <div class="mb-3">
                            <label for="codigo_barras" class="form-label">Código de Barras do Produto *</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="codigo_barras" name="codigo_barras" 
                                       value="<?= htmlspecialchars($_POST['codigo_barras'] ?? '') ?>" 
                                       required maxlength="255" autocomplete="off">
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
                        
                        <div id="produto-info" class="produto-info">
                            <h6>📋 Informações do Produto</h6>
                            <div id="produto-detalhes"></div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="quantidade" class="form-label">Quantidade a Retirar *</label>
                            <input type="number" class="form-control" id="quantidade" name="quantidade" 
                                   value="<?= htmlspecialchars($_POST['quantidade'] ?? '') ?>" 
                                   required min="1" max="9999">
                            <div class="form-text">Quantidade de produtos a serem retirados</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="observacao" class="form-label">Observação</label>
                            <textarea class="form-control" id="observacao" name="observacao" rows="3" maxlength="500"><?= htmlspecialchars($_POST['observacao'] ?? '') ?></textarea>
                            <div class="form-text">Informações adicionais sobre a retirada (opcional)</div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="bg-light p-3 rounded">
                                <small><strong>Responsável pela retirada:</strong> <?= htmlspecialchars($nome_usuario) ?></small><br>
                                <small><strong>Data/Hora:</strong> <?= date('d/m/Y H:i') ?></small>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" id="btnRetirar">
                            📤 Registrar Retirada
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">📋 Minhas Últimas Retiradas</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>Data/Hora</th>
                                    <th>Produto</th>
                                    <th>Qtd</th>
                                    <th>Observação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($minhas_retiradas && $minhas_retiradas->num_rows > 0): ?>
                                    <?php while ($retirada = $minhas_retiradas->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <small><?= date('d/m/Y H:i', strtotime($retirada['data_movimentacao'])) ?></small>
                                            </td>
                                            <td>
                                                <strong><?= htmlspecialchars($retirada['nome_produto']) ?></strong><br>
                                                <small class="text-muted"><?= htmlspecialchars($retirada['codigo_barras']) ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?= $retirada['quantidade'] ?></span>
                                            </td>
                                            <td>
                                                <small><?= htmlspecialchars($retirada['observacao'] ?: '-') ?></small>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">
                                            Nenhuma retirada registrada ainda.
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
    const produtoInfo = document.getElementById('produto-info');
    const produtoDetalhes = document.getElementById('produto-detalhes');
    const quantidadeInput = document.getElementById('quantidade');
    const btnRetirar = document.getElementById('btnRetirar');
    
    let scannerActive = false;
    let produtoAtual = null;
    
    // Função para buscar produto por código de barras
    function buscarProduto(codigo) {
        if (!codigo.trim()) {
            produtoInfo.classList.remove('produto-encontrado');
            produtoInfo.style.display = 'none';
            btnRetirar.disabled = false;
            return;
        }
        
        fetch('estoque_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'acao=buscar_produto&codigo_barras=' + encodeURIComponent(codigo)
        })
        .then(response => response.json())
        .then(data => {
            if (data.sucesso && data.produto) {
                produtoAtual = data.produto;
                
                const estoqueClass = data.produto.quantidade_atual > 10 ? 'estoque-normal' : 'estoque-baixo';
                
                produtoDetalhes.innerHTML = `
                    <div class="row">
                        <div class="col-12">
                            <strong>${data.produto.nome_produto}</strong><br>
                            <small class="text-muted">Código: ${data.produto.codigo_barras}</small>
                        </div>
                        <div class="col-6">
                            <small><strong>Estoque atual:</strong></small><br>
                            <span class="${estoqueClass}">${data.produto.quantidade_atual} unidades</span>
                        </div>
                        <div class="col-6">
                            <small><strong>Status:</strong></small><br>
                            <span class="badge ${data.produto.ativo ? 'bg-success' : 'bg-secondary'}">
                                ${data.produto.ativo ? 'Ativo' : 'Inativo'}
                            </span>
                        </div>
                    </div>
                `;
                
                produtoInfo.classList.add('produto-encontrado');
                produtoInfo.style.display = 'block';
                
                // Atualizar quantidade máxima
                quantidadeInput.max = data.produto.quantidade_atual;
                
                // Desabilitar retirada se produto inativo ou sem estoque
                if (!data.produto.ativo || data.produto.quantidade_atual <= 0) {
                    btnRetirar.disabled = true;
                    if (!data.produto.ativo) {
                        produtoDetalhes.innerHTML += '<div class="alert alert-warning mt-2 mb-0">Produto inativo - não pode ser retirado</div>';
                    } else {
                        produtoDetalhes.innerHTML += '<div class="alert alert-danger mt-2 mb-0">Produto sem estoque</div>';
                    }
                } else {
                    btnRetirar.disabled = false;
                }
                
            } else {
                produtoInfo.classList.remove('produto-encontrado');
                produtoInfo.style.display = 'none';
                btnRetirar.disabled = false;
                produtoAtual = null;
            }
        })
        .catch(error => {
            console.error('Erro ao buscar produto:', error);
            produtoInfo.classList.remove('produto-encontrado');
            produtoInfo.style.display = 'none';
        });
    }
    
    // Buscar produto quando o código de barras mudar
    codigoBarrasInput.addEventListener('input', function() {
        buscarProduto(this.value);
    });
    
    // Validar quantidade
    quantidadeInput.addEventListener('input', function() {
        if (produtoAtual && parseInt(this.value) > produtoAtual.quantidade_atual) {
            this.value = produtoAtual.quantidade_atual;
        }
    });
    
    // Scanner de código de barras
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
            
            Quagga.start();
            scannerActive = true;
            btnScanner.textContent = '📷 Scanner Ativo';
            btnScanner.disabled = true;
        });
        
        Quagga.onDetected(function(result) {
            const code = result.codeResult.code;
            codigoBarrasInput.value = code;
            buscarProduto(code);
            
            // Parar o scanner após detectar um código
            Quagga.stop();
            scannerContainer.style.display = 'none';
            scannerActive = false;
            btnScanner.textContent = '📷 Escanear';
            btnScanner.disabled = false;
            
            // Focar no campo quantidade
            quantidadeInput.focus();
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
    
    // Buscar produto se há código inicial
    if (codigoBarrasInput.value) {
        buscarProduto(codigoBarrasInput.value);
    }
});
</script>

</body>
</html>