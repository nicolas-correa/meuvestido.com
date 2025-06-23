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
$vestido = [];
$imagens = [];

// Verifica se o ID foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: vestidos.php");
    exit;
}

$vestido_id = (int)$_GET['id'];

// Busca todos os usuários para o select
$usuarios = [];
$conn = conectarDB();
try {
    $sql_usuarios = "SELECT cpusuario, nome, email FROM public.usuarios ORDER BY nome";
    $stmt_usuarios = $conn->query($sql_usuarios);
    $usuarios = $stmt_usuarios->fetchAll(PDO::FETCH_ASSOC);
    
    // Busca vestido
    $sql = "SELECT * FROM public.vestidos WHERE cpvestido = :vestido_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':vestido_id', $vestido_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $vestido = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$vestido) {
        header("Location: vestidos.php");
        exit;
    }
    
    // Busca imagens do vestido
    $sql_img = "SELECT * FROM public.imagens WHERE cevestido = :vestido_id ORDER BY cpimagem";
    $stmt_img = $conn->prepare($sql_img);
    $stmt_img->bindParam(':vestido_id', $vestido_id, PDO::PARAM_INT);
    $stmt_img->execute();
    
    $imagens = $stmt_img->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $mensagem_erro = "Erro ao carregar dados do vestido.";
    error_log("Erro ao buscar vestido: " . $e->getMessage());
} finally {
    $conn = null;
}

// Processa formulário
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['atualizar_vestido'])) {
    // Obtem dados do formulário
    $nome = limparDados($_POST['nome']);
    $descricao = limparDados($_POST['descricao']);
    $valoraluguel = str_replace(',', '.', limparDados($_POST['valoraluguel']));
    $tamanho = limparDados($_POST['tamanho']);
    $disponivel = isset($_POST['disponivel']) ? 1 : 0;
    $cedono = (int)$_POST['cedono'];
    
    // Valida campos
    if (empty($nome) || empty($descricao) || empty($valoraluguel) || empty($tamanho) || empty($cedono)) {
        $mensagem_erro = "Por favor, preencha todos os campos obrigatórios.";
    } else {
        $conn = conectarDB();
        try {
            // Inicia transação
            $conn->beginTransaction();
            
            // Atualiza vestido
            $sql = "UPDATE public.vestidos 
                    SET nome = :nome, descricao = :descricao, valoraluguel = :valoraluguel, 
                        tamanho = :tamanho, disponivel = :disponivel, cedono = :cedono
                    WHERE cpvestido = :vestido_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':nome', $nome, PDO::PARAM_STR);
            $stmt->bindParam(':descricao', $descricao, PDO::PARAM_STR);
            $stmt->bindParam(':valoraluguel', $valoraluguel, PDO::PARAM_STR);
            $stmt->bindParam(':tamanho', $tamanho, PDO::PARAM_STR);
            $stmt->bindParam(':disponivel', $disponivel, PDO::PARAM_BOOL);
            $stmt->bindParam(':cedono', $cedono, PDO::PARAM_INT);
            $stmt->bindParam(':vestido_id', $vestido_id, PDO::PARAM_INT);
            $stmt->execute();
            
            // Processa exclusão de imagens
            if (isset($_POST['excluir_imagens']) && !empty($_POST['excluir_imagens'])) {
                foreach ($_POST['excluir_imagens'] as $imagem_id) {
                    // Busca caminho da imagem
                    $sql_img = "SELECT caminho_imagem FROM public.imagens WHERE cpimagem = :imagem_id AND cevestido = :vestido_id";
                    $stmt_img = $conn->prepare($sql_img);
                    $stmt_img->bindParam(':imagem_id', $imagem_id, PDO::PARAM_INT);
                    $stmt_img->bindParam(':vestido_id', $vestido_id, PDO::PARAM_INT);
                    $stmt_img->execute();
                    
                    $imagem = $stmt_img->fetch(PDO::FETCH_ASSOC);
                    
                    if ($imagem) {
                        // Exclui arquivo físico
                        $caminho_completo = "../" . $imagem['caminho_imagem'];
                        if (file_exists($caminho_completo)) {
                            unlink($caminho_completo);
                        }
                        
                        // Exclui registro do banco
                        $sql_del = "DELETE FROM public.imagens WHERE cpimagem = :imagem_id";
                        $stmt_del = $conn->prepare($sql_del);
                        $stmt_del->bindParam(':imagem_id', $imagem_id, PDO::PARAM_INT);
                        $stmt_del->execute();
                    }
                }
            }
            
            // Processa upload de novas imagens
            if (!empty($_FILES['imagens']['name'][0])) {
                $upload_dir = "../images/vestidos/";
                
                // Cria diretório se não existir
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $total_files = count($_FILES['imagens']['name']);
                
                for ($i = 0; $i < $total_files; $i++) {
                    $tmp_name = $_FILES['imagens']['tmp_name'][$i];
                    
                    if (!empty($tmp_name)) {
                        $filename = $vestido_id . '_' . time() . '_' . $i . '.jpg';
                        $target_file = $upload_dir . $filename;
                        
                        if (move_uploaded_file($tmp_name, $target_file)) {
                            // Inseri referência da imagem no banco
                            $caminho_imagem = "images/vestidos/" . $filename;
                            $nome_imagem = "Imagem " . ($i + 1) . " do vestido " . $nome;
                            $sql_img = "INSERT INTO public.imagens (cevestido, caminho_imagem, nome) VALUES (:vestido_id, :caminho, :nome)";
                            $stmt_img = $conn->prepare($sql_img);
                            $stmt_img->bindParam(':vestido_id', $vestido_id, PDO::PARAM_INT);
                            $stmt_img->bindParam(':caminho', $caminho_imagem, PDO::PARAM_STR);
                            $stmt_img->bindParam(':nome', $nome_imagem, PDO::PARAM_STR);
                            $stmt_img->execute();
                        } else {
                            throw new Exception("Erro ao fazer upload da imagem " . ($i + 1));
                        }
                    }
                }
            }
            
            // Confirma transação
            $conn->commit();
            
            $mensagem_sucesso = "Vestido atualizado com sucesso!";
            
            // Recarrega dados do vestido e imagens
            $sql = "SELECT * FROM public.vestidos WHERE cpvestido = :vestido_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':vestido_id', $vestido_id, PDO::PARAM_INT);
            $stmt->execute();
            $vestido = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $sql_img = "SELECT * FROM public.imagens WHERE cevestido = :vestido_id ORDER BY cpimagem";
            $stmt_img = $conn->prepare($sql_img);
            $stmt_img->bindParam(':vestido_id', $vestido_id, PDO::PARAM_INT);
            $stmt_img->execute();
            $imagens = $stmt_img->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            // Reverte transação em caso de erro
            $conn->rollBack();
            $mensagem_erro = "Erro ao atualizar vestido: " . $e->getMessage();
            error_log("Erro ao atualizar vestido: " . $e->getMessage());
        } finally {
            $conn = null;
        }
    }
}

// HTML 
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Vestido - MeuVestido.com</title>
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
        
        .form-container {
            background-color: var(--secondary-color);
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        
        .form-group textarea {
            height: 150px;
            resize: vertical;
        }
        
        .form-group .checkbox-label {
            display: flex;
            align-items: center;
            font-weight: normal;
        }
        
        .form-group .checkbox-label input {
            margin-right: 10px;
        }
        
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 30px;
        }
        
        .image-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        
        .image-preview-item {
            width: 100px;
            height: 100px;
            border: 1px solid #ddd;
            border-radius: 5px;
            overflow: hidden;
            position: relative;
        }
        
        .image-preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .image-preview-item .remove-image {
            position: absolute;
            top: 5px;
            right: 5px;
            background-color: rgba(0,0,0,0.5);
            color: white;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        
        .current-images {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 10px;
            margin-bottom: 20px;
        }
        
        .current-image-item {
            width: 150px;
            border: 1px solid #ddd;
            border-radius: 5px;
            overflow: hidden;
            position: relative;
        }
        
        .current-image-item img {
            width: 100%;
            height: auto;
            display: block;
        }
        
        .current-image-item .image-actions {
            padding: 5px;
            background-color: #f8f9fa;
            display: flex;
            justify-content: center;
        }
        
        .current-image-item .image-actions label {
            display: flex;
            align-items: center;
            cursor: pointer;
            font-weight: normal;
            margin: 0;
            font-size: 14px;
        }
        
        .current-image-item .image-actions input {
            margin-right: 5px;
        }
        
        .required-field::after {
            content: " *";
            color: var(--danger-color);
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
                <h1>Editar Vestido</h1>
                <a href="vestidos.php" class="btn"><i class="fas fa-arrow-left"></i> Voltar para Vestidos</a>
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
            
            <div class="form-container">
                <form method="post" action="vestido_editar.php?id=<?php echo $vestido_id; ?>" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="cedono" class="required-field">Dono do Vestido</label>
                        <select id="cedono" name="cedono" required>
                            <option value="">Selecione o dono...</option>
                            <?php foreach ($usuarios as $usuario): ?>
                                <option value="<?php echo $usuario['cpusuario']; ?>" <?php echo $vestido['cedono'] == $usuario['cpusuario'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($usuario['nome']); ?> (<?php echo htmlspecialchars($usuario['email']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="nome" class="required-field">Nome do Vestido</label>
                        <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($vestido['nome']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="descricao" class="required-field">Descrição</label>
                        <textarea id="descricao" name="descricao" required><?php echo htmlspecialchars($vestido['descricao']); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="valoraluguel" class="required-field">Valor do Aluguel (R$)</label>
                        <input type="text" id="valoraluguel" name="valoraluguel" value="<?php echo number_format($vestido['valoraluguel'], 2, ',', '.'); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="tamanho" class="required-field">Tamanho</label>
                        <select id="tamanho" name="tamanho" required>
                            <option value="">Selecione...</option>
                            <option value="PP" <?php echo $vestido['tamanho'] == 'PP' ? 'selected' : ''; ?>>PP</option>
                            <option value="P" <?php echo $vestido['tamanho'] == 'P' ? 'selected' : ''; ?>>P</option>
                            <option value="M" <?php echo $vestido['tamanho'] == 'M' ? 'selected' : ''; ?>>M</option>
                            <option value="G" <?php echo $vestido['tamanho'] == 'G' ? 'selected' : ''; ?>>G</option>
                            <option value="GG" <?php echo $vestido['tamanho'] == 'GG' ? 'selected' : ''; ?>>GG</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="disponivel" value="1" <?php echo $vestido['disponivel'] ? 'checked' : ''; ?>>
                            Disponível para aluguel
                        </label>
                    </div>
                    
                    <?php if (!empty($imagens)): ?>
                        <div class="form-group">
                            <label>Imagens Atuais</label>
                            <p style="font-size: 0.9em; color: #666; margin-bottom: 10px;">Marque as imagens que deseja excluir.</p>
                            <div class="current-images">
                                <?php foreach ($imagens as $imagem): ?>
                                    <div class="current-image-item">
                                        <img src="../<?php echo htmlspecialchars($imagem['caminho_imagem']); ?>" alt="Imagem do vestido">
                                        <div class="image-actions">
                                            <label>
                                                <input type="checkbox" name="excluir_imagens[]" value="<?php echo $imagem['cpimagem']; ?>">
                                                Excluir
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="imagens">Adicionar Novas Imagens</label>
                        <input type="file" id="imagens" name="imagens[]" accept="image/*" multiple onchange="previewImages(this)">
                        <p style="font-size: 0.9em; color: #666; margin-top: 5px;">Você pode selecionar múltiplas imagens.</p>
                        <div class="image-preview" id="imagePreview"></div>
                    </div>
                    
                    <div class="form-actions">
                        <a href="vestidos.php" class="btn btn-danger">Cancelar</a>
                        <button type="submit" name="atualizar_vestido" class="btn btn-success">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function previewImages(input) {
            const preview = document.getElementById('imagePreview');
            preview.innerHTML = '';
            
            if (input.files) {
                Array.from(input.files).forEach((file, index) => {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        const div = document.createElement('div');
                        div.className = 'image-preview-item';
                        
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        
                        div.appendChild(img);
                        preview.appendChild(div);
                    }
                    
                    reader.readAsDataURL(file);
                });
            }
        }
        
        // Transforma campo de valor para moeda
        document.getElementById('valoraluguel').addEventListener('input', function(e) {
            let value = e.target.value;
            value = value.replace(/\D/g, '');
            value = value.replace(/(\d)(\d{2})$/, '$1,$2');
            value = value.replace(/(?=(\d{3})+(\D))\B/g, '.');
            e.target.value = value;
        });
    </script>
</body>
</html>
