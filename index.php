<?php
// index.php - Página inicial explicativa do sistema
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Recupera tipo do usuário
$tipo_usuario = 'padrao';
$nome_usuario = '';
if (isset($_SESSION['usuario_id'])) {
    include_once 'includes/db.php';
    $stmt = $conn->prepare("SELECT tipo, nome FROM usuarios WHERE id=?");
    $stmt->bind_param("i", $_SESSION['usuario_id']);
    $stmt->execute();
    $stmt->bind_result($tipo_usuario, $nome_usuario);
    $stmt->fetch();
    $stmt->close();
}

// Processo de alteração de senha (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['alterar_senha'])) {
    header('Content-Type: application/json');
    
    $senha_atual = $_POST['senha_atual'] ?? '';
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    
    // Validações
    if (empty($senha_atual) || empty($nova_senha) || empty($confirmar_senha)) {
        echo json_encode(['success' => false, 'message' => 'Todos os campos são obrigatórios']);
        exit;
    }
    
    if ($nova_senha !== $confirmar_senha) {
        echo json_encode(['success' => false, 'message' => 'Nova senha e confirmação não coincidem']);
        exit;
    }
    
    if (strlen($nova_senha) < 6) {
        echo json_encode(['success' => false, 'message' => 'Nova senha deve ter pelo menos 6 caracteres']);
        exit;
    }
    
    // Verificar senha atual
    $stmt = $conn->prepare("SELECT senha FROM usuarios WHERE id=?");
    $stmt->bind_param("i", $_SESSION['usuario_id']);
    $stmt->execute();
    $stmt->bind_result($senha_hash);
    $stmt->fetch();
    $stmt->close();
    
    if (!password_verify($senha_atual, $senha_hash)) {
        echo json_encode(['success' => false, 'message' => 'Senha atual incorreta']);
        exit;
    }
    
    // Atualizar senha
    $nova_senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE usuarios SET senha=? WHERE id=?");
    $stmt->bind_param("si", $nova_senha_hash, $_SESSION['usuario_id']);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Senha alterada com sucesso!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao alterar senha']);
    }
    $stmt->close();
    exit;
}

// Buscar serviços pendentes/críticos para funcionários
$servicos_pendentes = [];
if ($tipo_usuario === 'funcionario') {
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total_pendentes,
               SUM(CASE WHEN status = 'Pendente' THEN 1 ELSE 0 END) as criticos,
               SUM(CASE WHEN DATEDIFF(CURDATE(), data_criacao) > 3 AND status != 'Finalizado' THEN 1 ELSE 0 END) as atrasados
        FROM manutencao 
        WHERE funcionario_id = ? AND status != 'Finalizado'
    ");
    $stmt->bind_param("i", $_SESSION['usuario_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $servicos_pendentes = $result->fetch_assoc();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Bem-vindo ao Sistema de Inspeções</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        .section-title { margin-top:2rem; font-size:1.3rem; }
        .card { margin-bottom: 1.5rem; }
        
        /* Alert Modal Styles */
        .alert-modal {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 400px;
            background: linear-gradient(135deg, #ff6b6b, #ffa500);
            color: white;
            border: none;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
            padding: 0;
            animation: slideInRight 0.5s ease;
        }
        
        .alert-modal.hidden {
            animation: slideOutRight 0.5s ease;
            opacity: 0;
            pointer-events: none;
        }
        
        .alert-modal-content {
            padding: 20px;
            border-radius: 12px;
        }
        
        .alert-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .alert-modal-close {
            background: none;
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
            opacity: 0.8;
            padding: 0;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .alert-modal-close:hover {
            opacity: 1;
        }
        
        .progress-bar-container {
            height: 4px;
            background: rgba(255,255,255,0.3);
            border-radius: 2px;
            overflow: hidden;
            margin-top: 15px;
        }
        
        .progress-bar {
            height: 100%;
            background: white;
            width: 100%;
            animation: progressCountdown 10s linear;
        }
        
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes slideOutRight {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
        
        @keyframes progressCountdown {
            from { width: 100%; }
            to { width: 0%; }
        }
        
        /* Password Modal Styles */
        .password-modal {
            display: none;
            position: fixed;
            z-index: 9998;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .password-modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border-radius: 10px;
            width: 400px;
            max-width: 90%;
        }
        
        .close-password-modal {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close-password-modal:hover {
            color: black;
        }
    </style>
</head>
<body>
<?php include 'includes/menu.php'; ?>

<!-- Alert Modal for Employee Services -->
<?php if ($tipo_usuario === 'funcionario' && ($servicos_pendentes['total_pendentes'] > 0 || $servicos_pendentes['criticos'] > 0 || $servicos_pendentes['atrasados'] > 0)): ?>
<div id="alertModal" class="alert-modal">
    <div class="alert-modal-content">
        <div class="alert-modal-header">
            <h5><i class="fas fa-exclamation-triangle"></i> Atenção, <?= htmlspecialchars($nome_usuario) ?>!</h5>
            <button type="button" class="alert-modal-close" onclick="closeAlert()">&times;</button>
        </div>
        <div class="alert-modal-body">
            <p><strong>Você possui serviços que requerem atenção:</strong></p>
            <ul class="mb-2">
                <?php if ($servicos_pendentes['total_pendentes'] > 0): ?>
                    <li><?= $servicos_pendentes['total_pendentes'] ?> serviço(s) em aberto</li>
                <?php endif; ?>
                <?php if ($servicos_pendentes['criticos'] > 0): ?>
                    <li><?= $servicos_pendentes['criticos'] ?> serviço(s) crítico(s)</li>
                <?php endif; ?>
                <?php if ($servicos_pendentes['atrasados'] > 0): ?>
                    <li><?= $servicos_pendentes['atrasados'] ?> serviço(s) atrasado(s)</li>
                <?php endif; ?>
            </ul>
            <small>Acesse "Serviços em Aberto" no menu para ver detalhes.</small>
        </div>
        <div class="progress-bar-container">
            <div class="progress-bar"></div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Password Change Modal -->
<div id="passwordModal" class="password-modal">
    <div class="password-modal-content">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5>Alterar Senha</h5>
            <span class="close-password-modal">&times;</span>
        </div>
        <form id="passwordForm">
            <div class="mb-3">
                <label for="senha_atual" class="form-label">Senha Atual:</label>
                <input type="password" class="form-control" id="senha_atual" name="senha_atual" required>
            </div>
            <div class="mb-3">
                <label for="nova_senha" class="form-label">Nova Senha:</label>
                <input type="password" class="form-control" id="nova_senha" name="nova_senha" required minlength="6">
                <small class="form-text text-muted">Mínimo 6 caracteres</small>
            </div>
            <div class="mb-3">
                <label for="confirmar_senha" class="form-label">Confirmar Nova Senha:</label>
                <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" required>
            </div>
            <div class="d-flex justify-content-between">
                <button type="button" class="btn btn-secondary" onclick="closePasswordModal()">Cancelar</button>
                <button type="submit" class="btn btn-primary">Alterar Senha</button>
            </div>
        </form>
        <div id="passwordMessage" class="mt-3"></div>
    </div>
</div>
<div class="container mt-4 mb-5">
    <h1 class="mb-4">Bem-vindo ao Sistema de Gestão de Operações</h1>
    <div class="alert alert-secondary">
        <b>Instruções Gerais:</b><br>
        Este sistema foi desenvolvido para facilitar o registro, controle e acompanhamento das rotinas de <b>manutenção</b>, <b>limpeza</b>, <b>relatórios de plantão</b>, <b>chamados de TI</b> e a <b>gestão do fluxo de pessoas</b> no shopping.<br><br>
        <ul>
            <li>Utilize o menu superior para acessar cada módulo do sistema.</li>
            <li>Preencha os formulários de registro de acordo com a ocorrência ou necessidade.</li>
            <li>Consulte históricos e relatórios para acompanhamento das operações.</li>
            <li>Em caso de dúvidas, utilize a área de suporte ou contate o administrador.</li>
        </ul>
    </div>

    <?php if ($tipo_usuario === 'administrador' || $tipo_usuario === 'admin'): ?>
        <div class="card">
            <div class="card-header bg-primary text-white">
                <strong>Informações Administrativas</strong>
            </div>
            <div class="card-body">
                <p>Como administrador, você pode visualizar todas as informações já cadastradas no sistema, acessar relatórios completos, editar registros e gerenciar usuários. Utilize o menu para acessar áreas exclusivas e realizar consultas detalhadas.</p>
                <ul>
                    <li>Acesse <b>Histórico de Serviços</b> para consultar e filtrar todas as operações registradas.</li>
                    <li>Utilize <b>Cadastro de Funcionário</b> para incluir ou editar funcionários do sistema.</li>
                    <li>Veja <b>Relatórios de Plantão</b> para acompanhar a rotina dos funcionários.</li>
                </ul>
            </div>
        </div>
    <?php elseif ($tipo_usuario === 'funcionario'): ?>
        <div class="card">
            <div class="card-header bg-success text-white">
                <strong>Área do Funcionário</strong>
            </div>
            <div class="card-body">
                <ul>
                    <li><b>Botão "Abertura de Chamados"</b> acima permite abrir solicitações diretamente para o setor de TI.</li>
                    <li><b>Botão "Serviços em Aberto"</b> mostra para você todas as tarefas de manutenção e operações que estão pendentes ou em andamento e que foram atribuídas ao seu usuário.</li>
                    <li>Após concluir o serviço, marque-o como finalizado para atualizar o status.</li>
                </ul>
                <div class="alert alert-warning mt-3">
                    Atenção: Fique atento às atualizações dos seus serviços e utilize os botões para acessar rapidamente suas tarefas e chamados.
                </div>
                <button type="button" class="btn btn-outline-primary mt-2" onclick="openPasswordModal()">
                    <i class="fas fa-key"></i> Alterar Minha Senha
                </button>
            </div>
        </div>
    <?php endif; ?>

    <div class="alert alert-info mt-4">
        Dica: Salve frequentemente e revise os dados inseridos antes de finalizar cada registro. Para melhor experiência, utilize o sistema preferencialmente em computadores desktop ou notebooks.
    </div>
</div>
<?php include 'includes/footer.php'; ?>

<script>
// Alert Modal Functions
function closeAlert() {
    const modal = document.getElementById('alertModal');
    if (modal) {
        modal.classList.add('hidden');
        setTimeout(() => modal.remove(), 500);
    }
}

// Auto-close alert after 10 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alertModal = document.getElementById('alertModal');
    if (alertModal) {
        setTimeout(() => {
            closeAlert();
        }, 10000);
    }
});

// Password Modal Functions
function openPasswordModal() {
    document.getElementById('passwordModal').style.display = 'block';
    document.getElementById('passwordForm').reset();
    document.getElementById('passwordMessage').innerHTML = '';
}

function closePasswordModal() {
    document.getElementById('passwordModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('passwordModal');
    if (event.target === modal) {
        closePasswordModal();
    }
}

// Close modal with X button
document.querySelector('.close-password-modal').onclick = closePasswordModal;

// Password Form Submission
document.getElementById('passwordForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData();
    formData.append('alterar_senha', '1');
    formData.append('senha_atual', document.getElementById('senha_atual').value);
    formData.append('nova_senha', document.getElementById('nova_senha').value);
    formData.append('confirmar_senha', document.getElementById('confirmar_senha').value);
    
    fetch('index.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        const messageDiv = document.getElementById('passwordMessage');
        if (data.success) {
            messageDiv.innerHTML = '<div class="alert alert-success">' + data.message + '</div>';
            document.getElementById('passwordForm').reset();
            setTimeout(() => {
                closePasswordModal();
            }, 2000);
        } else {
            messageDiv.innerHTML = '<div class="alert alert-danger">' + data.message + '</div>';
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        document.getElementById('passwordMessage').innerHTML = '<div class="alert alert-danger">Erro ao processar solicitação</div>';
    });
});
</script>

</body>
</html>