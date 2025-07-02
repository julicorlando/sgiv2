<?php
$mensagem = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $data = $_POST['data'] ?? '';
    $quantidade = $_POST['quantidade'] ?? '';

    if ($data && $quantidade !== '') {
        $dataFormatada = DateTime::createFromFormat('d/m/Y', $data);
        if ($dataFormatada) {
            $dataMySQL = $dataFormatada->format('Y-m-d');

            $conn = new mysqli("localhost", "root", "", "fluxo_pessoas");
            if ($conn->connect_error) {
                die("Erro na conexão: " . $conn->connect_error);
            }

            // Verifica se já existe registro para a data
            $check = $conn->prepare("SELECT quantidade FROM registros_fluxo WHERE data_registro = ?");
            $check->bind_param("s", $dataMySQL);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                $mensagem = "Já existe um registro para esta data.";
            } else {
                $stmt = $conn->prepare("INSERT INTO registros_fluxo (data_registro, quantidade) VALUES (?, ?)");
                $stmt->bind_param("si", $dataMySQL, $quantidade);

                if ($stmt->execute()) {
                    $mensagem = "Registro adicionado com sucesso!";
                } else {
                    $mensagem = "Erro ao inserir: " . $stmt->error;
                }

                $stmt->close();
            }

            $check->close();
            $conn->close();
        } else {
            $mensagem = "Data inválida. Use o formato DD/MM/AAAA.";
        }
    } else {
        $mensagem = "Preencha todos os campos.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Adicionar Fluxo de Pessoas</title>
    <link rel="stylesheet" href="assets/bootstrap.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
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
        .container-fluxo {
            background: var(--fundo-form);
            max-width: 400px;
            width: 100%;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 0 10px rgba(0,0,0,0.08);
            text-align: center;
            margin: 40px auto;
        }
        h2 {
            color: var(--cor-primaria);
            margin-bottom: 20px;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        input[type="text"], input[type="number"] {
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #ccc;
        }
        button {
            background-color: var(--cor-primaria);
            color: white;
            padding: 12px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: var(--cor-primaria-hover);
        }
        .mensagem {
            text-align: center;
            color: green;
            margin-top: 10px;
            font-weight: bold;
        }
        .erro {
            color: red;
        }
        a {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: var(--cor-primaria);
            text-decoration: none;
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
        @media (max-width: 500px) {
            .container-fluxo {
                padding: 20px;
            }
            h2 { font-size: 1.2rem; }
        }
    </style>
</head>
<body>
<?php include 'includes/menu.php'; ?>
<div class="container d-flex justify-content-center align-items-center flex-grow-1" style="min-height: 80vh;">
    <div class="container-fluxo">
        <h2>Adicionar Fluxo de Pessoas</h2>

        <?php if ($mensagem): ?>
            <div class="mensagem <?= str_contains($mensagem, 'sucesso') ? '' : 'erro' ?>">
                <?= htmlspecialchars($mensagem) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <label>Data (DD/MM/AAAA):
                <input type="text" name="data" placeholder="DD/MM/AAAA" required>
            </label>
            <label>Quantidade:
                <input type="number" name="quantidade" min="0" required>
            </label>
            <button type="submit">Adicionar</button>
        </form>

        <a href="index.php">Voltar</a>
    </div>
</div>
<div class="footer-fix">
    <?php include 'includes/footer.php'; ?>
</div>
</body>
</html>