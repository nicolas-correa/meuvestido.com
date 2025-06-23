<?php
// Incluir configurações
require_once 'includes/config.php';

// Incluir cabeçalho
$titulo_pagina = "Vestidos";
include 'includes/header.php';

// Processar filtros
$filtro_tamanho = isset($_GET['tamanho']) ? limparDados($_GET['tamanho']) : '';
$filtro_preco_min = isset($_GET['preco_min']) ? (float)$_GET['preco_min'] : 0;
$filtro_preco_max = isset($_GET['preco_max']) ? (float)$_GET['preco_max'] : 10000; // valor alto como padrão
$ordenacao = isset($_GET['ordenacao']) ? limparDados($_GET['ordenacao']) : 'nome_asc'; // padrão: ordem alfabética

// Buscar vestidos do banco de dados
$conn = conectarDB();
$vestidos = [];

try {
    // Construir a consulta SQL com filtros
    $sql = "SELECT 
                v.cpvestido, v.nome, v.descricao, v.tamanho, v.valoraluguel, v.disponivel,
                (SELECT i.caminho_imagem FROM public.imagens i WHERE i.cevestido = v.cpvestido LIMIT 1) as imagem_principal
            FROM public.vestidos v 
            WHERE v.disponivel = true";
    
    // Adicionar filtros se existirem
    if (!empty($filtro_tamanho)) {
        $sql .= " AND v.tamanho = :tamanho";
    }
    
    $sql .= " AND v.valoraluguel BETWEEN :preco_min AND :preco_max";
    
    // Adicionar ordenação
    switch ($ordenacao) {
        case 'preco_asc':
            $sql .= " ORDER BY v.valoraluguel ASC";
            break;
        case 'preco_desc':
            $sql .= " ORDER BY v.valoraluguel DESC";
            break;
        case 'nome_desc':
            $sql .= " ORDER BY v.nome DESC";
            break;
        case 'nome_asc':
        default:
            $sql .= " ORDER BY v.nome ASC";
            break;
    }
    
    $stmt = $conn->prepare($sql);
    
    // Vincular parâmetros
    if (!empty($filtro_tamanho)) {
        $stmt->bindParam(':tamanho', $filtro_tamanho, PDO::PARAM_STR);
    }
    $stmt->bindParam(':preco_min', $filtro_preco_min, PDO::PARAM_STR);
    $stmt->bindParam(':preco_max', $filtro_preco_max, PDO::PARAM_STR);
    
    $stmt->execute();
    $vestidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Erro ao buscar vestidos: " . $e->getMessage());
} finally {
    $conn = null; // Fechar conexão
}

// Buscar tamanhos disponíveis para o filtro
$tamanhos_disponiveis = [];
try {
    $conn = conectarDB();
    $sql_tamanhos = "SELECT DISTINCT tamanho FROM public.vestidos WHERE tamanho IS NOT NULL ORDER BY tamanho";
    $stmt_tamanhos = $conn->query($sql_tamanhos);
    while ($row = $stmt_tamanhos->fetch(PDO::FETCH_ASSOC)) {
        if (!empty($row['tamanho'])) {
            $tamanhos_disponiveis[] = $row['tamanho'];
        }
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar tamanhos: " . $e->getMessage());
} finally {
    $conn = null;
}

if (empty($tamanhos_disponiveis)) {
    $tamanhos_disponiveis = ['P', 'M', 'G', 'GG'];
}
?>

<section class="vestidos-container" style="padding: 20px;">
    <h1 style="text-align: center; margin-bottom: 30px;">Nossos Vestidos</h1>
    
    <!-- Filtros -->
    <div class="filtros" style="margin-bottom: 30px; padding: 15px; background-color: #f8f8f8; border-radius: 10px;">
        <h3 style="margin-top: 0;">Filtrar Vestidos</h3>
        <form method="get" action="vestidos.php" style="display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end;">
            <!-- Filtro por tamanho -->
            <div style="flex: 1; min-width: 150px;">
                <label for="tamanho" style="display: block; margin-bottom: 5px;">Tamanho:</label>
                <select name="tamanho" id="tamanho" style="width: 100%; padding: 8px; border-radius: 5px; border: 1px solid #ddd;">
                    <option value="">Todos</option>
                    <?php foreach ($tamanhos_disponiveis as $tamanho): ?>
                        <option value="<?php echo htmlspecialchars($tamanho); ?>" <?php echo ($filtro_tamanho === $tamanho) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($tamanho); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Filtro por preço -->
            <div style="flex: 1; min-width: 150px;">
                <label for="preco_min" style="display: block; margin-bottom: 5px;">Preço Mínimo:</label>
                <input type="number" name="preco_min" id="preco_min" min="0" step="50" value="<?php echo $filtro_preco_min; ?>" style="width: 100%; padding: 8px; border-radius: 5px; border: 1px solid #ddd;">
            </div>
            
            <div style="flex: 1; min-width: 150px;">
                <label for="preco_max" style="display: block; margin-bottom: 5px;">Preço Máximo:</label>
                <input type="number" name="preco_max" id="preco_max" min="0" step="50" value="<?php echo $filtro_preco_max; ?>" style="width: 100%; padding: 8px; border-radius: 5px; border: 1px solid #ddd;">
            </div>
            
            <!-- Ordenação -->
            <div style="flex: 1; min-width: 150px;">
                <label for="ordenacao" style="display: block; margin-bottom: 5px;">Ordenar por:</label>
                <select name="ordenacao" id="ordenacao" style="width: 100%; padding: 8px; border-radius: 5px; border: 1px solid #ddd;">
                    <option value="nome_asc" <?php echo ($ordenacao === 'nome_asc') ? 'selected' : ''; ?>>Nome (A-Z)</option>
                    <option value="nome_desc" <?php echo ($ordenacao === 'nome_desc') ? 'selected' : ''; ?>>Nome (Z-A)</option>
                    <option value="preco_asc" <?php echo ($ordenacao === 'preco_asc') ? 'selected' : ''; ?>>Preço (menor-maior)</option>
                    <option value="preco_desc" <?php echo ($ordenacao === 'preco_desc') ? 'selected' : ''; ?>>Preço (maior-menor)</option>
                </select>
            </div>
            
            <!-- Botão de filtrar -->
            <div style="flex: 0 0 auto;">
                <button type="submit" style="background-color: black; color: white; padding: 10px 20px; border: none; border-radius: 20px; cursor: pointer;">Filtrar</button>
            </div>
        </form>
    </div>
    
    <!-- Resultados -->
    <div class="vestidos-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px;">
        <?php if (empty($vestidos)): ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 50px 0;">
                <p>Nenhum vestido encontrado com os filtros selecionados.</p>
                <a href="vestidos.php" style="display: inline-block; margin-top: 15px; background-color: black; color: white; padding: 10px 20px; text-decoration: none; border-radius: 20px;">Ver todos os vestidos</a>
            </div>
        <?php else: ?>
            <?php foreach ($vestidos as $vestido): ?>
                <div class="vestido-card" style="border: 1px solid #eee; border-radius: 10px; overflow: hidden; transition: transform 0.3s; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                    <a href="produto.php?id=<?php echo $vestido['cpvestido']; ?>" style="text-decoration: none; color: inherit;">
                        <div style="position: relative; padding-top: 133%; /* 3:4 aspect ratio */">
                            <img src="<?php echo htmlspecialchars($vestido['imagem_principal'] ?: 'images/placeholder.png'); ?>" 
                                 alt="<?php echo htmlspecialchars($vestido['nome']); ?>"
                                 style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        <div style="padding: 15px;">
                            <h3 style="margin: 0 0 10px; font-size: 1.1rem;"><?php echo htmlspecialchars($vestido['nome']); ?></h3>
                            <p style="margin: 0 0 10px; color: #666; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                <?php echo htmlspecialchars(substr($vestido['descricao'], 0, 100) . (strlen($vestido['descricao']) > 100 ? '...' : '')); ?>
                            </p>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-weight: bold;"><?php echo formatarPreco($vestido['valoraluguel']); ?></span>
                                <span style="background-color: #f0f0f0; padding: 3px 8px; border-radius: 10px; font-size: 0.9rem;">
                                    <?php echo htmlspecialchars($vestido['tamanho'] ?: 'N/A'); ?>
                                </span>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<?php
// Incluir rodapé
include 'includes/footer.php';
?>
