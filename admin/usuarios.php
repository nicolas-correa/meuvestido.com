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
$usuarios = [];

// Processa exclusão de usuário
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['excluir_usuario'])) {
    $usuario_id = (int)$_POST['usuario_id'];
    
    $conn = conectarDB();
    try {
        // Verifica se o usuário tem aluguéis
        $sql_check = "SELECT COUNT(*) FROM public.aluguel WHERE cecomprador = :usuario_id";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
        $stmt_check->execute();
        
        if ($stmt_check->fetchColumn() > 0) {
            $mensagem_erro = "Não é possível excluir este usuário pois ele possui aluguéis registrados.";
        } else {
            // Exclui feedbacks do usuário
            $sql_delete_feedback = "DELETE FROM public.feedback WHERE ceusuariofeedback = :usuario_id";
            $stmt_delete_feedback = $conn->prepare($sql_delete_feedback);
            $stmt_delete_feedback->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
            $stmt_delete_feedback->execute();
            
            // Exclui endereços do usuário
            $sql_delete_endereco = "DELETE FROM public.enderecos WHERE cemorador = :usuario_id";
            $stmt_delete_endereco = $conn->prepare($sql_delete_endereco);
            $stmt_delete_endereco->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
            $stmt_delete_endereco->execute();
            
            // Exclui o usuário
            $sql_delete = "DELETE FROM public.usuarios WHERE cpusuario = :usuario_id";
            $stmt_delete = $conn->prepare($sql_delete);
            $stmt_delete->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
            
            if ($stmt_delete->execute()) {
                $mensagem_sucesso = "Usuário excluído com sucesso!";
            } else {
                $mensagem_erro = "Erro ao excluir o usuário.";
            }
        }
    } catch (PDOException $e) {
        $mensagem_erro = "Erro ao processar a exclusão.";
        error_log("Erro ao excluir usuário: " . $e->getMessage());
    } finally {
        $conn = null;
    }
}

// Busca todos os usuários (exceto administradores)
$conn = conectarDB();
try {
    $sql = "SELECT cpusuario, nome, email, cpf, dtanascimento, telefone 
            FROM public.usuarios 
            WHERE admin = false
            ORDER BY cpusuario DESC";
    $stmt = $conn->query($sql);
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $mensagem_erro = "Erro ao carregar usuários.";
    error_log("Erro ao buscar usuários: " . $e->getMessage());
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
    <title>Gerenciar Usuários - MeuVestido.com</title>
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
        
        .table-container {
            background-color: var(--secondary-color);
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        
        tr:hover {
            background-color: #f8f9fa;
        }
        
        .actions {
            display: flex;
            gap: 10px;
        }
        
        .actions form {
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
                    <li><a href="vestidos.php"><i class="fas fa-tshirt"></i> Vestidos</a></li>
                    <li><a href="usuarios.php" class="active"><i class="fas fa-users"></i> Usuários</a></li>
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
                <h1>Gerenciar Usuários</h1>
                <a href="usuario_adicionar.php" class="btn btn-success"><i class="fas fa-plus"></i> Adicionar Novo Usuário</a>
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
                <input type="text" id="searchUsuario" placeholder="Buscar usuário por nome ou email...">
                <button class="btn" onclick="searchUsuarios()"><i class="fas fa-search"></i> Buscar</button>
            </div>
            
 
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>CPF</th>
                            <th>Data de Nascimento</th>
                            <th>Telefone</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($usuarios)): ?>
                            <?php foreach ($usuarios as $usuario): ?>
                                <tr>
                                    <td><?php echo $usuario['cpusuario']; ?></td>
                                    <td><?php echo htmlspecialchars($usuario['nome']); ?></td>
                                    <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                    <td><?php echo htmlspecialchars($usuario['cpf'] ?: 'Não informado'); ?></td>
                                    <td><?php echo $usuario['dtanascimento'] ? date('d/m/Y', strtotime($usuario['dtanascimento'])) : 'Não informado'; ?></td>
                                    <td><?php echo htmlspecialchars($usuario['telefone'] ?: 'Não informado'); ?></td>
                                    <td class="actions">
                                        <form method="post" action="usuarios.php" onsubmit="return confirm('Tem certeza que deseja excluir este usuário?');">
                                            <input type="hidden" name="usuario_id" value="<?php echo $usuario['cpusuario']; ?>">
                                            <button type="submit" name="excluir_usuario" class="btn btn-danger"><i class="fas fa-trash"></i> Excluir</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center;">Nenhum usuário encontrado.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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
        function searchUsuarios() {
            const searchTerm = document.getElementById('searchUsuario').value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const nome = row.cells[1].textContent.toLowerCase();
                const email = row.cells[2].textContent.toLowerCase();
                
                if (nome.includes(searchTerm) || email.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>
