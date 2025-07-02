<?php
if (isset($_POST['preview'])) {
    if (isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] == 0) {
        $file = $_FILES['arquivo']['tmp_name'];

        echo "<h2>Pré-visualização do CSV</h2>";

        if (($handle = fopen($file, 'r')) !== FALSE) {
            echo "<form action='importar_csv.php' method='post'>";
            echo "<input type='hidden' name='arquivo_temp' value='" . $file . "'>";
            echo "<table border='1' cellpadding='5'><tr><th>Data</th><th>Quantidade</th></tr>";

            $linhas = [];
            fgetcsv($handle, 1000, ','); // Pula o cabeçalho
            while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
                $data_importada = trim($data[0]);
                $quantidade = trim($data[1]);

                echo "<tr><td>$data_importada</td><td>$quantidade</td></tr>";
                $linhas[] = [$data_importada, $quantidade];
            }

            echo "</table><br>";
            fclose($handle);

            // Salvar o conteúdo em sessão para posterior importação
            session_start();
            $_SESSION['dados_csv'] = $linhas;

            echo "<input type='submit' name='importar' value='Importar para o banco'>";
            echo "</form>";
        } else {
            echo "Erro ao abrir o arquivo.";
        }
    } else {
        echo "Por favor, selecione um arquivo CSV válido.";
    }
} elseif (isset($_POST['importar'])) {
    session_start();
    $dados_csv = $_SESSION['dados_csv'] ?? [];

    $conn = new mysqli('localhost', 'root', '', 'fluxo_pessoas');
    if ($conn->connect_error) {
        die("Erro na conexão: " . $conn->connect_error);
    }

    foreach ($dados_csv as $linha) {
        $data_importada = $linha[0];
        $quantidade = (int) $linha[1];

        $sql = "INSERT INTO registros_fluxo (data_registro, quantidade) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('si', $data_importada, $quantidade);

        if ($stmt->execute()) {
            echo "✅ Inserido: $data_importada | $quantidade <br>";
        } else {
            echo "❌ Erro: " . $stmt->error . "<br>";
        }
    }

    $conn->close();
    unset($_SESSION['dados_csv']);
}
?>
