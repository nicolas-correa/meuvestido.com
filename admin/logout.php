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

// Processa logout
if (isset($_GET['confirm']) && $_GET['confirm'] == 'yes') {
    // Destrui sessão
    session_destroy();
    
    // Redireciona para a página de login
    header("Location: login.php");
    exit;
}

// HTML 
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sair - MeuVestido.com</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #000;
            --secondary-color: #fff;
            --accent-color: #f0f0f0;
            --danger-color: #dc3545;
            --success-color: #28a745;
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
        
        .logout-container {
            max-width: 500px;
            margin: 100px auto;
            padding: 30px;
            background-color: var(--secondary-color);
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .logout-container h1 {
            margin-bottom: 20px;
            color: var(--primary-color);
        }
        
        .logout-container p {
            margin-bottom: 30px;
        }
        
        .btn-container {
            display: flex;
            justify-content: center;
            gap: 20px;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: var(--secondary-color);
        }
        
        .btn-danger {
            background-color: var(--danger-color);
            color: var(--secondary-color);
        }
        
        .btn:hover {
            opacity: 0.9;
        }
        
        .icon {
            font-size: 48px;
            margin-bottom: 20px;
            color: var(--primary-color);
        }
    </style>
</head>
<body>
    <div class="logout-container">
        <i class="fas fa-sign-out-alt icon"></i>
        <h1>Sair do Painel Administrativo</h1>
        <p>Tem certeza que deseja sair do painel administrativo?</p>
        <div class="btn-container">
            <a href="index.php" class="btn btn-primary">Cancelar</a>
            <a href="logout.php?confirm=yes" class="btn btn-danger">Sim, Sair</a>
        </div>
    </div>
</body>
</html>
