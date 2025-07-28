<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
include 'includes/menu.php';
include 'includes/db.php'; // conexão MySQLi

$result = $conn->query("SELECT * FROM talentos_curriculos ORDER BY data_cadastro DESC");
?>
<div class="container">
    <h2 class="mb-4">Currículos Cadastrados</h2>
    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle">
            <thead class="table-primary">
                <tr>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>Arquivo</th>
                    <th>Data de Cadastro</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($c = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($c['nome']) ?></td>
                        <td><?= htmlspecialchars($c['email']) ?></td>
                        <td>
                            <a class="btn btn-sm btn-outline-primary" href="uploads/<?= urlencode($c['arquivo']) ?>" target="_blank">
                                Visualizar
                            </a>
                            <a class="btn btn-sm btn-outline-success ms-1" href="uploads/<?= urlencode($c['arquivo']) ?>" download>
                                Baixar
                            </a>
                        </td>
                        <td><?= date('d/m/Y H:i', strtotime($c['data_cadastro'])) ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>