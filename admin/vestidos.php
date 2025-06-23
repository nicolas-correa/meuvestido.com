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
$vestidos = [];

// Processa exclusão de vestido
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['excluir_vestido'])) {
    $vestido_id = (int)$_POST['vestido_id'];
    
    $conn = conectarDB();
    try {
        // Verifica se o vestido está em algum aluguel
        $sql_check = "SELECT COUNT(*) FROM public.aluguel WHERE cevestidoalugado = :vestido_id";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bindParam(':vestido_id', $vestido_id, PDO::PARAM_INT);
        $stmt_check->execute();
        
        if ($stmt_check->fetchColumn() > 0) {
            $mensagem_erro = "Não é possível excluir este vestido pois ele está associado a um ou mais aluguéis.";
        } else {
            // Exclui imagens associadas ao vestido
            $sql_delete_img = "DELETE FROM public.imagens WHERE cevestido = :vestido_id";
            $stmt_delete_img = $conn->prepare($sql_delete_img);
            $stmt_delete_img->bindParam(':vestido_id', $vestido_id, PDO::PARAM_INT);
            $stmt_delete_img->execute();
            
            // Exclui feedbacks associados ao vestido
            $sql_delete_feedback = "DELETE FROM public.feedback WHERE cevestidofeedback = :vestido_id";
            $stmt_delete_feedback = $conn->prepare($sql_delete_feedback);
            $stmt_delete_feedback->bindParam(':vestido_id', $vestido_id, PDO::PARAM_INT);
            $stmt_delete_feedback->execute();
            
            // Exclui o vestido
            $sql_delete = "DELETE FROM public.vestidos WHERE cpvestido = :vestido_id";
            $stmt_delete = $conn->prepare($sql_delete);
            $stmt_delete->bindParam(':vestido_id', $vestido_id, PDO::PARAM_INT);
            
            if ($stmt_delete->execute()) {
                $mensagem_sucesso = "Vestido excluído com sucesso!";
            } else {
                $mensagem_erro = "Erro ao excluir o vestido.";
            }
        }
    } catch (PDOException $e) {
        $mensagem_erro = "Erro ao processar a exclusão.";
        error_log("Erro ao excluir vestido: " . $e->getMessage());
    } finally {
        $conn = null;
    }
}

// Busca todos os vestidos
$conn = conectarDB();
try {
    $sql = "SELECT v.cpvestido, v.nome, v.descricao, v.valoraluguel, v.tamanho, v.disponivel,
            (SELECT i.caminho_imagem FROM public.imagens i WHERE i.cevestido = v.cpvestido ORDER BY i.cpimagem LIMIT 1) as imagem_principal
            FROM public.vestidos v
            ORDER BY v.cpvestido DESC";
    $stmt = $conn->query($sql);
    $vestidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $mensagem_erro = "Erro ao carregar vestidos.";
    error_log("Erro ao buscar vestidos: " . $e->getMessage());
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
    <title>Gerenciar Vestidos - MeuVestido.com</title>
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
            border-bottom: 1px solid rgba(255,255,255,0.1);
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
            color: rgba(255,255,255,0.6);
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
        
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background-color: rgba(255,255,255,0.1);
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
        
        .btn-success {
            background-color: var(--success-color);
        }
        
        .btn-danger {
            background-color: var(--danger-color);
        }
        
        .btn-warning {
            background-color: var(--warning-color);
            color: #333;
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
        
        .vestidos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .vestido-card {
            background-color: var(--secondary-color);
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: all 0.3s;
        }
        
        .vestido-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .vestido-image {
            height: 200px;
            overflow: hidden;
            position: relative;
        }
        
        .vestido-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .vestido-status {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-disponivel {
            background-color: var(--success-color);
            color: white;
        }
        
        .status-indisponivel {
            background-color: var(--danger-color);
            color: white;
        }
        
        .vestido-info {
            padding: 15px;
        }
        
        .vestido-info h3 {
            margin-bottom: 10px;
            font-size: 18px;
        }
        
        .vestido-info p {
            margin-bottom: 10px;
            color: #666;
        }
        
        .vestido-info .price {
            font-size: 18px;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 15px;
        }
        
        .vestido-actions {
            display: flex;
            justify-content: space-between;
        }
        
        .vestido-actions form {
            display: inline;
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
        
        .pagination a:hover, .pagination a.active {
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
                    <li><a href="vestidos.php" class="active"><i class="fas fa-tshirt"></i> Vestidos</a></li>
                    <li><a href="usuarios.php"><i class="fas fa-users"></i> Usuários</a></li>
                    <li><a href="alugueis.php"><i class="fas fa-calendar-alt"></i> Aluguéis</a></li>
                    <li><a href="feedbacks.php"><i class="fas fa-comments"></i> Feedbacks</a></li>
                </ul>
                
                <h3>Configurações</h3>
                <ul>
                    
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a></li>
                </ul>
            </div>
        </div>
        

        <div class="main-content">
            <div class="page-header">
                <h1>Gerenciar Vestidos</h1>
                <a href="vestido_adicionar.php" class="btn btn-success"><i class="fas fa-plus"></i> Adicionar Novo Vestido</a>
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
                <input type="text" id="searchVestido" placeholder="Buscar vestido por nome...">
                <button class="btn" onclick="searchVestidos()"><i class="fas fa-search"></i> Buscar</button>
            </div>
            

            <div class="vestidos-grid">
                <?php if (!empty($vestidos)): ?>
                    <?php foreach ($vestidos as $vestido): ?>
                        <div class="vestido-card">
                            <div class="vestido-image">
                                <img src="<?php echo htmlspecialchars($vestido['imagem_principal'] ?: '../images/placeholder.png'); ?>" alt="<?php echo htmlspecialchars($vestido['nome']); ?>">
                                <div class="vestido-status <?php echo $vestido['disponivel'] ? 'status-disponivel' : 'status-indisponivel'; ?>">
                                    <?php echo $vestido['disponivel'] ? 'Disponível' : 'Indisponível'; ?>
                                </div>
                            </div>
                            <div class="vestido-info">
                                <h3><?php echo htmlspecialchars($vestido['nome']); ?></h3>
                                <p><?php echo mb_strimwidth(htmlspecialchars($vestido['descricao']), 0, 100, "..."); ?></p>
                                <p>Tamanho: <?php echo htmlspecialchars($vestido['tamanho']); ?></p>
                                <div class="price">R$ <?php echo number_format($vestido['valoraluguel'], 2, ',', '.'); ?></div>
                                <div class="vestido-actions">
                                    <a href="vestido_editar.php?id=<?php echo $vestido['cpvestido']; ?>" class="btn btn-warning"><i class="fas fa-edit"></i> Editar</a>
                                    <form method="post" action="vestidos.php" onsubmit="return confirm('Tem certeza que deseja excluir este vestido?');">
                                        <input type="hidden" name="vestido_id" value="<?php echo $vestido['cpvestido']; ?>">
                                        <button type="submit" name="excluir_vestido" class="btn btn-danger"><i class="fas fa-trash"></i> Excluir</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="grid-column: 1 / -1; text-align: center; padding: 30px;">Nenhum vestido encontrado.</p>
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
        function searchVestidos() {
            const searchTerm = document.getElementById('searchVestido').value.toLowerCase();
            const vestidoCards = document.querySelectorAll('.vestido-card');
            
            vestidoCards.forEach(card => {
                const vestidoName = card.querySelector('h3').textContent.toLowerCase();
                if (vestidoName.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>
