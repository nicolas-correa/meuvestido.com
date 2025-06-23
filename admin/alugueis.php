<?php
// Inclui configurações
require_once '../includes/config.php';

// Verifica se o administrador está logado
if (!isset($_SESSION['admin_logado']) || $_SESSION['admin_logado'] !== true) {
    header("Location: login.php");
    exit;
}

// Inicia variáveis
$mensagem_sucesso = '';
$mensagem_erro = '';
$alugueis = [];

// Processa cancelamento de aluguel
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cancelar_aluguel'])) {
    $aluguel_id = (int)$_POST['aluguel_id'];
    
    $conn = conectarDB();
    try {
        // Verifica se o aluguel existe
        $sql_check = "SELECT * FROM public.aluguel WHERE cpaluguel = :aluguel_id";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bindParam(':aluguel_id', $aluguel_id, PDO::PARAM_INT);
        $stmt_check->execute();
        
        if ($stmt_check->fetch()) {
            // Exclui o aluguel
            $sql_delete = "DELETE FROM public.aluguel WHERE cpaluguel = :aluguel_id";
            $stmt_delete = $conn->prepare($sql_delete);
            $stmt_delete->bindParam(':aluguel_id', $aluguel_id, PDO::PARAM_INT);
            
            if ($stmt_delete->execute()) {
                $mensagem_sucesso = "Aluguel cancelado com sucesso!";
            } else {
                $mensagem_erro = "Erro ao cancelar o aluguel.";
            }
        } else {
            $mensagem_erro = "Aluguel não encontrado.";
        }
    } catch (PDOException $e) {
        $mensagem_erro = "Erro ao processar o cancelamento.";
        error_log("Erro ao cancelar aluguel: " . $e->getMessage());
    } finally {
        $conn = null;
    }
}

// Busca todos os aluguéis
$conn = conectarDB();
try {
    $sql = "SELECT a.cpaluguel, u.nome as nome_cliente, v.nome as nome_vestido, 
            a.datainicio, a.datafim, a.valortotal, u.cpusuario, v.cpvestido
            FROM public.aluguel a 
            JOIN public.usuarios u ON a.cecomprador = u.cpusuario 
            JOIN public.vestidos v ON a.cevestidoalugado = v.cpvestido 
            ORDER BY a.datainicio DESC";
    $stmt = $conn->query($sql);
    $alugueis = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $mensagem_erro = "Erro ao carregar aluguéis.";
    error_log("Erro ao buscar aluguéis: " . $e->getMessage());
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
    <title>Gerenciar Aluguéis - MeuVestido.com</title>
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
        
        .filter-options {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .filter-options select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: white;
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
                    <li><a href="alugueis.php" class="active"><i class="fas fa-calendar-alt"></i> Aluguéis</a></li>
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
                <h1>Gerenciar Aluguéis</h1>
                <a href="aluguel_adicionar.php" class="btn btn-success"><i class="fas fa-plus"></i> Registrar Novo Aluguel</a>
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
                <input type="text" id="searchAluguel" placeholder="Buscar por cliente ou vestido...">
                <button class="btn" onclick="searchAlugueis()"><i class="fas fa-search"></i> Buscar</button>
            </div>
            

            <div class="filter-options">
                <select id="filterPeriodo" onchange="filterAlugueis()">
                    <option value="todos">Todos os períodos</option>
                    <option value="atual">Aluguéis atuais</option>
                    <option value="futuro">Aluguéis futuros</option>
                    <option value="passado">Aluguéis passados</option>
                </select>
            </div>
            

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Cliente</th>
                            <th>Vestido</th>
                            <th>Data Início</th>
                            <th>Data Fim</th>
                            <th>Valor Total</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($alugueis)): ?>
                            <?php foreach ($alugueis as $aluguel): 
                                $hoje = date('Y-m-d');
                                $status = '';
                                $status_class = '';
                                
                                if ($aluguel['datainicio'] > $hoje) {
                                    $status = 'Agendado';
                                    $status_class = 'text-warning';
                                } elseif ($aluguel['datafim'] < $hoje) {
                                    $status = 'Finalizado';
                                    $status_class = 'text-success';
                                } else {
                                    $status = 'Em andamento';
                                    $status_class = 'text-info';
                                }
                            ?>
                                <tr data-inicio="<?php echo $aluguel['datainicio']; ?>" data-fim="<?php echo $aluguel['datafim']; ?>">
                                    <td><?php echo $aluguel['cpaluguel']; ?></td>
                                    <td>
                                        <a href="usuario_detalhe.php?id=<?php echo $aluguel['cpusuario']; ?>">
                                            <?php echo htmlspecialchars($aluguel['nome_cliente']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <a href="vestido_detalhe.php?id=<?php echo $aluguel['cpvestido']; ?>">
                                            <?php echo htmlspecialchars($aluguel['nome_vestido']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($aluguel['datainicio'])); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($aluguel['datafim'])); ?></td>
                                    <td>R$ <?php echo number_format($aluguel['valortotal'], 2, ',', '.'); ?></td>
                                    <td class="<?php echo $status_class; ?>"><?php echo $status; ?></td>
                                    <td class="actions">
                                        <form method="post" action="alugueis.php" onsubmit="return confirm('Tem certeza que deseja cancelar este aluguel?');">
                                            <input type="hidden" name="aluguel_id" value="<?php echo $aluguel['cpaluguel']; ?>">
                                            <button type="submit" name="cancelar_aluguel" class="btn btn-danger"><i class="fas fa-times"></i> Cancelar</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align: center;">Nenhum aluguel encontrado.</td>
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
        function searchAlugueis() {
            const searchTerm = document.getElementById('searchAluguel').value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const cliente = row.cells[1].textContent.toLowerCase();
                const vestido = row.cells[2].textContent.toLowerCase();
                
                if (cliente.includes(searchTerm) || vestido.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
        
        function filterAlugueis() {
            const filterValue = document.getElementById('filterPeriodo').value;
            const rows = document.querySelectorAll('tbody tr');
            const hoje = new Date().toISOString().split('T')[0];
            
            rows.forEach(row => {
                const dataInicio = row.getAttribute('data-inicio');
                const dataFim = row.getAttribute('data-fim');
                
                if (filterValue === 'todos') {
                    row.style.display = '';
                } else if (filterValue === 'atual' && dataInicio <= hoje && dataFim >= hoje) {
                    row.style.display = '';
                } else if (filterValue === 'futuro' && dataInicio > hoje) {
                    row.style.display = '';
                } else if (filterValue === 'passado' && dataFim < hoje) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>
