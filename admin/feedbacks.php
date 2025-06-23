<?php
// Inclui configurações
require_once '../includes/config.php';

// Verifica se o administrador está logado
if (!isset($_SESSION['admin_logado']) || $_SESSION['admin_logado'] !== true) {
    header("Location: login.php");
    exit;
}

// Inicializa variáveis
$mensagem_sucesso = '';
$mensagem_erro = '';
$feedbacks = [];

// Processa exclusão de feedback
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['excluir_feedback'])) {
    $feedback_id = (int) $_POST['feedback_id'];

    $conn = conectarDB();
    try {
        // Exclui o feedback
        $sql_delete = "DELETE FROM public.feedback WHERE cpfeedback = :feedback_id";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bindParam(':feedback_id', $feedback_id, PDO::PARAM_INT);

        if ($stmt_delete->execute()) {
            $mensagem_sucesso = "Feedback excluído com sucesso!";
        } else {
            $mensagem_erro = "Erro ao excluir o feedback.";
        }
    } catch (PDOException $e) {
        $mensagem_erro = "Erro ao processar a exclusão.";
        error_log("Erro ao excluir feedback: " . $e->getMessage());
    } finally {
        $conn = null;
    }
}

// Busca todos os feedbacks
$conn = conectarDB();
try {
    $sql = "SELECT f.cpfeedback, f.comentario, f.dtafeedback, 
            u.nome as nome_usuario, v.nome as nome_vestido,
            u.cpusuario, v.cpvestido
            FROM public.feedback f 
            JOIN public.usuarios u ON f.ceusuariofeedback = u.cpusuario 
            JOIN public.vestidos v ON f.cevestidofeedback = v.cpvestido 
            ORDER BY f.dtafeedback DESC";
    $stmt = $conn->query($sql);
    $feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $mensagem_erro = "Erro ao carregar feedbacks.";
    error_log("Erro ao buscar feedbacks: " . $e->getMessage());
} finally {
    $conn = null;
}

// HTML
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Feedbacks - MeuVestido.com</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #000;
            --secondary-color: #fff;
            --accent-color: #f0f0f0;
            --danger-color: #dc3545;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            color: #333;
            line-height: 1.6;
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 250px;
            background-color: var(--primary-color);
            color: var(--secondary-color);
            padding: 20px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }

        .sidebar-header h2 {
            margin-bottom: 5px;
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .sidebar-menu h3 {
            padding: 0 20px;
            margin-bottom: 10px;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255, 255, 255, 0.6);
        }

        .sidebar-menu ul {
            list-style: none;
        }

        .sidebar-menu li {
            margin-bottom: 5px;
        }

        .sidebar-menu a {
            display: block;
            padding: 10px 20px;
            color: var(--secondary-color);
            text-decoration: none;
            transition: all 0.3s;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background-color: rgba(255, 255, 255, 0.1);
            border-left: 4px solid var(--secondary-color);
        }

        .sidebar-menu i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
        }

        .page-header {
            margin-bottom: 30px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-header h1 {
            font-size: 24px;
        }

        .btn {
            display: inline-block;
            padding: 8px 15px;
            background-color: var(--primary-color);
            color: var(--secondary-color);
            border: none;
            border-radius: 20px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
        }

        .btn:hover {
            opacity: 0.9;
        }

        .btn-danger {
            background-color: var(--danger-color);
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }

        .search-filter {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }

        .search-filter input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .search-filter button {
            padding: 10px 15px;
        }

        .feedback-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }

        .feedback-card {
            background-color: var(--secondary-color);
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            transition: all 0.3s;
        }

        .feedback-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .feedback-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .feedback-user {
            font-weight: bold;
        }

        .feedback-date {
            color: #666;
            font-size: 0.9em;
        }

        .feedback-product {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .feedback-product a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: bold;
        }

        .feedback-product a:hover {
            text-decoration: underline;
        }

        .feedback-content {
            margin-bottom: 15px;
        }

        .feedback-actions {
            display: flex;
            justify-content: flex-end;
        }

        .feedback-actions form {
            display: inline;
        }

        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 30px;
        }

        .pagination a {
            display: inline-block;
            padding: 8px 12px;
            margin: 0 5px;
            background-color: var(--secondary-color);
            color: var(--primary-color);
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s;
        }

        .pagination a:hover,
        .pagination a.active {
            background-color: var(--primary-color);
            color: var(--secondary-color);
        }
    </style>
</head>

<body>
    <div class="admin-container">

        <div class="sidebar">
            <div class="sidebar-header">
                <h2>MeuVestido</h2>
                <p>Painel Administrativo</p>
            </div>

            <div class="sidebar-menu">
                <h3>Menu Principal</h3>
                <ul>
                    <li><a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="vestidos.php"><i class="fas fa-tshirt"></i> Vestidos</a></li>
                    <li><a href="usuarios.php"><i class="fas fa-users"></i> Usuários</a></li>
                    <li><a href="alugueis.php"><i class="fas fa-calendar-alt"></i> Aluguéis</a></li>
                    <li><a href="feedbacks.php" class="active"><i class="fas fa-comments"></i> Feedbacks</a></li>
                </ul>

                <h3>Configurações</h3>
                <ul>
                    
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a></li>
                </ul>
            </div>
        </div>


        <div class="main-content">
            <div class="page-header">
                <h1>Gerenciar Feedbacks</h1>
            </div>

            <?php if (!empty($mensagem_sucesso)): ?>
                <div class="alert alert-success">
                    <?php echo $mensagem_sucesso; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($mensagem_erro)): ?>
                <div class="alert alert-danger">
                    <?php echo $mensagem_erro; ?>
                </div>
            <?php endif; ?>


            <div class="search-filter">
                <input type="text" id="searchFeedback" placeholder="Buscar por usuário ou vestido...">
                <button class="btn" onclick="searchFeedbacks()"><i class="fas fa-search"></i> Buscar</button>
            </div>


            <div class="feedback-grid">
                <?php if (!empty($feedbacks)): ?>
                    <?php foreach ($feedbacks as $feedback): ?>
                        <div class="feedback-card">
                            <div class="feedback-header">
                                <div class="feedback-user">
                                    
                                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($feedback['nome_usuario']); ?>
                                    
                                </div>
                                <div class="feedback-date">
                                    <i class="fas fa-calendar"></i>
                                    <?php echo date('d/m/Y', strtotime($feedback['dtafeedback'])); ?>
                                </div>
                            </div>

                            <div class="feedback-product">
                                <i class="fas fa-tshirt"></i> Vestido:

                                <?php echo htmlspecialchars($feedback['nome_vestido']); ?>

                            </div>

                            <div class="feedback-content">
                                <p><?php echo nl2br(htmlspecialchars($feedback['comentario'])); ?></p>
                            </div>

                            <div class="feedback-actions">
                                <form method="post" action="feedbacks.php"
                                    onsubmit="return confirm('Tem certeza que deseja excluir este feedback?');">
                                    <input type="hidden" name="feedback_id" value="<?php echo $feedback['cpfeedback']; ?>">
                                    <button type="submit" name="excluir_feedback" class="btn btn-danger"><i
                                            class="fas fa-trash"></i> Excluir</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="grid-column: 1 / -1; text-align: center; padding: 30px;">Nenhum feedback encontrado.</p>
                <?php endif; ?>
            </div>


            <div class="pagination">
                <a href="#" class="active">1</a>
                <a href="#">2</a>
                <a href="#">3</a>
                <a href="#">&raquo;</a>
            </div>
        </div>
    </div>

    <script>
        function searchFeedbacks() {
            const searchTerm = document.getElementById('searchFeedback').value.toLowerCase();
            const feedbackCards = document.querySelectorAll('.feedback-card');

            feedbackCards.forEach(card => {
                const usuario = card.querySelector('.feedback-user').textContent.toLowerCase();
                const vestido = card.querySelector('.feedback-product').textContent.toLowerCase();

                if (usuario.includes(searchTerm) || vestido.includes(searchTerm)) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        }
    </script>
</body>

</html>