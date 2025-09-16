<?php
$conn = new mysqli("localhost", "root", "", "sistema_inspecao");
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $local = $_POST['local'];
    $capacidade = $_POST['capacidade'];
    $recursos = isset($_POST['recursos']) ? implode(', ', $_POST['recursos']) : '';
    $stmt = $conn->prepare("INSERT INTO salas (nome, local, capacidade, recursos) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssis", $nome, $local, $capacidade, $recursos);
    $stmt->execute();
    $msg = "Sala cadastrada com sucesso!";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Cadastrar Sala</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6fb;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 380px;
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
        form label {
            display: block;
            margin: 12px 0 6px 0;
            color: #444;
            font-weight: bold;
        }
        form input[type="text"], 
        form input[type="number"] {
            width: 100%;
            padding: 8px 10px;
            border-radius: 5px;
            border: 1px solid #bfc9d8;
            margin-bottom: 12px;
            font-size: 15px;
            background: #f8f9fc;
        }
        .recursos-group label {
            display: inline-block;
            margin-right: 16px;
            font-weight: normal;
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
    </style>
</head>
<body>
<?php include 'includes/menu.php'; ?>
    <div class="container">
        <h1>Cadastrar Sala</h1>
        <?php if(isset($msg)) echo "<div class='success-message'>$msg</div>"; ?>
        <form method="post">
            <label>Nome:</label>
            <input type="text" name="nome" required>
            <label>Local:</label>
            <input type="text" name="local">
            <label>Capacidade:</label>
            <input type="number" name="capacidade" min="1">
            <label>Recursos:</label>
            <div class="recursos-group">
                <label><input type="checkbox" name="recursos[]" value="Projetor"> Projetor</label>
                <label><input type="checkbox" name="recursos[]" value="Ar-condicionado"> Ar-condicionado</label>
                <label><input type="checkbox" name="recursos[]" value="TV"> TV</label>
            </div>
            <button type="submit">Cadastrar</button>
        </form>
    </div>
</body>
</html>