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

// Verificar se o carrinho está vazio
if (!isset($_SESSION['carrinho']) || count($_SESSION['carrinho']) == 0) {
    // Redirecionar para o carrinho com mensagem
    $_SESSION['mensagem_erro'] = "Seu carrinho está vazio. Adicione vestidos antes de prosseguir para o checkout.";
    header("Location: carrinho.php");
    exit;
}

// Incluir cabeçalho
$titulo_pagina = "Checkout";
include 'includes/header.php';

// Inicializar variáveis
$mensagem_erro = '';
$mensagem_sucesso = '';
$endereco = null;
$usuario_id = $_SESSION['usuario_id'];

// Buscar endereço do usuário
$conn = conectarDB();
try {
    $sql = "SELECT * FROM public.enderecos WHERE cemorador = :usuario_id LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
    $stmt->execute();
    $endereco = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar endereço: " . $e->getMessage());
} finally {
    $conn = null;
}

// Processar formulário de checkout
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['finalizar_aluguel'])) {
    // Verificar se tem endereço cadastrado
    if (!$endereco) {
        $mensagem_erro = "Você precisa cadastrar um endereço de entrega antes de finalizar o aluguel.";
    } else {
        // Obter dados do formulário
        $data_inicio = limparDados($_POST['data_inicio']);
        $data_fim = limparDados($_POST['data_fim']);
        
        // Validar datas
        $hoje = date('Y-m-d');
        if (empty($data_inicio) || empty($data_fim)) {
            $mensagem_erro = "Por favor, selecione as datas de início e fim do aluguel.";
        } elseif ($data_inicio < $hoje) {
            $mensagem_erro = "A data de início não pode ser anterior à data atual.";
        } elseif ($data_fim <= $data_inicio) {
            $mensagem_erro = "A data de fim deve ser posterior à data de início.";
        } else {
            $conn = conectarDB();
            try {
                // Iniciar transação
                $conn->beginTransaction();
                
                // Calcular valor total do carrinho
                $valor_total = calcularTotalCarrinho();
                
                // Flag para verificar se todos os vestidos estão disponíveis nas datas selecionadas
                $todos_disponiveis = true;
                $vestidos_indisponiveis = [];
                
                // Verificar disponibilidade de cada vestido nas datas selecionadas
                foreach ($_SESSION['carrinho'] as $item) {
                    $vestido_id = $item['produto_id'];
                    
                    // Verificar se o vestido já está alugado nas datas selecionadas
                    $sql_check = "SELECT a.cevestidoalugado 
                                 FROM public.aluguel a 
                                 WHERE a.cevestidoalugado = :vestido_id 
                                 AND (
                                     (a.datainicio <= :data_inicio AND a.datafim >= :data_inicio) OR
                                     (a.datainicio <= :data_fim AND a.datafim >= :data_fim) OR
                                     (a.datainicio >= :data_inicio AND a.datafim <= :data_fim)
                                 )";
                    $stmt_check = $conn->prepare($sql_check);
                    $stmt_check->bindParam(':vestido_id', $vestido_id, PDO::PARAM_INT);
                    $stmt_check->bindParam(':data_inicio', $data_inicio, PDO::PARAM_STR);
                    $stmt_check->bindParam(':data_fim', $data_fim, PDO::PARAM_STR);
                    $stmt_check->execute();
                    
                    if ($stmt_check->fetch()) {
                        // Vestido já está alugado nesse período
                        $todos_disponiveis = false;
                        $vestidos_indisponiveis[] = $item['nome'];
                    }
                }
                
                if (!$todos_disponiveis) {
                    // Alguns vestidos não estão disponíveis nas datas selecionadas
                    $mensagem_erro = "Os seguintes vestidos não estão disponíveis nas datas selecionadas: " . implode(", ", $vestidos_indisponiveis) . ". Por favor, escolha outras datas ou remova estes itens do carrinho.";
                    $conn->rollBack();
                } else {
                    // Todos os vestidos estão disponíveis, prosseguir com o aluguel
                    foreach ($_SESSION['carrinho'] as $item) {
                        $vestido_id = $item['produto_id'];
                        $valor_item = $item['preco'] * $item['quantidade'];
                        
                        // Inserir na tabela aluguel
                        $sql = "INSERT INTO public.aluguel (cecomprador, cevestidoalugado, datainicio, datafim, valortotal) 
                                VALUES (:usuario_id, :vestido_id, :data_inicio, :data_fim, :valor_item)";
                        $stmt = $conn->prepare($sql);
                        $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
                        $stmt->bindParam(':vestido_id', $vestido_id, PDO::PARAM_INT);
                        $stmt->bindParam(':data_inicio', $data_inicio, PDO::PARAM_STR);
                        $stmt->bindParam(':data_fim', $data_fim, PDO::PARAM_STR);
                        $stmt->bindParam(':valor_item', $valor_item, PDO::PARAM_STR);
                        $stmt->execute();
                        
                        // Não atualizar mais a disponibilidade do vestido para false
                        // Os vestidos permanecem disponíveis para outras datas
                    }
                    
                    // Confirmar transação
                    $conn->commit();
                    
                    // Limpar carrinho
                    $_SESSION['carrinho'] = [];
                    
                    // Salvar dados do aluguel na sessão para a página de confirmação
                    $_SESSION['aluguel_info'] = [
                        'data_inicio' => $data_inicio,
                        'data_fim' => $data_fim,
                        'valor_total' => $valor_total
                    ];
                    
                    // Redirecionar para página de confirmação
                    header("Location: confirmacao.php");
                    exit;
                }
            } catch (PDOException $e) {
                // Reverter transação em caso de erro
                $conn->rollBack();
                $mensagem_erro = "Erro ao processar o aluguel. Por favor, tente novamente mais tarde.";
                error_log("Erro no checkout: " . $e->getMessage());
            } finally {
                $conn = null;
            }
        }
    }
}

// Calcular subtotal do carrinho
$subtotal = calcularTotalCarrinho();
?>

<section style="max-width: 1000px; margin: 30px auto; padding: 20px;">
    <h1 style="text-align: center; margin-bottom: 30px;">Finalizar Aluguel</h1>
    
    <?php if (!empty($mensagem_erro)): ?>
        <div style="background-color: #f8d7da; color: #8a5a88; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
            <?php echo $mensagem_erro; ?>
        </div>
    <?php endif; ?>
    
    <div style="display: flex; flex-wrap: wrap; gap: 20px;">
        <!-- Resumo do pedido -->
        <div style="flex: 1; min-width: 300px; background-color: #f8f9fa; padding: 20px; border-radius: 10px;">
            <h2 style="margin-top: 0;">Resumo do Pedido</h2>
            
            <?php if (isset($_SESSION['carrinho']) && count($_SESSION['carrinho']) > 0): ?>
                <div style="margin-bottom: 20px;">
                    <?php foreach ($_SESSION['carrinho'] as $item): ?>
                        <div style="display: flex; margin-bottom: 15px; border-bottom: 1px solid #ddd; padding-bottom: 15px;">
                            <div style="width: 80px; height: 80px; overflow: hidden; margin-right: 15px;">
                                <img src="<?php echo htmlspecialchars($item['imagem'] ?: 'images/placeholder.png'); ?>" 
                                     alt="<?php echo htmlspecialchars($item['nome']); ?>"
                                     style="width: 100%; height: 100%; object-fit: cover;">
                            </div>
                            <div style="flex-grow: 1;">
                                <p style="margin: 0 0 5px; font-weight: bold;"><?php echo htmlspecialchars($item['nome']); ?></p>
                                <p style="margin: 0 0 5px;">Tamanho: <?php echo htmlspecialchars($item['tamanho'] ?: 'N/A'); ?></p>
                                <p style="margin: 0;">
                                    <?php echo formatarPreco($item['preco']); ?> x <?php echo $item['quantidade']; ?> = 
                                    <?php echo formatarPreco($item['preco'] * $item['quantidade']); ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div style="border-top: 2px solid #ddd; padding-top: 15px;">
                    <p style="display: flex; justify-content: space-between; margin: 5px 0;">
                        <span>Subtotal:</span>
                        <span><?php echo formatarPreco($subtotal); ?></span>
                    </p>
                    <p style="display: flex; justify-content: space-between; margin: 5px 0;">
                        <span>Entrega:</span>
                        <span>Grátis</span>
                    </p>
                    <p style="display: flex; justify-content: space-between; font-weight: bold; font-size: 1.2em; margin: 10px 0;">
                        <span>Total:</span>
                        <span><?php echo formatarPreco($subtotal); ?></span>
                    </p>
                </div>
            <?php else: ?>
                <p>Seu carrinho está vazio.</p>
            <?php endif; ?>
        </div>
        
        <!-- Formulário de checkout -->
        <div style="flex: 1; min-width: 300px; background-color: #8a5a88; color: white; padding: 20px; border-radius: 10px;">
            <h2 style="margin-top: 0;">Informações do Aluguel</h2>
            
            <form method="post" action="checkout.php">
                <!-- Endereço de entrega -->
                <div style="margin-bottom: 20px;">
                    <h3>Endereço de Entrega</h3>
                    
                    <?php if ($endereco): ?>
                        <div style="background-color: rgba(255,255,255,0.1); padding: 15px; border-radius: 5px; margin-bottom: 15px;">
                            <p style="margin: 0 0 5px;">
                                <strong><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></strong>
                            </p>
                            <p style="margin: 0 0 5px;">
                                <?php echo htmlspecialchars($endereco['rua']); ?>, 
                                <?php echo htmlspecialchars($endereco['numero']); ?>
                                <?php echo !empty($endereco['complemento']) ? ' - ' . htmlspecialchars($endereco['complemento']) : ''; ?>
                            </p>
                            <p style="margin: 0 0 5px;">
                                <?php echo htmlspecialchars($endereco['bairro']); ?> - 
                                <?php echo htmlspecialchars($endereco['cidade']); ?>/<?php echo htmlspecialchars($endereco['estado']); ?>
                            </p>
                            <p style="margin: 0;">
                                CEP: <?php echo htmlspecialchars($endereco['cep']); ?>
                            </p>
                        </div>
                        <a href="cadastro_endereco.php" style="color: white; text-decoration: underline;">Alterar endereço</a>
                    <?php else: ?>
                        <p>Você ainda não possui um endereço cadastrado.</p>
                        <a href="cadastro_endereco.php" class="btn" style="display: inline-block; background-color: white; color: #721c24; padding: 10px 15px; text-decoration: none; border-radius: 20px; margin-top: 10px;">
                            Cadastrar Endereço
                        </a>
                    <?php endif; ?>
                </div>
                
                <!-- Período do aluguel -->
                <div style="margin-bottom: 20px;">
                    <h3>Período do Aluguel</h3>
                    
                    <div style="margin-bottom: 15px;">
                        <label for="data_inicio" style="display: block; margin-bottom: 5px;">Data de Início *</label>
                        <input type="date" id="data_inicio" name="data_inicio" required 
                               min="<?php echo date('Y-m-d'); ?>"
                               style="width: 100%; padding: 10px; border-radius: 5px; border: none;">
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <label for="data_fim" style="display: block; margin-bottom: 5px;">Data de Devolução *</label>
                        <input type="date" id="data_fim" name="data_fim" required 
                               min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                               style="width: 100%; padding: 10px; border-radius: 5px; border: none;">
                    </div>
                </div>
                
                <!-- Método de pagamento (simulado) -->
                <div style="margin-bottom: 30px;">
                    <h3>Método de Pagamento</h3>
                    
                    <div style="margin-bottom: 10px;">
                        <input type="radio" id="cartao" name="pagamento" value="cartao" checked>
                        <label for="cartao">Cartão de Crédito</label>
                    </div>
                    
                    <div style="margin-bottom: 10px;">
                        <input type="radio" id="boleto" name="pagamento" value="boleto">
                        <label for="boleto">Boleto Bancário</label>
                    </div>
                    
                    <div style="margin-bottom: 10px;">
                        <input type="radio" id="pix" name="pagamento" value="pix">
                        <label for="pix">PIX</label>
                    </div>
                    
                    <p style="font-size: 0.9em; margin-top: 15px;">
                        * Esta é uma simulação. Nenhum pagamento real será processado.
                    </p>
                </div>
                
                <!-- Botões de ação -->
                <div style="display: flex; justify-content: space-between;">
                    <a href="carrinho.php" style="background-color: transparent; color: white; border: 1px solid white; padding: 12px 20px; text-decoration: none; border-radius: 20px;">
                        Voltar ao Carrinho
                    </a>
                    <button type="submit" name="finalizar_aluguel" style="background-color: white; color: #8a5a88; padding: 12px 20px; border: none; border-radius: 20px; cursor: pointer; font-weight: bold;">
                        Finalizar Aluguel
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>

<?php
// Incluir rodapé
include 'includes/footer.php';
?>
