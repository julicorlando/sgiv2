<?php
$conn = new mysqli("localhost", "root", "", "fluxo_pessoas");
if ($conn->connect_error) die("Erro: " . $conn->connect_error);

$mes1 = $_GET['mes1'] ?? '';
$ano1 = $_GET['ano1'] ?? '';
$mes2 = $_GET['mes2'] ?? '';
$ano2 = $_GET['ano2'] ?? '';

$labels = $valores1 = $valores2 = [];

function obterTotaisMensais($conn, $mes, $ano): array {
    $dias = cal_days_in_month(CAL_GREGORIAN, $mes, $ano);
    $labels = $dados = [];

    for ($dia = 1; $dia <= $dias; $dia++) {
        $data = sprintf('%04d-%02d-%02d', $ano, $mes, $dia);
        $sql = "SELECT SUM(quantidade) as total FROM registros_fluxo WHERE DATE(data_registro) = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $data);
        $stmt->execute();
        $res = $stmt->get_result();
        $total = $res->fetch_assoc()['total'] ?? 0;

        $labels[] = sprintf('%02d/%02d', $dia, $mes);
        $dados[] = $total;

        $stmt->close();
    }

    return [$labels, $dados];
}

if ($mes1 && $ano1 && $mes2 && $ano2) {
    [$labels, $valores1] = obterTotaisMensais($conn, $mes1, $ano1);
    [$_, $valores2] = obterTotaisMensais($conn, $mes2, $ano2); // mesma estrutura
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Comparar Fluxo por Mês/Ano</title>
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
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #fff;
            color: var(--cor-texto);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .comparativo-container {
            max-width: 960px;
            margin: 40px auto 0 auto;
            background: var(--fundo-form);
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        h2 {
            text-align: center;
            color: var(--cor-primaria);
            margin-bottom: 30px;
        }
        form {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 15px;
            margin-bottom: 30px;
        }
        label {
            display: flex;
            flex-direction: column;
            font-weight: 600;
            color: #555;
        }
        select, button {
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #ccc;
            min-width: 100px;
        }
        button {
            background: var(--cor-primaria);
            color: white;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.3s;
        }
        button:hover {
            background: var(--cor-primaria-hover);
        }
        canvas {
            margin-top: 20px;
            background: #fff;
            border-radius: 10px;
            width: 100% !important;
            max-height: 350px;
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
        @media(max-width: 700px) {
            .comparativo-container { max-width: 98vw; padding: 10px; }
            form { flex-direction: column; align-items: center; }
        }
        @media(max-width: 500px) {
            h2 { font-size: 1.2rem; }
            select, button { font-size: 15px; }
        }
    </style>
</head>
<body>
<?php include 'includes/menu.php'; ?>
<div class="container d-flex justify-content-center align-items-center flex-grow-1" style="min-height: 80vh;">
    <div class="comparativo-container">
        <h2>Comparar Fluxo de Pessoas</h2>

        <form method="GET">
            <label>Mês 1:
                <select name="mes1" required>
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= $m == $mes1 ? 'selected' : '' ?>><?= sprintf('%02d', $m) ?></option>
                    <?php endfor; ?>
                </select>
                <select name="ano1" required>
                    <?php for ($a = 2023; $a <= date('Y'); $a++): ?>
                        <option value="<?= $a ?>" <?= $a == $ano1 ? 'selected' : '' ?>><?= $a ?></option>
                    <?php endfor; ?>
                </select>
            </label>

            <label>Mês 2:
                <select name="mes2" required>
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= $m == $mes2 ? 'selected' : '' ?>><?= sprintf('%02d', $m) ?></option>
                    <?php endfor; ?>
                </select>
                <select name="ano2" required>
                    <?php for ($a = 2023; $a <= date('Y'); $a++): ?>
                        <option value="<?= $a ?>" <?= $a == $ano2 ? 'selected' : '' ?>><?= $a ?></option>
                    <?php endfor; ?>
                </select>
            </label>

            <button type="submit">Comparar</button>
        </form>

        <canvas id="grafico"></canvas>
    </div>
</div>
<div class="footer-fix">
    <?php include 'includes/footer.php'; ?>
</div>
<script>
const ctx = document.getElementById('grafico').getContext('2d');

new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode($labels) ?>,
        datasets: [
            {
                label: '<?= "$mes1/$ano1" ?>',
                data: <?= json_encode($valores1) ?>,
                borderColor: '#00bfff',
                backgroundColor: 'rgba(0, 191, 255, 0.2)',
                fill: true,
                tension: 0.3
            },
            {
                label: '<?= "$mes2/$ano2" ?>',
                data: <?= json_encode($valores2) ?>,
                borderColor: '#dc3545',
                backgroundColor: 'rgba(220, 53, 69, 0.2)',
                fill: true,
                tension: 0.3
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            title: {
                display: true,
                text: 'Comparação de Fluxo Diário',
                font: { size: 18 }
            },
            tooltip: {
                mode: 'index',
                intersect: false
            },
            legend: {
                position: 'top'
            }
        },
        interaction: {
            mode: 'nearest',
            axis: 'x',
            intersect: false
        },
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Quantidade de Pessoas'
                }
            },
            x: {
                title: {
                    display: true,
                    text: 'Dia do Mês'
                }
            }
        }
    }
});
</script>
</body>
</html>