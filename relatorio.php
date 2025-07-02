<?php
// Sempre comece sem nenhuma saída antes do PHP!

// Inclua a sessão ANTES de qualquer saída
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Conexão
$conn = new mysqli("localhost", "root", "", "fluxo_pessoas");
if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

$labels = [];
$valores = [];

$data_inicio = '';
$data_fim = '';

if (isset($_GET['inicio']) && isset($_GET['fim'])) {
    // Converte de dd/mm/yyyy para yyyy-mm-dd
    $inicio = DateTime::createFromFormat('d/m/Y', $_GET['inicio']);
    $fim = DateTime::createFromFormat('d/m/Y', $_GET['fim']);

    if ($inicio && $fim) {
        $data_inicio = $_GET['inicio'];
        $data_fim = $_GET['fim'];

        $periodo = new DatePeriod(
            $inicio,
            new DateInterval('P1D'),
            $fim->modify('+1 day')
        );

        foreach ($periodo as $data) {
            $dataStr = $data->format('Y-m-d');
            $sql = "SELECT SUM(quantidade) AS total FROM registros_fluxo WHERE DATE(data_registro) = '$dataStr'";
            $res = $conn->query($sql);
            $total = $res->fetch_assoc()['total'] ?? 0;

            $labels[] = $data->format('d/m/Y');
            $valores[] = $total;
        }
    }
} else {
    // Últimos 7 dias padrão
    for ($i = 6; $i >= 0; $i--) {
        $data = date('Y-m-d', strtotime("-$i days"));
        $data_brasil = date('d/m/Y', strtotime($data));
        $sql = "SELECT SUM(quantidade) AS total FROM registros_fluxo WHERE DATE(data_registro) = '$data'";
        $res = $conn->query($sql);
        $total = $res->fetch_assoc()['total'] ?? 0;

        $labels[] = $data_brasil;
        $valores[] = $total;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório com Gráfico</title>
    <link rel="stylesheet" href="assets/bootstrap.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        :root {
            --cor-primaria: #00bfff;
            --cor-secundaria: #00ffff;
            --cor-primaria-hover: #009acd;
            --cor-texto: #333;
            --fundo-form: #fff;
        }
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            color: var(--cor-texto);
            background-color: #fff;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .content {
            background-color: var(--fundo-form);
            padding: 30px;
            max-width: 900px;
            margin: 50px auto 0 auto;
            border-radius: 15px;
            color: var(--cor-texto);
            box-shadow: 0 0 10px rgba(0,0,0,0.08);
        }
        h2 {
            color: var(--cor-primaria);
            text-align: center;
            margin-bottom: 30px;
        }
        form {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 10px;
            margin-bottom: 25px;
        }
        label {
            display: flex;
            flex-direction: column;
            font-weight: bold;
        }
        input[type="text"] {
            padding: 8px;
            border-radius: 8px;
            border: 1px solid #ccc;
            width: 150px;
            color: var(--cor-texto);
            background: #fafafa;
        }
        button {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            background-color: var(--cor-primaria);
            color: white;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: var(--cor-primaria-hover);
        }
        .btn-link {
            background: none;
            color: var(--cor-primaria);
            border: none;
            padding: 0;
            margin-top: auto;
            margin-left: 12px;
            font-weight: bold;
            text-decoration: underline;
            cursor: pointer;
        }
        .btn-link:hover {
            color: var(--cor-primaria-hover);
            text-decoration: underline;
        }
        canvas {
            background-color: white;
            border-radius: 10px;
            padding: 10px;
            margin-bottom: 20px;
            width: 100% !important;
            max-height: 350px;
        }
        .exportar {
            text-align: center;
            margin-top: 20px;
        }
        table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
            background-color: white;
            color: var(--cor-texto);
            border-radius: 10px;
            overflow: hidden;
        }
        table th, table td {
            padding: 10px;
            text-align: center;
            border-bottom: 1px solid #ccc;
        }
        table th {
            background-color: var(--cor-primaria);
            color: white;
        }
        .total {
            font-weight: bold;
            text-align: right;
            margin-top: 10px;
            color: var(--cor-primaria);
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
            .content { max-width: 98vw; padding: 10px; }
            table th, table td { font-size: 12px; padding: 6px; }
        }
    </style>
</head>
<body>
<?php include 'includes/menu.php'; ?>
<div class="content" id="area-pdf">
    <h2>Relatório de Fluxo de Pessoas</h2>

    <form method="GET">
        <label>Data início:
            <input type="text" name="inicio" placeholder="DD/MM/AAAA" value="<?= htmlspecialchars($data_inicio) ?>" required>
        </label>
        <label>Data fim:
            <input type="text" name="fim" placeholder="DD/MM/AAAA" value="<?= htmlspecialchars($data_fim) ?>" required>
        </label>
        <button type="submit">Filtrar</button>
        <a href="relatorio.php" class="btn-link">Limpar</a>
    </form>

    <canvas id="grafico"></canvas>

    <?php if (count($labels) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Quantidade</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $totalGeral = 0;
                for ($i = 0; $i < count($labels); $i++): 
                    $totalGeral += $valores[$i];
                ?>
                    <tr>
                        <td><?= $labels[$i] ?></td>
                        <td><?= $valores[$i] ?></td>
                    </tr>
                <?php endfor; ?>
            </tbody>
        </table>
        <div class="total">Total no período: <?= $totalGeral ?> pessoas</div>
    <?php else: ?>
        <p style="text-align:center; color:#ff3333; font-weight:bold;">Nenhum registro encontrado para o período selecionado.</p>
    <?php endif; ?>

    <div class="exportar">
        <button onclick="gerarPDF()">Exportar PDF</button>
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
            datasets: [{
                label: 'Quantidade de Pessoas',
                data: <?= json_encode($valores) ?>,
                backgroundColor: 'rgba(0, 191, 255, 0.6)',
                borderColor: 'rgba(0, 191, 255, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

    function gerarPDF() {
        const elemento = document.getElementById('area-pdf');
        const opcoes = {
            margin: 10,
            filename: 'relatorio_fluxo.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2 },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
        };
        html2pdf().set(opcoes).from(elemento).save();
    }
</script>
</body>
</html>