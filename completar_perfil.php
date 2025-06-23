<?php
// Incluir configurações
require_once 'includes/config.php';

// Verificar se o usuário está logado
verificarLogin();

// Verificar se o usuário já completou o perfil
if (!isset($_SESSION['perfil_incompleto']) || $_SESSION['perfil_incompleto'] !== true) {
    // Se o perfil já está completo, redirecionar para a página inicial
    header("Location: index.php");
    exit;
}

// Incluir cabeçalho
$titulo_pagina = "Completar Perfil";
include 'includes/header.php';

$mensagem_erro = '';
$mensagem_sucesso = '';

// Processar formulário quando enviado
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['completar_perfil'])) {
    $nome = limparDados($_POST['nome']);
    $cpf = limparDados($_POST['cpf']);
    $dtanascimento = limparDados($_POST['dtanascimento']);
    $telefone = limparDados($_POST['telefone'] ?? '');
    
    // Validar campos obrigatórios
    if (empty($nome) || empty($cpf) || empty($dtanascimento)) {
        $mensagem_erro = "Por favor, preencha todos os campos obrigatórios.";
    } elseif (strlen($cpf) < 11) { // Validação básica de CPF (apenas tamanho)
        $mensagem_erro = "CPF inválido.";
    } else {
        $conn = conectarDB();
        $usuario_id = $_SESSION['usuario_id'];
        
        try {
            // Verificar se o CPF já está cadastrado para outro usuário
            $sql_check = "SELECT cpusuario FROM public.usuarios WHERE cpf = :cpf AND cpusuario != :usuario_id";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bindParam(':cpf', $cpf, PDO::PARAM_STR);
            $stmt_check->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
            $stmt_check->execute();
            
            if ($stmt_check->fetch()) {
                $mensagem_erro = "Este CPF já está cadastrado para outro usuário.";
            } else {
                // Atualizar dados do usuário
                $sql_update = "UPDATE public.usuarios 
                               SET nome = :nome, cpf = :cpf, dtanascimento = :dtanascimento, telefone = :telefone
                               WHERE cpusuario = :usuario_id";
                $stmt_update = $conn->prepare($sql_update);
                
                $stmt_update->bindParam(':nome', $nome, PDO::PARAM_STR);
                $stmt_update->bindParam(':cpf', $cpf, PDO::PARAM_STR);
                $stmt_update->bindParam(':dtanascimento', $dtanascimento, PDO::PARAM_STR);
                $stmt_update->bindParam(':telefone', $telefone, PDO::PARAM_STR);
                $stmt_update->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
                
                if ($stmt_update->execute()) {
                    // Atualizar dados da sessão
                    $_SESSION['usuario_nome'] = $nome;
                    // Remover flag de perfil incompleto
                    unset($_SESSION['perfil_incompleto']);
                    
                    $mensagem_sucesso = "Perfil completado com sucesso!";
                    // Redirecionar para a página inicial após alguns segundos
                    echo '<script>setTimeout(function() { window.location.href = "minha_conta.php"; }, 2000);</script>';
                } else {
                    $mensagem_erro = "Erro ao atualizar o perfil. Tente novamente.";
                }
            }
        } catch (PDOException $e) {
            $mensagem_erro = "Erro ao processar os dados. Tente novamente mais tarde.";
            error_log("Erro ao completar perfil: " . $e->getMessage());
        } finally {
            $conn = null;
        }
    }
}
?>

<section class="completar-perfil-container" style="max-width: 800px; margin: 30px auto; padding: 20px;">
    <div class="completar-perfil-box" style="background-color: black; color: white; padding: 30px; border-radius: 10px;">
        <h2 style="text-align: center; margin-bottom: 20px;">COMPLETE SEU PERFIL</h2>
        <p style="text-align: center; margin-bottom: 30px;">Para continuar usando o MeuVestido.com, precisamos de algumas informações adicionais.</p>
        
        <?php if (!empty($mensagem_erro)): ?>
            <div style="background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                <?php echo $mensagem_erro; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($mensagem_sucesso)): ?>
            <div style="background-color: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                <?php echo $mensagem_sucesso; ?>
            </div>
        <?php endif; ?>
        
        <form method="post" action="completar_perfil.php">
            <div style="margin-bottom: 15px;">
                <label for="nome" style="display: block; margin-bottom: 5px;">Nome Completo *</label>
                <input type="text" id="nome" name="nome" required 
                       style="width: 100%; padding: 10px; border-radius: 5px; border: none;">
            </div>
            
            <div style="margin-bottom: 15px;">
                <label for="cpf" style="display: block; margin-bottom: 5px;">CPF *</label>
                <input type="text" id="cpf" name="cpf" required placeholder="Somente números" pattern="\d*" maxlength="14"
                       style="width: 100%; padding: 10px; border-radius: 5px; border: none;">
            </div>
            
            <div style="margin-bottom: 15px;">
                <label for="dtanascimento" style="display: block; margin-bottom: 5px;">Data de Nascimento *</label>
                <input type="date" id="dtanascimento" name="dtanascimento" required
                       style="width: 100%; padding: 10px; border-radius: 5px; border: none;">
            </div>
            
            <div style="margin-bottom: 25px;">
                <label for="telefone" style="display: block; margin-bottom: 5px;">Telefone</label>
                <input type="tel" id="telefone" name="telefone" placeholder="(XX) XXXXX-XXXX"
                       style="width: 100%; padding: 10px; border-radius: 5px; border: none;">
            </div>
            
            <div style="text-align: center;">
                <button type="submit" name="completar_perfil"
                        style="background-color: white; color: black; padding: 12px 30px; border: none; border-radius: 20px; cursor: pointer; font-weight: bold;">
                    SALVAR E CONTINUAR
                </button>
            </div>
        </form>
    </div>
</section>

<?php
// Incluir rodapé
include 'includes/footer.php';
?>
