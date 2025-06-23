<?php
// Inclui configurações
require_once '../includes/config.php';

// Inicia sessão se ainda não estiver iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Se já estiver logado como admin, redireciona para o painel
if (isset($_SESSION['admin_logado']) && $_SESSION['admin_logado'] === true) {
    header("Location: index.php");
    exit;
}

// Inicializa variáveis
$mensagem_erro = '';

// Processa formulário de login
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $email = limparDados($_POST['email']);
    $senha_form = $_POST['senha']; // Senha enviada pelo formulário

    // Valida campos
    if (empty($email) || empty($senha_form)) {
        $mensagem_erro = "Por favor, preencha todos os campos.";
    } else {
        $conn = conectarDB();
        try {
            // Verifica se o usuário existe e é um administrador
            $sql = "SELECT cpusuario, nome, email, senha FROM public.usuarios WHERE email = :email AND admin = true";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($admin) {
                // Verifica senha usando password_verify
                if (password_verify($senha_form, $admin['senha'])) {
                    // Login bem-sucedido
                    $_SESSION['admin_id'] = $admin['cpusuario'];
                    $_SESSION['admin_nome'] = $admin['nome'];
                    $_SESSION['admin_email'] = $admin['email'];
                    $_SESSION['admin_logado'] = true;
                    
                    // Redireciona para o painel administrativo
                    header("Location: index.php");
                    exit;
                } else {
                    $mensagem_erro = "Senha incorreta.";
                }
            } else {
                $mensagem_erro = "E-mail não encontrado ou usuário não é administrador.";
            }
        } catch (PDOException $e) {
            $mensagem_erro = "Erro no login. Tente novamente mais tarde.";
            error_log("Erro de login admin: " . $e->getMessage());
        } finally {
            $conn = null; // Fecha conexão
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
    <title>Login Administrativo - MeuVestido.com</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: Arial, sans-serif;
        }
        .admin-login-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 30px;
            background-color: black;
            color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .admin-login-container h1 {
            text-align: center;
            margin-bottom: 30px;
        }
        .admin-login-container form {
            display: flex;
            flex-direction: column;
        }
        .admin-login-container label {
            margin-bottom: 5px;
        }
        .admin-login-container input {
            padding: 10px;
            margin-bottom: 20px;
            border: none;
            border-radius: 5px;
        }
        .admin-login-container button {
            background-color: white;
            color: black;
            border: none;
            padding: 12px;
            border-radius: 20px;
            cursor: pointer;
            font-weight: bold;
            margin-top: 10px;
        }
        .admin-login-container button:hover {
            background-color: #f0f0f0;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: white;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="admin-login-container">
        <h1>ÁREA ADMINISTRATIVA</h1>
        
        <?php if (!empty($mensagem_erro)): ?>
            <div class="error-message">
                <?php echo $mensagem_erro; ?>
            </div>
        <?php endif; ?>
        
        <form method="post" action="login.php">
            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            
            <label for="senha">Senha</label>
            <input type="password" id="senha" name="senha" required>
            
            <button type="submit" name="login">ENTRAR</button>
        </form>
        
        <a href="../index.php" class="back-link">Voltar para o site</a>
    </div>
</body>
</html>
