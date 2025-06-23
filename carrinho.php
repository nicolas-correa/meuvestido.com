<?php
// Incluir configurações
require_once 'includes/config.php';

// Verificar se o usuário está logado
verificarLogin();

// Verificar se o perfil está incompleto
if (isset($_SESSION['perfil_incompleto']) && $_SESSION['perfil_incompleto'] === true) {
    // Redirecionar para completar o perfil
    header("Location: completar_perfil.php");
    exit;
}

// Incluir cabeçalho
$titulo_pagina = "Carrinho";
include 'includes/header.php';

// Processar ações do carrinho (remover item)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['remover_item'])) {
    $item_id_remover = $_POST['item_id'];
    removerDoCarrinho($item_id_remover);
    // Recarregar a página para refletir a remoção
    header("Location: carrinho.php");
    exit;
}


?>

<section class="cart-container">
    <h2>MEU CARRINHO</h2>

    <?php
    // Verificar se há mensagens da página de produto
    $mensagem_sucesso = $_SESSION['mensagem_sucesso'] ?? '';
    $mensagem_erro = $_SESSION['mensagem_erro'] ?? '';
    unset($_SESSION['mensagem_sucesso'], $_SESSION['mensagem_erro']); // Limpar mensagens

    if (!empty($mensagem_sucesso)): ?>
        <p style="color: green; text-align: center;"><?php echo $mensagem_sucesso; ?></p>
    <?php endif; ?>
    <?php if (!empty($mensagem_erro)): ?>
        <p style="color: red; text-align: center;"><?php echo $mensagem_erro; ?></p>
    <?php endif; ?>

    <?php
    // Verificar se o carrinho está vazio
    if (!isset($_SESSION['carrinho']) || count($_SESSION['carrinho']) == 0) {
        echo '<div style="text-align: center; padding: 40px;">';
        echo '<p>Seu carrinho está vazio.</p>';
        echo '<a href="vestidos.php" class="btn btn-primary" style="background-color: black; color: white; padding: 10px 20px; text-decoration: none; border-radius: 20px; display: inline-block; margin-top: 20px;">Ver Vestidos</a>';
        echo '</div>';
    } else {
    ?>
        <form method="post" action="carrinho.php"> <!-- Formulário para botão de remover -->
            <div class="cart-table">
                <div class="cart-header">
                    <span>Produto</span>
                    <span>Preço</span>
                    <span>Tamanho</span>
                    <span>Quantidade</span>
                    <span>Total</span>
                    <span>Remover</span>
                </div>

                <?php 
                $subtotal = 0;
                foreach ($_SESSION['carrinho'] as $item_id => $item): 
                    // Validar se os dados essenciais existem no item da sessão
                    $preco_item = isset($item['preco']) ? (float)$item['preco'] : 0;
                    $quantidade_item = isset($item['quantidade']) ? (int)$item['quantidade'] : 0;
                    $item_total = $preco_item * $quantidade_item;
                    $subtotal += $item_total;
                ?>
                    <div class="cart-item">
                        <div class="cart-product">
                            <img src="<?php echo htmlspecialchars($item['imagem'] ?: 'images/placeholder.png'); ?>" alt="<?php echo htmlspecialchars($item['nome'] ?? 'Produto'); ?>">
                            <p><?php echo htmlspecialchars($item['nome'] ?? 'Nome Indisponível'); ?></p>
                        </div>
                        <span><?php echo formatarPreco($preco_item); ?></span>
                        <span><?php echo htmlspecialchars($item['tamanho'] ?: 'N/A'); ?></span>
                        <span><?php echo $quantidade_item; ?></span> 
                        <span><?php echo formatarPreco($item_total); ?></span>
                        <span>
                            <!-- Botão de remover dentro do formulário -->
                            <input type="hidden" name="item_id" value="<?php echo $item_id; ?>">
                            <button type="submit" name="remover_item" class="remove-button" style="background: none; border: none; color: red; cursor: pointer; font-size: 1.2em; padding: 0;">×</button>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </form>

        <div class="cart-summary">
            <p>Subtotal <span><?php echo formatarPreco($subtotal); ?></span></p>
            <p>Entrega <span>Grátis</span></p> 
            <p class="total">Total <span><?php echo formatarPreco($subtotal); ?></span></p>
            <button class="rent" onclick="window.location.href='checkout.php'">ALUGAR</button> 
            <a href="vestidos.php" style="display: block; text-align: center; margin-top: 10px; color: black; text-decoration: underline;">Continuar comprando</a>
        </div>
    <?php 
    } // Fim do else (carrinho não vazio)
    ?>
</section>

<?php
// Incluir rodapé
include 'includes/footer.php';
?>
