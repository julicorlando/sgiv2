<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
include 'includes/db.php';

$sql = "SELECT * FROM plantao ORDER BY data_inicio DESC";
$result = $conn->query($sql);

function exibe($campo) {
    return !empty($campo) ? nl2br(htmlspecialchars($campo)) : '<span style="color:#999">-</span>';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Relatórios de Plantão</title>
    <link rel="stylesheet" href="assets/bootstrap.min.css">
    <style>
        .section-title { margin-top:2.5rem; font-size:1.2rem; font-weight:bold; }
        .pre { white-space: pre-line; }
        .logo-plantao { position: absolute; right: 40px; top: 30px; max-height: 90px; }
        @media print {
            .noprint { display: none !important; }
            .logo-plantao { position: absolute; right: 40px; top: 30px; max-height: 90px; }
            body { background: #fff !important; }
            .card, .card-body, .card-header, .card-footer { box-shadow: none !important; border: none !important; }
        }
    </style>
</head>
<body>
<div class="noprint">
    <?php include 'includes/menu.php'; ?>
</div>
<div class="container mt-4 mb-5">
    <h2 class="noprint">Relatórios de Plantão</h2>
    <?php if($result && $result->num_rows): ?>
        <table class="table table-bordered table-striped noprint">
            <thead>
                <tr>
                    <th>Início</th>
                    <th>Fim</th>
                    <th>Responsável</th>
                    <th>Visualizar</th>
                </tr>
            </thead>
            <tbody>
            <?php while($r = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($r['data_inicio'])) ?></td>
                    <td><?= date('d/m/Y', strtotime($r['data_fim'])) ?></td>
                    <td><?= htmlspecialchars($r['responsavel']) ?></td>
                    <td>
                        <a href="?id=<?= $r['id'] ?>" class="btn btn-sm btn-info">Detalhes</a>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert alert-info noprint">Nenhum relatório encontrado.</div>
    <?php endif; ?>

<?php
// Exibe detalhes se for passado ?id=ID na URL
if (isset($_GET['id'])):
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM plantao WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $detalhe = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if($detalhe):
?>
    <div class="card mt-5 mb-5" id="print-section" style="position:relative;">
        <!-- Troque o src abaixo pelo caminho da sua logomarca -->
        <img src="assets/logo.png" class="logo-plantao" alt="Logo">
        <div class="card-header text-center"><b style="font-size:1.4em;">Relatório de Plantão</b></div>
        <div class="card-body">
            <div class="row mb-2">
                <div class="col-md-6"><b>Início:</b> <?= date('d/m/Y', strtotime($detalhe['data_inicio'])) ?></div>
                <div class="col-md-6"><b>Fim:</b> <?= date('d/m/Y', strtotime($detalhe['data_fim'])) ?></div>
                <div class="col-md-12"><b>Responsável:</b> <?= htmlspecialchars($detalhe['responsavel']) ?></div>
            </div>
            <hr>
            <div class="section-title">LIMPEZA</div>
            <b>Banheiros:</b><br>
            <span class="pre">Observações: <?= exibe($detalhe['banheiros_obs']) ?></span><br>
            <span class="pre">Ações: <?= exibe($detalhe['banheiros_acao']) ?></span>
            <br><b>Cestos de Lixo:</b><br>
            <span class="pre">Observações: <?= exibe($detalhe['cestos_obs']) ?></span><br>
            <span class="pre">Ações: <?= exibe($detalhe['cestos_acao']) ?></span>
            <br><b>Praça de Alimentação:</b><br>
            <span class="pre">Observações: <?= exibe($detalhe['praca_obs']) ?></span><br>
            <span class="pre">Ações: <?= exibe($detalhe['praca_acao']) ?></span>
            <br><b>Mall:</b><br>
            <span class="pre">Observações: <?= exibe($detalhe['mall_obs']) ?></span><br>
            <span class="pre">Ações: <?= exibe($detalhe['mall_acao']) ?></span>
            <br><b>Corredores Técnicos:</b><br>
            <span class="pre">Observações: <?= exibe($detalhe['corredores_obs']) ?></span><br>
            <span class="pre">Ações: <?= exibe($detalhe['corredores_acao']) ?></span>
            <br><b>Área Externa, Estacionamento, Doca, Parque das Aves, Parquinho de Madeira:</b><br>
            <span class="pre">Observações: <?= exibe($detalhe['externa_obs']) ?></span><br>
            <span class="pre">Ações: <?= exibe($detalhe['externa_acao']) ?></span>

            <div class="section-title">SEGURANÇA</div>
            <span class="pre">Observações: <?= exibe($detalhe['seguranca_obs']) ?></span><br>
            <span class="pre">Ações: <?= exibe($detalhe['seguranca_acao']) ?></span>

            <div class="section-title">MANUTENÇÃO</div>
            <span class="pre">Observações: <?= exibe($detalhe['manutencao_obs']) ?></span><br>
            <span class="pre">Ações: <?= exibe($detalhe['manutencao_acao']) ?></span>

            <div class="section-title">MARKETING</div>
            <span class="pre">Observações: <?= exibe($detalhe['marketing_obs']) ?></span><br>
            <span class="pre">Ações: <?= exibe($detalhe['marketing_acao']) ?></span>

            <div class="section-title">OCORRÊNCIA COM CLIENTES</div>
            <span class="pre">Observações: <?= exibe($detalhe['ocorrencia_obs']) ?></span><br>
            <span class="pre">Ações: <?= exibe($detalhe['ocorrencia_acao']) ?></span><br>
            <span class="pre">Contato do Cliente: <?= exibe($detalhe['ocorrencia_contato']) ?></span>

            <div class="section-title">ESTACIONAMENTO</div>
            <span class="pre">Observações: <?= exibe($detalhe['estacionamento_obs']) ?></span><br>
            <span class="pre">Ações: <?= exibe($detalhe['estacionamento_acao']) ?></span>
        </div>
        <div class="card-footer noprint text-end">
            <button class="btn btn-primary" onclick="window.print();return false;">Imprimir</button>
            <a href="plantao_visualizar.php" class="btn btn-secondary">Voltar</a>
        </div>
    </div>
<?php
    endif;
endif;
?>
</div>
<?php include 'includes/footer.php'; ?>
</body>
</html>