<?php
// Incluir configurações
require_once 'includes/config.php';

// Definir título da página
$titulo_pagina = "Resultados da Busca";

// Obter parâmetros de busca
$nome_busca = isset($_GET['nome']) ? limparDados($_GET['nome']) : '';
$tamanho_busca = isset($_GET['tamanho']) ? limparDados($_GET['tamanho']) : '';

// Inicializar array de resultados
$vestidos = [];

// Realizar busca apenas se houver algum parâmetro
if (!empty($nome_busca) || !empty($tamanho_busca)) {
    $conn = conectarDB();
    
    try {
        // Construir consulta SQL base
        $sql = "SELECT v.*, i.caminho_imagem 
                FROM public.vestidos v 
                LEFT JOIN public.imagens i ON v.cpvestido = i.cevestido 
                WHERE v.disponivel = true";
        
        $params = [];
        
        // Adicionar filtro por nome se fornecido
        if (!empty($nome_busca)) {
            $sql .= " AND v.nome ILIKE :nome";
            $params[':nome'] = '%' . $nome_busca . '%';
        }
        
        // Adicionar filtro por tamanho se fornecido
        if (!empty($tamanho_busca)) {
            $sql .= " AND v.tamanho = :tamanho";
            $params[':tamanho'] = $tamanho_busca;
        }
        
        // Agrupar por ID do vestido para evitar duplicatas devido ao JOIN com imagens
        $sql .= " GROUP BY v.cpvestido, i.caminho_imagem ORDER BY v.nome";
        
        $stmt = $conn->prepare($sql);
        
        // Vincular parâmetros
        foreach ($params as $param => $value) {
            $stmt->bindValue($param, $value);
        }
        
        $stmt->execute();
        $vestidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Erro na busca: " . $e->getMessage());
    } finally {
        $conn = null;
    }
}

// Incluir cabeçalho
require_once 'includes/header.php';
?>

<div class="search-results">
    <h1>Resultados da Busca</h1>
    
    <div class="search-summary">
        <?php if (!empty($nome_busca) || !empty($tamanho_busca)): ?>
            <p>
                Você buscou por: 
                <?php if (!empty($nome_busca)): ?>
                    <strong>Nome:</strong> "<?php echo htmlspecialchars($nome_busca); ?>"
                <?php endif; ?>
                
                <?php if (!empty($tamanho_busca)): ?>
                    <?php if (!empty($nome_busca)): ?> e <?php endif; ?>
                    <strong>Tamanho:</strong> <?php echo htmlspecialchars($tamanho_busca); ?>
                <?php endif; ?>
            </p>
        <?php else: ?>
            <p>Por favor, informe um nome ou tamanho para buscar vestidos.</p>
        <?php endif; ?>
    </div>
    
    <?php if (count($vestidos) > 0): ?>
        <p class="results-count"><?php echo count($vestidos); ?> vestido(s) encontrado(s)</p>
        
        <div class="vestidos-grid">
            <?php foreach ($vestidos as $vestido): ?>
                <div class="vestido-card">
                    <a href="produto.php?id=<?php echo $vestido['cpvestido']; ?>">
                        <?php if (!empty($vestido['caminho_imagem'])): ?>
                            <img src="<?php echo htmlspecialchars($vestido['caminho_imagem']); ?>" alt="<?php echo htmlspecialchars($vestido['nome']); ?>">
                        <?php else: ?>
                            <div class="no-image">Sem imagem</div>
                        <?php endif; ?>
                        <h3><?php echo htmlspecialchars($vestido['nome']); ?></h3>
                        <p class="tamanho">Tamanho: <?php echo htmlspecialchars($vestido['tamanho']); ?></p>
                        <p class="preco">R$ <?php echo number_format($vestido['valoraluguel'], 2, ',', '.'); ?></p>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php elseif (!empty($nome_busca) || !empty($tamanho_busca)): ?>
        <div class="no-results">
            <p>Nenhum vestido encontrado com os critérios informados.</p>
            <p>Tente usar termos mais gerais ou verifique se o tamanho está disponível.</p>
        </div>
    <?php endif; ?>
</div>

<style>
    .search-results {
        padding: 20px;
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .search-results h1 {
        margin-bottom: 20px;
        text-align: center;
    }
    
    .search-summary {
        background-color: #f9f9f9;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
    }
    
    .results-count {
        margin-bottom: 20px;
        font-weight: bold;
    }
    
    .vestidos-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 20px;
    }
    
    .vestido-card {
        border: 1px solid #ddd;
        border-radius: 5px;
        overflow: hidden;
        transition: transform 0.3s ease;
    }
    
    .vestido-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .vestido-card a {
        text-decoration: none;
        color: inherit;
        display: block;
    }
    
    .vestido-card img, .vestido-card .no-image {
        width: 100%;
        height: 250px;
        object-fit: cover;
        display: block;
    }
    
    .vestido-card .no-image {
        background-color: #f0f0f0;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #999;
    }
    
    .vestido-card h3 {
        padding: 10px;
        margin: 0;
        font-size: 16px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .vestido-card .tamanho {
        padding: 0 10px;
        margin: 0;
        color: #666;
        font-size: 14px;
    }
    
    .vestido-card .preco {
        padding: 0 10px 10px;
        margin: 5px 0 0;
        font-weight: bold;
        font-size: 18px;
    }
    
    .no-results {
        text-align: center;
        padding: 40px 0;
        color: #666;
    }
    
    @media (max-width: 768px) {
        .vestidos-grid {
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        }
        
        .vestido-card img, .vestido-card .no-image {
            height: 180px;
        }
    }
</style>

<?php
// Incluir rodapé
require_once 'includes/footer.php';
?>
