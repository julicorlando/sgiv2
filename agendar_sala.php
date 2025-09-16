<?php
$conn = new mysqli("localhost", "root", "", "sistema_inspecao");

// Carregar salas cadastradas
$salas = $conn->query("SELECT * FROM salas ORDER BY nome");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sala_id = $_POST['sala_id'];
    $data = $_POST['data'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fim = $_POST['hora_fim'];
    $responsavel = $_POST['responsavel'];
    $finalidade = $_POST['finalidade'];

    // Checar conflito
    $stmt = $conn->prepare("SELECT * FROM salas_agendamentos WHERE sala_id=? AND data=? AND status='ativo' AND (hora_inicio < ? AND hora_fim > ?)");
    $stmt->bind_param("isss", $sala_id, $data, $hora_fim, $hora_inicio);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $erro = "Já existe um agendamento para essa sala neste horário!";
    } else {
        $stmt = $conn->prepare("INSERT INTO salas_agendamentos (sala_id, data, hora_inicio, hora_fim, responsavel, finalidade, status) VALUES (?, ?, ?, ?, ?, ?, 'ativo')");
        $stmt->bind_param("isssss", $sala_id, $data, $hora_inicio, $hora_fim, $responsavel, $finalidade);
        $stmt->execute();
        $sucesso = "Agendamento realizado com sucesso!";
        // Aqui você pode enviar e-mail ou alerta interno se desejar
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Agendar Sala</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6fb;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 420px;
            margin: 40px auto;
            padding: 28px 36px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px #0001;
        }
        h1 {
            text-align: center;
            color: #2b3a67;
            margin-bottom: 24px;
        }
        form label {
            display: block;
            margin: 12px 0 6px 0;
            color: #444;
            font-weight: bold;
        }
        form input[type="text"], 
        form input[type="date"],
        form input[type="time"],
        form select {
            width: 100%;
            padding: 8px 10px;
            border-radius: 5px;
            border: 1px solid #bfc9d8;
            margin-bottom: 12px;
            font-size: 15px;
            background: #f8f9fc;
        }
        button[type="submit"] {
            width: 100%;
            background: #2b3a67;
            color: #fff;
            padding: 10px 0;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            margin-top: 12px;
            cursor: pointer;
            transition: background 0.2s;
        }
        button[type="submit"]:hover {
            background: #1a2647;
        }
        .success-message {
            background: #d1e7dd;
            color: #20543a;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            margin-bottom: 16px;
            border: 1px solid #b7dfca;
        }
        .error-message {
            background: #f8d7da;
            color: #842029;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            margin-bottom: 16px;
            border: 1px solid #f5c2c7;
        }
        a {
            text-decoration: none;
            color: #2b3a67;
            font-weight: bold;
        }
        a:hover {
            color: #1a2647;
        }
    </style>
</head>
<body>
<?php include 'includes/menu.php'; ?>
    <div class="container">
        <h1>Agendar Sala de Reunião</h1>
        <?php if(isset($erro)) echo "<div class='error-message'>$erro</div>"; ?>
        <?php if(isset($sucesso)) echo "<div class='success-message'>$sucesso</div>"; ?>
        <form method="post">
            <label>Sala:</label>
            <select name="sala_id" required>
                <option value="">Selecione</option>
                <?php while($sala = $salas->fetch_assoc()): ?>
                    <option value="<?= $sala['id'] ?>"><?= htmlspecialchars($sala['nome']) ?> (<?= htmlspecialchars($sala['local']) ?>)</option>
                <?php endwhile; ?>
            </select>
            <label>Data:</label>
            <input type="date" name="data" required>
            <label>Hora Início:</label>
            <input type="time" name="hora_inicio" required>
            <label>Hora Fim:</label>
            <input type="time" name="hora_fim" required>
            <label>Responsável:</label>
            <input type="text" name="responsavel" required>
            <label>Finalidade:</label>
            <input type="text" name="finalidade" required>
            <button type="submit">Agendar</button>
        </form>
        <br>
        <a href="listar_agendamentos.php">Ver Agendamentos</a>
    </div>
</body>
</html>