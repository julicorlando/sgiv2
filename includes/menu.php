<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Recupera o tipo do usuário
$tipo_usuario = 'padrao';
if (isset($_SESSION['usuario_id'])) {
    include_once 'includes/db.php';
    // Buscar o campo 'nome' em vez de 'usuario'
    $stmt = $conn->prepare("SELECT tipo, nome FROM usuarios WHERE id=?");
    $stmt->bind_param("i", $_SESSION['usuario_id']);
    $stmt->execute();
    $stmt->bind_result($tipo_usuario, $nome_usuario);
    $stmt->fetch();
    $stmt->close();
    $_SESSION['usuario_nome'] = $nome_usuario ?? '';
}
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<style>
    .navbar-carpina {
        background-color: #5bc0eb !important;
    }
    .navbar-carpina .navbar-brand,
    .navbar-carpina .nav-link,
    .navbar-carpina .navbar-text {
        color: #fff !important;
        font-weight: 500;
    }
    .navbar-carpina .nav-link.active,
    .navbar-carpina .nav-link:focus,
    .navbar-carpina .nav-link:hover {
        color: #ffe066 !important;
    }
    .navbar-carpina .btn-outline-danger {
        border-color: #fff;
        color: #fff;
    }
    .navbar-carpina .btn-outline-danger:hover {
        background: #ff6b6b;
        border-color: #ff6b6b;
        color: #fff;
    }
</style>
<nav class="navbar navbar-expand-lg navbar-carpina mb-4">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <img src="logo.png" alt="Logo" width="36" height="36" class="me-2" style="object-fit:contain;">
            <span></span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSistema" aria-controls="navbarSistema" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSistema">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a href="index.php" class="nav-link">🏠 Início</a>
                </li>
                <!-- Link para SCTI - disponível para todos -->
                <li class="nav-item">
                    <a href="http://10.0.18.153/scti/index.php" class="nav-link" target="_blank">🖥️ Abertura de chamados </a>
                </li>
                                                        <!-- Relatórios -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="relatoriosDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            📝 Relatórios de Plantão
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="relatoriosDropdown">
                            <li><a class="dropdown-item" href="plantao.php">🆕 Novo Relatório de Plantão</a></li>
                            <li><a class="dropdown-item" href="plantao_visualizar.php">📚 Relatórios de Plantão</a></li>
                        </ul>
                    </li>
                <?php if ($tipo_usuario === 'funcionario'): ?>
                    <li class="nav-item">
                        <a href="agente.php" class="nav-link">🛠️ Serviços em aberto</a>
                    </li>
                <?php else: ?>
                    <!-- Operações Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="inspecoesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            🛠️ Operações
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="inspecoesDropdown">
                            <li><a class="dropdown-item" href="pendencias.php">🕑 Pendências</a></li>
                            <li><a class="dropdown-item" href="mapa.php">🌍 Mapa estratégico</a></li>
                            <li><a class="dropdown-item" href="manutencao.php">🛠️ Solicitação de Manutenção</a></li>
                            <li><a class="dropdown-item" href="limpeza.php">🧹 Solicitação de Limpeza</a></li>
                            <li><a class="dropdown-item" href="historico.php">📋 Histórico de Serviços</a></li>
                            <li><a class="dropdown-item" href="upload_pdf.php">📄 Histórico CPRH</a></li>
                            <li><a class="dropdown-item" href="inventario.php">🏷️ Patrimônios</a></li>
                        </ul>
                    </li>
                    <!-- Chamados Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="chamadosDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            💻 T.I.
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="chamadosDropdown">
                            <li><a class="dropdown-item" href="ti.php">🆕 Novo chamado para o T.I.</a></li>
                            <li><a class="dropdown-item" href="cadastrar_usuario.php">👤 Cadastro de Usuário</a></li>
                            <li><a class="dropdown-item" href="cadastro_funcionario.php">👷 Cadastro de Funcionário</a></li>
                            <li><a class="dropdown-item" href="editar_senha.php">🔒 Alterar Senha</a></li>
                            <li><a class="dropdown-item" href="ti_chat.php">💬 Chat T.i.</a></li>
                            <li><a class="dropdown-item" href="semanal_jogos.php">⚽ Agenda de jogos</a></li>
                            <li><a class="dropdown-item" href="talentos_upload.php">📤 Upload de Currículo</a></li>
                            <li><a class="dropdown-item" href="talentos_visualizar.php">📁 Visualizar Currículos</a></li>
                        </ul>
                    </li>
                    <!-- Fluxo de Pessoas Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="fluxoDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            🚶‍♂️ Fluxo
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="fluxoDropdown">
                            <li><a class="dropdown-item" href="fluxo.php">✅ Registrar Fluxo</a></li>
                            <li><a class="dropdown-item" href="relatorio.php">📊 Ver Relatório</a></li>
                            <li><a class="dropdown-item" href="relatorio2.php">📈 Comparar Meses</a></li>
                            <li><a class="dropdown-item" href="dia.php">📅 Dia a Dia</a></li>
                            <li><a class="dropdown-item" href="comparativo.php">📉 Comparativo Mês/Ano</a></li>
                            <li><a class="dropdown-item" href="implantar.php">⚙️ Configurações</a></li>
                        </ul>
                    </li>
                    <!-- Salas Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="salasDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            🏢 Reunião
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="salasDropdown">
                            <li><a class="dropdown-item" href="cadastrar_sala.php">➕ Cadastrar Sala</a></li>
                            <li><a class="dropdown-item" href="agendar_sala.php">📅 Agendar Sala</a></li>
                            <li><a class="dropdown-item" href="listar_agendamentos.php">📖 Reservas Ativas</a></li>
                            <li><a class="dropdown-item" href="historico_agendamentos.php">📜 Histórico de Reservas</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>
            <span class="navbar-text me-3">
                👋 Bem-vindo(a) <?= htmlspecialchars($_SESSION['usuario_nome']) ?>
            </span>
            <a href="logout.php" class="btn btn-outline-danger btn-sm">🚪 Sair</a>
        </div>
    </div>
</nav>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>