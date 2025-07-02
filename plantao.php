<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

include 'includes/db.php';

// Pega o nome do usuário logado
$usuario_nome = '';
$stmt_nome = $conn->prepare("SELECT usuario FROM usuarios WHERE id = ?");
$stmt_nome->bind_param("i", $_SESSION['usuario_id']);
$stmt_nome->execute();
$stmt_nome->bind_result($usuario_nome);
$stmt_nome->fetch();
$stmt_nome->close();

$mensagem = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data_inicio = $_POST['data_inicio'];
    $data_fim = $_POST['data_fim'];
    // Agora pega automaticamente
    $responsavel = $usuario_nome;

    // Campos do formulário
    $campos = [
        'banheiros_obs','banheiros_acao','cestos_obs','cestos_acao','praca_obs','praca_acao','mall_obs','mall_acao',
        'corredores_obs','corredores_acao','externa_obs','externa_acao','seguranca_obs','seguranca_acao',
        'manutencao_obs','manutencao_acao','marketing_obs','marketing_acao','ocorrencia_obs','ocorrencia_acao','ocorrencia_contato',
        'estacionamento_obs','estacionamento_acao'
    ];

    $valores = [];
    foreach ($campos as $c) {
        $valores[$c] = isset($_POST[$c]) ? $_POST[$c] : '';
    }

    $sql = "INSERT INTO plantao (
        data_inicio, data_fim, responsavel,
        banheiros_obs, banheiros_acao,
        cestos_obs, cestos_acao,
        praca_obs, praca_acao,
        mall_obs, mall_acao,
        corredores_obs, corredores_acao,
        externa_obs, externa_acao,
        seguranca_obs, seguranca_acao,
        manutencao_obs, manutencao_acao,
        marketing_obs, marketing_acao,
        ocorrencia_obs, ocorrencia_acao, ocorrencia_contato,
        estacionamento_obs, estacionamento_acao,
        usuario_id, data_criacao
    ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "sssssssssssssssssssssssssssi",
        $data_inicio, $data_fim, $responsavel,
        $valores['banheiros_obs'], $valores['banheiros_acao'],
        $valores['cestos_obs'], $valores['cestos_acao'],
        $valores['praca_obs'], $valores['praca_acao'],
        $valores['mall_obs'], $valores['mall_acao'],
        $valores['corredores_obs'], $valores['corredores_acao'],
        $valores['externa_obs'], $valores['externa_acao'],
        $valores['seguranca_obs'], $valores['seguranca_acao'],
        $valores['manutencao_obs'], $valores['manutencao_acao'],
        $valores['marketing_obs'], $valores['marketing_acao'],
        $valores['ocorrencia_obs'], $valores['ocorrencia_acao'], $valores['ocorrencia_contato'],
        $valores['estacionamento_obs'], $valores['estacionamento_acao'],
        $_SESSION['usuario_id']
    );
    if ($stmt->execute()) {
        $mensagem = '<div class="alert alert-success">Relatório de plantão salvo com sucesso!</div>';
    } else {
        $mensagem = '<div class="alert alert-danger">Erro ao salvar: ' . $conn->error . '</div>';
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Plantão</title>
    <link rel="stylesheet" href="assets/bootstrap.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        .form-label { font-weight: bold; }
        .setor-title { margin-top:2rem; font-size:1.3rem; }
    </style>
</head>
<body>
<?php include 'includes/menu.php'; ?>
<div class="container mt-4 mb-5">
    <h2 class="mb-3">Relatório de Plantão</h2>
    <?php if ($mensagem) echo $mensagem; ?>
    <form method="post" class="row g-3">
        <div class="col-md-3">
            <label class="form-label">Data de Início</label>
            <input type="datetime-local" class="form-control" name="data_inicio" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Data de Fim</label>
            <input type="datetime-local" class="form-control" name="data_fim" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Responsável pelo Plantão</label>
            <input type="text" class="form-control" name="responsavel" maxlength="80" value="<?= htmlspecialchars($usuario_nome) ?>" readonly>
        </div>
        <div class="col-12 setor-title">LIMPEZA</div>
        <div class="col-md-6"><label class="form-label">BANHEIROS - Observações</label>
            <textarea class="form-control" name="banheiros_obs"></textarea></div>
        <div class="col-md-6"><label class="form-label">BANHEIROS - Ações</label>
            <textarea class="form-control" name="banheiros_acao"></textarea></div>
        <div class="col-md-6"><label class="form-label">CESTOS DE LIXO - Observações</label>
            <textarea class="form-control" name="cestos_obs"></textarea></div>
        <div class="col-md-6"><label class="form-label">CESTOS DE LIXO - Ações</label>
            <textarea class="form-control" name="cestos_acao"></textarea></div>
        <div class="col-md-6"><label class="form-label">PRAÇA DE ALIMENTAÇÃO - Observações</label>
            <textarea class="form-control" name="praca_obs"></textarea></div>
        <div class="col-md-6"><label class="form-label">PRAÇA DE ALIMENTAÇÃO - Ações</label>
            <textarea class="form-control" name="praca_acao"></textarea></div>
        <div class="col-md-6"><label class="form-label">MALL - Observações</label>
            <textarea class="form-control" name="mall_obs"></textarea></div>
        <div class="col-md-6"><label class="form-label">MALL - Ações</label>
            <textarea class="form-control" name="mall_acao"></textarea></div>
        <div class="col-md-6"><label class="form-label">CORREDORES TÉCNICOS - Observações</label>
            <textarea class="form-control" name="corredores_obs"></textarea></div>
        <div class="col-md-6"><label class="form-label">CORREDORES TÉCNICOS - Ações</label>
            <textarea class="form-control" name="corredores_acao"></textarea></div>
        <div class="col-md-6"><label class="form-label">ÁREA EXTERNA, ESTACIONAMENTO, DOCA, PARQUE DAS AVES, PARQUINHO DE MADEIRA - Observações</label>
            <textarea class="form-control" name="externa_obs"></textarea></div>
        <div class="col-md-6"><label class="form-label">ÁREA EXTERNA, ESTACIONAMENTO... - Ações</label>
            <textarea class="form-control" name="externa_acao"></textarea></div>

        <div class="col-12 setor-title">SEGURANÇA</div>
        <div class="col-md-6"><label class="form-label">SEGURANÇA - Observações</label>
            <textarea class="form-control" name="seguranca_obs"></textarea></div>
        <div class="col-md-6"><label class="form-label">SEGURANÇA - Ações</label>
            <textarea class="form-control" name="seguranca_acao"></textarea></div>

        <div class="col-12 setor-title">MANUTENÇÃO</div>
        <div class="col-md-6"><label class="form-label">MANUTENÇÃO - Observações</label>
            <textarea class="form-control" name="manutencao_obs"></textarea></div>
        <div class="col-md-6"><label class="form-label">MANUTENÇÃO - Ações</label>
            <textarea class="form-control" name="manutencao_acao"></textarea></div>

        <div class="col-12 setor-title">MARKETING (EVENTOS, SOM DO MALL, ÔNIBUS, QUALQUER ATENDIMENTO AO CLIENTE – INCLUSIVE RECLAMAÇÃO RELAÇÃO ÀS LOJAS, ESPAÇO CLIENTE, MÚSICA, ETC)</div>
        <div class="col-md-6"><label class="form-label">MARKETING - Observações</label>
            <textarea class="form-control" name="marketing_obs"></textarea></div>
        <div class="col-md-6"><label class="form-label">MARKETING - Ações</label>
            <textarea class="form-control" name="marketing_acao"></textarea></div>

        <div class="col-12 setor-title">OCORRÊNCIA COM CLIENTES (NECESSIDADE DE ATENDIMENTO DOS BOMBEIROS)</div>
        <div class="col-md-4"><label class="form-label">OCORRÊNCIA - Observações</label>
            <textarea class="form-control" name="ocorrencia_obs"></textarea></div>
        <div class="col-md-4"><label class="form-label">OCORRÊNCIA - Ações</label>
            <textarea class="form-control" name="ocorrencia_acao"></textarea></div>
        <div class="col-md-4"><label class="form-label">OCORRÊNCIA - Contato do Cliente</label>
            <input type="text" class="form-control" name="ocorrencia_contato"></div>

        <div class="col-12 setor-title">ESTACIONAMENTO</div>
        <div class="col-md-6"><label class="form-label">ESTACIONAMENTO - Observações</label>
            <textarea class="form-control" name="estacionamento_obs"></textarea></div>
        <div class="col-md-6"><label class="form-label">ESTACIONAMENTO - Ações</label>
            <textarea class="form-control" name="estacionamento_acao"></textarea></div>

        <div class="col-12 text-end"><button class="btn btn-success mt-4">Salvar Relatório</button></div>
    </form>
</div>
<?php include 'includes/footer.php'; ?>
</body>
</html>