<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($titulo_pagina) ? $titulo_pagina . ' - MeuVestido.Com' : 'MeuVestido.Com'; ?></title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="icon" href="imagens/logo.png" type="image/png"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const logoElement = document.getElementById("clickableLogo");
            if (logoElement) {
                logoElement.addEventListener("click", function () {
                    window.location.href = "index.php";
                });
            }
        });
    </script>
    <style>
        #clickableLogo {
            cursor: pointer;
        }
        
        .search-bar {
            display: flex;
            align-items: center;
            margin: 0 20px;
            width: 100%;
            max-width: 500px;
        }
        
        .search-bar form {
            display: flex;
            width: 100%;
        }
        
        .search-bar input[type="text"] {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 20px 0 0 20px;
            width: 70%;
            font-size: 14px;
        }
        
        .search-bar select {
            padding: 8px;
            border: 1px solid #ddd;
            border-left: none;
            font-size: 14px;
            width: 20%;
        }
        
        .search-bar button {
            background-color: #8a5a88;
            color: #fff;
            border: none;
            border-radius: 0 20px 20px 0;
            padding: 8px 15px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .search-bar button i {
            margin-right: 5px;
        }
        
        @media (max-width: 768px) {
            .search-bar {
                order: 3;
                margin-top: 10px;
                width: 100%;
                max-width: none;
            }
            
            header {
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>
    <header>
        <div id="clickableLogo" class="logo">MeuVestido.Com</div>

        <!-- Barra de busca para pesquisa de vestidos -->
        <div class="search-bar">
            <form action="busca.php" method="GET">
                <input type="text" name="nome" placeholder="Buscar vestidos..." aria-label="Buscar vestidos">
                <select name="tamanho" aria-label="Filtrar por tamanho">
                    <option value="">Tamanho</option>
                    <option value="PP">PP</option>
                    <option value="P">P</option>
                    <option value="M">M</option>
                    <option value="G">G</option>
                    <option value="GG">GG</option>
                </select>
                <button type="submit"><i class="fas fa-search"></i></button>
            </form>
        </div>

        <div class="header-buttons">
            <button onclick="window.location.href='vestidos.php'">VESTIDOS</button>
            <?php if (estaLogado()): ?>
                <button onclick="window.location.href='cadastrar_vestido.php'">ENVIAR VESTIDO</button>
                <button onclick="window.location.href='minha_conta.php'">MINHA CONTA</button>
                <button onclick="window.location.href='logout.php'">SAIR</button> 
            <?php else: ?>
                <button onclick="window.location.href='login.php'">ENTRAR</button>
            <?php endif; ?>
            <button onclick="window.location.href='ajuda.php'">AJUDA</button>

            <a href="carrinho.php" class="cart-icon">
                <img src="imagens/cesta.png" alt="Carrinho">
                <?php 
                $contagem_carrinho = 0;
                if (isset($_SESSION['carrinho'])) {
                    $contagem_carrinho = count($_SESSION['carrinho']);
                }
                if ($contagem_carrinho > 0) {
                    echo '<span class="cart-count">' . $contagem_carrinho . '</span>'; 
                }
                ?>
            </a>
        </div>
    </header>
    <main class="main-content">
