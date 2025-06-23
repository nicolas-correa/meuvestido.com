<?php
// Incluir configurações
require_once 'includes/config.php';

// Verificar se o usuário está logado
if (estaLogado()) {
    // Verificar se o perfil está incompleto
    if (isset($_SESSION['perfil_incompleto']) && $_SESSION['perfil_incompleto'] === true) {
        // Redirecionar para completar o perfil
        header("Location: completar_perfil.php");
        exit;
    }
    
    // Adicionar link para a página de minha conta no header
    $_SESSION['mostrar_minha_conta'] = true;
}

// Incluir cabeçalho
$titulo_pagina = "Início";
include 'includes/header.php';
?>

<section class="highlight">
    <img src="imagens/banner.png" alt="Casal de noivos">
    <div class="highlight-text">
        <h3>Descubra os modelos perfeitos para os dias mais quentes! Tecidos leves, cortes fluídos e detalhes em
            renda que unem conforto e sofisticação. Alugue seu vestido e arrase no seu grande dia com o frescor do
            verão</h3>
            <div class="header-buttons"><button onclick="window.location.href='vestidos.php'">seguir >></button></div>

    </div>

</section>


<section class="top-designers">
    <h2>TOP DESIGNERS</h2>
    <div class="designer-gallery">
        <?php
        // Obter produtos em destaque (vestidos disponíveis)
        $conn = conectarDB();
        $produtos_destaque = [];
        try {
            // Seleciona os 3 primeiros vestidos disponíveis e suas imagens principais
            $sql = "SELECT 
                        v.cpvestido, v.nome, 
                        (SELECT i.caminho_imagem FROM public.imagens i WHERE i.cevestido = v.cpvestido ORDER BY i.cpimagem LIMIT 1) as imagem_principal
                    FROM public.vestidos v 
                    WHERE v.disponivel = true
                    ORDER BY v.cpvestido 
                    LIMIT 3";
            
            $stmt = $conn->query($sql);
            $produtos_destaque = $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            // Logar erro em produção
            error_log("Erro ao buscar produtos em destaque: " . $e->getMessage());
        } finally {
            $conn = null; // Fechar conexão
        }

        if (!empty($produtos_destaque)) {
            $contador = 1;
            foreach($produtos_destaque as $produto) {
                echo '<div class="image-container">
                    <a href="produto.php?id=' . htmlspecialchars($produto['cpvestido']) . '">
                        <img src="' . htmlspecialchars($produto['imagem_principal'] ?: 'imagens/placeholder.png') . '" alt="' . htmlspecialchars($produto['nome']) . '">
                        <div class="text">' . htmlspecialchars($produto['nome']) . '</div> 
                    </a>
                </div>';
                $contador++;
            }
        } else {
            // Mensagem se não houver produtos ou erro
            echo '<p style="text-align: center; width: 100%;">Nenhum vestido em destaque encontrado no momento.</p>';
        }
        ?>
    </div>
</section>

<?php
// Incluir rodapé
include 'includes/footer.php';
?>
