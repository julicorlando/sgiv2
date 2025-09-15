<?php
$conn = new mysqli("localhost", "root", "", "sistema_inspecao");

$conn->query("
    UPDATE salas_agendamentos
    SET status = 'historico'
    WHERE status = 'ativo'
      AND CONCAT(data, ' ', hora_fim) < NOW()
");

$result = $conn->query("SELECT a.*, s.nome as sala_nome, s.local FROM salas_agendamentos a JOIN salas s ON a.sala_id = s.id WHERE a.status = 'historico' ORDER BY a.data DESC, a.hora_inicio DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Histórico de Agendamentos</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6fb;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 90%;
            max-width: 1000px;
            margin: 40px auto;
            padding: 24px 32px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px #0001;
        }
        h1 {
            text-align: center;
            color: #2b3a67;
            margin-bottom: 24px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }
        table th, table td {
            border: 1px solid #bfc9d8;
            padding: 10px 8px;
            text-align: center;
        }
        table th {
            background: #2b3a67;
            color: #fff;
            font-weight: bold;
        }
        table tr:nth-child(even) {
            background: #f8f9fc;
        }
        table tr:hover {
            background: #e7eefd;
        }
        a {
            text-decoration: none;
            color: #2b3a67;
            font-weight: bold;
            margin-right: 20px;
        }
        a:hover {
            color: #1a2647;
        }
    </style>
</head>
<body>
<?php include 'includes/menu.php'; ?>
    <div class="container">
        <h1>Histórico de Agendamentos</h1>
        <table>
            <tr>
                <th>Sala</th>
                <th>Local</th>
                <th>Data</th>
                <th>Hora Início</th>
                <th>Hora Fim</th>
                <th>Responsável</th>
                <th>Finalidade</th>
            </tr>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['sala_nome']) ?></td>
                <td><?= htmlspecialchars($row['local']) ?></td>
                <td><?= htmlspecialchars($row['data']) ?></td>
                <td><?= htmlspecialchars($row['hora_inicio']) ?></td>
                <td><?= htmlspecialchars($row['hora_fim']) ?></td>
                <td><?= htmlspecialchars($row['responsavel']) ?></td>
                <td><?= htmlspecialchars($row['finalidade']) ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
        <a href="listar_agendamentos.php">Ver Agendamentos Ativos</a>
    </div>
</body>
</html>