<?php
$conn = new mysqli("localhost", "root", "", "fluxo_pessoas");
if ($conn->connect_error) die("Erro: " . $conn->connect_error);

$data1 = $_GET['data1'] ?? '';
$data2 = $_GET['data2'] ?? '';

$labels = [];
$valores = [];

function getTotalPorDia($conn, $data) {
    $sql = "SELECT SUM(quantidade) as total FROM registros_fluxo WHERE DATE(data_registro) = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $data);
    $stmt->execute();
    $res = $stmt->get_result();
    $total = $res->fetch_assoc()['total'] ?? 0;
    $stmt->close();
    return (int)$total;
}

if ($data1 && $data2) {
    $dataFormatada1 = DateTime::createFromFormat('d/m/Y', $data1)?->format('Y-m-d');
    $dataFormatada2 = DateTime::createFromFormat('d/m/Y', $data2)?->format('Y-m-d');

    if ($dataFormatada1 && $dataFormatada2) {
        $labels = [$data1, $data2];
        $valores = [
            getTotalPorDia($conn, $dataFormatada1),
            getTotalPorDia($conn, $dataFormatada2)
        ];
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Comparar Dias</title>
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
        .dia-container {
            background: var(--fundo-form);
            max-width: 600px;
            width: 100%;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 0 10px rgba(0,0,0,0.08);
            margin: 40px auto;
        }
        h2 {
            color: var(--cor-primaria);
            text-align: center;
            margin-bottom: 20px;
        }
        form {
            margin-bottom: 24px;
            text-align: center;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
        }
        input[type="text"], button {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 8px;
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
            border-radius: 10px;
            border: 1px solid #ccc;
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
        @media (max-width: 700px) {
            .dia-container { max-width: 95vw; padding: 15px; }
        }
        @media (max-width: 500px) {
            h2 { font-size: 1.2rem; }
            input[type="text"], button { font-size: 15px; }
        }
    </style>
</head>
<body>
<?php include 'includes/menu.php'; ?>
<div class="container d-flex justify-content-center align-items-center flex-grow-1" style="min-height: 80vh;">
    <div class="dia-container">
        <h2>Comparar Total de Pessoas por Dia</h2>
        <form method="GET">
            <label>
                Dia 1: <input type="text" name="data1" placeholder="DD/MM/AAAA" value="<?= htmlspecialchars($data1) ?>" required>
            </label>
            <label>
                Dia 2: <input type="text" name="data2" placeholder="DD/MM/AAAA" value="<?= htmlspecialchars($data2) ?>" required>
            </label>
            <button type="submit">Comparar</button>
        </form>
        <canvas id="grafico" height="100"></canvas>
    </div>
</div>
<div class="footer-fix">
    <?php include 'includes/footer.php'; ?>
</div>
<script>
const ctx = document.getElementById('grafico').getContext('2d');

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($labels) ?>,
        datasets: [{
            label: 'Total de Pessoas',
            data: <?= json_encode($valores) ?>,
            backgroundColor: ['rgba(0, 123, 255, 0.6)', 'rgba(255, 99, 132, 0.6)']
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: { beginAtZero: true }
        },
        plugins: {
            title: {
                display: true,
                text: 'Comparação de Fluxo por Dia'
            }
        }
    }
});
</script>
</body>
</html>