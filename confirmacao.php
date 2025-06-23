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

// Verificar se há informações de aluguel na sessão
if (!isset($_SESSION['aluguel_info'])) {
    // Redirecionar para o carrinho se não houver informações de aluguel
    header("Location: carrinho.php");
    exit;
}

// Obter informações do aluguel da sessão
$aluguel_info = $_SESSION['aluguel_info'];
$data_inicio = $aluguel_info['data_inicio'];
$data_fim = $aluguel_info['data_fim'];
$valor_total = $aluguel_info['valor_total'];

// Limpar informações de aluguel da sessão após uso
unset($_SESSION['aluguel_info']);

// Incluir cabeçalho
$titulo_pagina = "Confirmação de Aluguel";
include 'includes/header.php';
?>

<section style="max-width: 800px; margin: 30px auto; padding: 20px; text-align: center;">
    <div style="background-color: #d4edda; color: #155724; padding: 30px; border-radius: 10px; margin-bottom: 30px;">
        <h1 style="margin-top: 0;">Aluguel Confirmado!</h1>
        <p style="font-size: 1.2em;">Seu pedido foi processado com sucesso.</p>
        <i style="font-size: 3em; display: block; margin: 20px 0;">✓</i>
    </div>
    
    <div style="background-color: #f8f9fa; padding: 30px; border-radius: 10px; text-align: left;">
        <h2 style="margin-top: 0; text-align: center;">Detalhes do Aluguel</h2>
        
        <div style="margin: 20px 0; padding: 15px; border-bottom: 1px solid #ddd;">
            <p><strong>Período de Aluguel:</strong></p>
            <p>
                <?php 
                $data_inicio_obj = new DateTime($data_inicio);
                $data_fim_obj = new DateTime($data_fim);
                echo $data_inicio_obj->format('d/m/Y') . ' até ' . $data_fim_obj->format('d/m/Y'); 
                ?>
            </p>
        </div>
        
        <div style="margin: 20px 0; padding: 15px; border-bottom: 1px solid #ddd;">
            <p><strong>Valor Total:</strong></p>
            <p style="font-size: 1.2em;"><?php echo formatarPreco($valor_total); ?></p>
        </div>
        
        <div style="margin: 20px 0; padding: 15px;">
            <p><strong>Informações de Entrega:</strong></p>
            <?php
            // Buscar endereço do usuário
            $usuario_id = $_SESSION['usuario_id'];
            $conn = conectarDB();
            $endereco = null;
            
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
            
            if ($endereco):
            ?>
                <p>
                    <?php echo htmlspecialchars($endereco['rua']); ?>, 
                    <?php echo htmlspecialchars($endereco['numero']); ?>
                    <?php echo !empty($endereco['complemento']) ? ' - ' . htmlspecialchars($endereco['complemento']) : ''; ?>
                </p>
                <p>
                    <?php echo htmlspecialchars($endereco['bairro']); ?> - 
                    <?php echo htmlspecialchars($endereco['cidade']); ?>/<?php echo htmlspecialchars($endereco['estado']); ?>
                </p>
                <p>CEP: <?php echo htmlspecialchars($endereco['cep']); ?></p>
            <?php else: ?>
                <p>Endereço não encontrado.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <div style="margin-top: 30px;">
        <p>Um e-mail com os detalhes do seu aluguel foi enviado para <?php echo htmlspecialchars($_SESSION['usuario_email']); ?>.</p>
        <p>Você também pode acompanhar seus aluguéis na sua área de cliente.</p>
        
        <div style="margin-top: 30px;">
            <a href="minha_conta.php" style="background-color: black; color: white; padding: 12px 25px; text-decoration: none; border-radius: 20px; margin-right: 15px;">
                Minha Conta
            </a>
            <a href="index.php" style="background-color: white; color: black; border: 1px solid black; padding: 12px 25px; text-decoration: none; border-radius: 20px;">
                Voltar à Página Inicial
            </a>
        </div>
    </div>
</section>

<?php
// Incluir rodapé
include 'includes/footer.php';
?>
