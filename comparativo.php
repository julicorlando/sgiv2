<?php
$conn = new mysqli("localhost", "root", "", "fluxo_pessoas");
if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

$labels = [];
$valores1 = [];
$valores2 = [];

$mes1 = $_GET['mes1'] ?? '';
$ano1 = $_GET['ano1'] ?? '';
$mes2 = $_GET['mes2'] ?? '';
$ano2 = $_GET['ano2'] ?? '';

function getTotaisPorDia($conn, $mes, $ano) {
    $totais = [];
    $labels = [];

    $diasNoMes = cal_days_in_month(CAL_GREGORIAN, $mes, $ano);
    for ($dia = 1; $dia <= $diasNoMes; $dia++) {
        $data = sprintf('%04d-%02d-%02d', $ano, $mes, $dia);
        $sql = "SELECT SUM(quantidade) AS total FROM registros_fluxo WHERE DATE(data_registro) = '$data'";
        $res = $conn->query($sql);
        $total = $res->fetch_assoc()['total'] ?? 0;

        $labels[] = sprintf('%02d/%02d', $dia, $mes);
        $totais[] = $total;
    }

    return [$labels, $totais];
}

if ($mes1 && $ano1 && $mes2 && $ano2) {
    list($labels, $valores1) = getTotaisPorDia($conn, $mes1, $ano1);
    list($_, $valores2) = getTotaisPorDia($conn, $mes2, $ano2); // mesmos labels
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Comparação por Mês e Ano</title>
    <link rel="stylesheet" href="assets/bootstrap.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --cor-primaria: #00bfff;
            --cor-primaria-hover: #009acd;
            --cor-texto: #333;
            --fundo-form: #fff;
        }
        body {
            font-family: Arial, sans-serif;
            color: var(--cor-texto);
            background-color: #fff;
            min-height: 100vh;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
        }
        .comparativo-container {
            background: var(--fundo-form);
            max-width: 700px;
            width: 100%;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 0 10px rgba(0,0,0,0.08);
            margin: 40px auto;
        }
        h2 {
            margin-bottom: 20px;
            color: var(--cor-primaria);
            text-align: center;
        }
        form {
            margin-bottom: 24px;
            text-align: center;
        }
        select, button {
            padding: 8px;
            margin: 5px 2px;
            border-radius: 8px;
            border: 1px solid #ccc;
        }
        button {
            background: var(--cor-primaria);
            color: #fff;
            border: none;
            font-weight: bold;
            transition: background 0.2s;
        }
        button:hover {
            background: var(--cor-primaria-hover);
        }
        canvas {
            background: #fff;
            border: 1px solid #ccc;
            border-radius: 10px;
            margin-top: 24px;
        }
        .footer-fix {
            width: 100%;
            margin-top: auto;
            background: #f8f9fa;
            color: #888;
            padding: 12px 0;
            text-align: center;
            font-size: 0.95rem;
            border-top: 1px solid #eee;
        }
        @media (max-width: 900px) {
            .comparativo-container { max-width: 95vw; }
        }
        @media (max-width: 500px) {
            .comparativo-container { padding: 12px; }
            h2 { font-size: 1.2rem; }
            select, button { font-size: 15px; }
        }
    </style>
</head>
<body>
<?php include 'includes/menu.php'; ?>
<div class="container d-flex justify-content-center align-items-center flex-grow-1" style="min-height: 80vh;">
    <div class="comparativo-container">
        <h2>Comparar Fluxo por Mês e Ano</h2>
        <form method="GET" class="row g-2 justify-content-center">
            <div class="col-auto">
                <label class="form-label mb-0">Mês 1:</label>
                <select name="mes1" class="form-select d-inline-block w-auto">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= ($m == $mes1) ? 'selected' : '' ?>><?= sprintf('%02d', $m) ?></option>
                    <?php endfor; ?>
                </select>
                <select name="ano1" class="form-select d-inline-block w-auto">
                    <?php for ($a = 2023; $a <= date('Y'); $a++): ?>
                        <option value="<?= $a ?>" <?= ($a == $ano1) ? 'selected' : '' ?>><?= $a ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label mb-0">Mês 2:</label>
                <select name="mes2" class="form-select d-inline-block w-auto">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= ($m == $mes2) ? 'selected' : '' ?>><?= sprintf('%02d', $m) ?></option>
                    <?php endfor; ?>
                </select>
                <select name="ano2" class="form-select d-inline-block w-auto">
                    <?php for ($a = 2023; $a <= date('Y'); $a++): ?>
                        <option value="<?= $a ?>" <?= ($a == $ano2) ? 'selected' : '' ?>><?= $a ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-auto align-self-end">
                <button type="submit" class="btn">Comparar</button>
            </div>
        </form>

        <canvas id="grafico"></canvas>
    </div>
</div>
<div class="footer-fix">
    <?php include 'includes/footer.php'; ?>
</div>
<script>
    const ctx = document.getElementById('grafico').getContext('2d');
    const chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($labels) ?>,
            datasets: [
                {
                    label: '<?= "$mes1/$ano1" ?>',
                    data: <?= json_encode($valores1) ?>,
                    backgroundColor: 'rgba(0, 123, 255, 0.6)'
                },
                {
                    label: '<?= "$mes2/$ano2" ?>',
                    data: <?= json_encode($valores2) ?>,
                    backgroundColor: 'rgba(255, 99, 132, 0.6)'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
</script>
</body>
</html>