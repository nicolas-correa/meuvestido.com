<?php
// Incluir configurações
require_once 'includes/config.php';

// Incluir cabeçalho
$titulo_pagina = "Minha Conta";
include 'includes/header.php';

// Verificar se o usuário está logado
if (!estaLogado()) {
    // Redirecionar para a página de login
    header("Location: login.php");
    exit;
}

// Obter informações do usuário
$usuario_id = $_SESSION['usuario_id'];
$usuario_nome = $_SESSION['usuario_nome'] ?? 'Usuário';
$usuario_email = $_SESSION['usuario_email'] ?? '';

// Verificar se o usuário tem endereço cadastrado
$tem_endereco = false;
$conn = conectarDB();
try {
    $sql = "SELECT cpendereco FROM public.enderecos WHERE cemorador = :usuario_id LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
    $stmt->execute();
    $tem_endereco = ($stmt->fetch() !== false);
} catch (PDOException $e) {
    error_log("Erro ao verificar endereço: " . $e->getMessage());
} finally {
    $conn = null;
}
?>

<section style="max-width: 800px; margin: 30px auto; padding: 20px;">
    <h1 style="text-align: center; margin-bottom: 30px;">Minha Conta</h1>
    
    <div style="background-color: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
        <h2 style="margin-top: 0;">Olá, <?php echo htmlspecialchars($usuario_nome); ?>!</h2>
        <p>Bem-vindo(a) à sua área de cliente. Aqui você pode gerenciar suas informações e acompanhar seus aluguéis.</p>
    </div>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
        <!-- Card de Informações Pessoais -->
        <div style="background-color: white; border-radius: 10px; padding: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
            <h3 style="margin-top: 0;">Informações Pessoais</h3>
            <p><strong>Nome:</strong> <?php echo htmlspecialchars($usuario_nome); ?></p>
            <p><strong>E-mail:</strong> <?php echo htmlspecialchars($usuario_email); ?></p>
            <!-- Botão para editar perfil-->
            <button onclick="alert('A função de Editar Perfil está em desenvolvimento.');" style="background-color: #8a5a88; color: white; padding: 8px 15px; border: none; border-radius: 20px; cursor: pointer; width: 100%; margin-top: 10px;">
                Editar Perfil
            </button>
        </div>
        
        <!-- Card de Endereço -->
        <div style="background-color: white; border-radius: 10px; padding: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
            <h3 style="margin-top: 0;">Endereço de Entrega</h3>
            <?php if ($tem_endereco): ?>
                <p>Você já possui um endereço cadastrado.</p>
                <button onclick="window.location.href='cadastro_endereco.php'" style="background-color: #8a5a88; color: white; padding: 8px 15px; border: none; border-radius: 20px; cursor: pointer; width: 100%; margin-top: 10px;">
                    Atualizar Endereço
                </button>
            <?php else: ?>
                <p>Você ainda não possui um endereço cadastrado.</p>
                <button onclick="window.location.href='cadastro_endereco.php'" style="background-color: #8a5a88; color: white; padding: 8px 15px; border: none; border-radius: 20px; cursor: pointer; width: 100%; margin-top: 10px;">
                    Cadastrar Endereço
                </button>
            <?php endif; ?>
        </div>
        
        <!-- Card de Aluguéis -->
        <div style="background-color: white; border-radius: 10px; padding: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
            <h3 style="margin-top: 0;">Meus Aluguéis</h3>
            <p>Visualize seus aluguéis ativos e histórico.</p>
            <button onclick="alert('A função de Ver Alugueis está em desenvolvimento.');" style="background-color: #8a5a88; color: white; padding: 8px 15px; border: none; border-radius: 20px; cursor: pointer; width: 100%; margin-top: 10px;">
                Ver Alugueis
            </button>
        </div>
        
        
    </div>
    
    <!-- Botão de Logout -->
    <div style="text-align: center; margin-top: 30px;">
        <a href="logout.php" style="background-color: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 20px; display: inline-block;">Sair da Conta</a>
    </div>
</section>

<?php
// Incluir rodapé
include 'includes/footer.php';
?>
