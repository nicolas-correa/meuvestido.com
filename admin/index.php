<?php
// Inclui configurações
require_once '../includes/config.php';

// Verifica se o administrador está logado
if (!isset($_SESSION['admin_logado']) || $_SESSION['admin_logado'] !== true) {
    header("Location: login.php");
    exit;
}

// Obtem contagens para o dashboard
$conn = conectarDB();
$total_vestidos = 0;
$total_usuarios = 0;
$total_alugueis = 0;
$total_feedbacks = 0;

try {
    // Conta vestidos
    $sql = "SELECT COUNT(*) FROM public.vestidos";
    $stmt = $conn->query($sql);
    $total_vestidos = $stmt->fetchColumn();
    
    // Conta usuários
    $sql = "SELECT COUNT(*) FROM public.usuarios WHERE admin = false";
    $stmt = $conn->query($sql);
    $total_usuarios = $stmt->fetchColumn();
    
    // Conta aluguéis
    $sql = "SELECT COUNT(*) FROM public.aluguel";
    $stmt = $conn->query($sql);
    $total_alugueis = $stmt->fetchColumn();
    
    // Conta feedbacks
    $sql = "SELECT COUNT(*) FROM public.feedback";
    $stmt = $conn->query($sql);
    $total_feedbacks = $stmt->fetchColumn();
    
} catch (PDOException $e) {
    error_log("Erro ao obter contagens: " . $e->getMessage());
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
    <title>Painel Administrativo - MeuVestido.com</title>
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
        
        .user-info {
            display: flex;
            align-items: center;
        }
        
        .user-info span {
            margin-right: 15px;
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
        
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .card {
            background-color: var(--secondary-color);
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            text-align: center;
            transition: all 0.3s;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .card i {
            font-size: 40px;
            margin-bottom: 15px;
            color: var(--primary-color);
        }
        
        .card h3 {
            font-size: 18px;
            margin-bottom: 10px;
        }
        
        .card p {
            font-size: 24px;
            font-weight: bold;
        }
        
        .recent-section {
            background-color: var(--secondary-color);
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .recent-section h2 {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .recent-section table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .recent-section th, .recent-section td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .recent-section th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        
        .recent-section tr:hover {
            background-color: #f8f9fa;
        }
        
        .recent-section .btn {
            padding: 5px 10px;
            font-size: 12px;
        }
        
        .view-all {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .view-all:hover {
            text-decoration: underline;
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
                    <li><a href="index.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="vestidos.php"><i class="fas fa-tshirt"></i> Vestidos</a></li>
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
                <h1>Dashboard</h1>
                <div class="user-info">
                    <span>Olá, <?php echo htmlspecialchars($_SESSION['admin_nome']); ?></span>
                    <a href="logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Sair</a>
                </div>
            </div>
            

            <div class="dashboard-cards">
                <div class="card">
                    <i class="fas fa-tshirt"></i>
                    <h3>Vestidos</h3>
                    <p><?php echo $total_vestidos; ?></p>
                </div>
                
                <div class="card">
                    <i class="fas fa-users"></i>
                    <h3>Usuários</h3>
                    <p><?php echo $total_usuarios; ?></p>
                </div>
                
                <div class="card">
                    <i class="fas fa-calendar-alt"></i>
                    <h3>Aluguéis</h3>
                    <p><?php echo $total_alugueis; ?></p>
                </div>
                
                <div class="card">
                    <i class="fas fa-comments"></i>
                    <h3>Feedbacks</h3>
                    <p><?php echo $total_feedbacks; ?></p>
                </div>
            </div>
            

            <div class="recent-section">
                <h2>Aluguéis Recentes</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Cliente</th>
                            <th>Vestido</th>
                            <th>Data Início</th>
                            <th>Data Fim</th>
                            <th>Valor</th>
                           
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $conn = conectarDB();
                        try {
                            $sql = "SELECT a.cpaluguel, u.nome as nome_cliente, v.nome as nome_vestido, 
                                    a.datainicio, a.datafim, a.valortotal 
                                    FROM public.aluguel a 
                                    JOIN public.usuarios u ON a.cecomprador = u.cpusuario 
                                    JOIN public.vestidos v ON a.cevestidoalugado = v.cpvestido 
                                    ORDER BY a.datainicio DESC LIMIT 5";
                            $stmt = $conn->query($sql);
                            $alugueis_recentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            if (!empty($alugueis_recentes)) {
                                foreach ($alugueis_recentes as $aluguel) {
                                    echo '<tr>';
                                    echo '<td>' . $aluguel['cpaluguel'] . '</td>';
                                    echo '<td>' . htmlspecialchars($aluguel['nome_cliente']) . '</td>';
                                    echo '<td>' . htmlspecialchars($aluguel['nome_vestido']) . '</td>';
                                    echo '<td>' . date('d/m/Y', strtotime($aluguel['datainicio'])) . '</td>';
                                    echo '<td>' . date('d/m/Y', strtotime($aluguel['datafim'])) . '</td>';
                                    echo '<td>R$ ' . number_format($aluguel['valortotal'], 2, ',', '.') . '</td>';
                                    
                                    echo '</tr>';
                                }
                            } else {
                                echo '<tr><td colspan="7" style="text-align: center;">Nenhum aluguel encontrado.</td></tr>';
                            }
                        } catch (PDOException $e) {
                            error_log("Erro ao buscar aluguéis recentes: " . $e->getMessage());
                            echo '<tr><td colspan="7" style="text-align: center;">Erro ao carregar dados.</td></tr>';
                        } finally {
                            $conn = null;
                        }
                        ?>
                    </tbody>
                </table>
                <a href="alugueis.php" class="view-all">Ver todos os aluguéis</a>
            </div>
            

            <div class="recent-section">
                <h2>Usuários Recentes</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Data de Cadastro</th>
                           
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $conn = conectarDB();
                        try {
                            $sql = "SELECT cpusuario, nome, email, dtanascimento FROM public.usuarios 
                                    WHERE admin = false ORDER BY cpusuario DESC LIMIT 5";
                            $stmt = $conn->query($sql);
                            $usuarios_recentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            if (!empty($usuarios_recentes)) {
                                foreach ($usuarios_recentes as $usuario) {
                                    echo '<tr>';
                                    echo '<td>' . $usuario['cpusuario'] . '</td>';
                                    echo '<td>' . htmlspecialchars($usuario['nome']) . '</td>';
                                    echo '<td>' . htmlspecialchars($usuario['email']) . '</td>';
                                    echo '<td>' . date('d/m/Y', strtotime($usuario['dtanascimento'])) . '</td>';
                                    echo '</tr>';
                                }
                            } else {
                                echo '<tr><td colspan="5" style="text-align: center;">Nenhum usuário encontrado.</td></tr>';
                            }
                        } catch (PDOException $e) {
                            error_log("Erro ao buscar usuários recentes: " . $e->getMessage());
                            echo '<tr><td colspan="5" style="text-align: center;">Erro ao carregar dados.</td></tr>';
                        } finally {
                            $conn = null;
                        }
                        ?>
                    </tbody>
                </table>
                <a href="usuarios.php" class="view-all">Ver todos os usuários</a>
            </div>
        </div>
    </div>
</body>
</html>
