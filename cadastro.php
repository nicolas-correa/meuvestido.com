<?php
// Incluir configurações
require_once 'includes/config.php';

// Se já estiver logado, redirecionar para a página inicial
if (estaLogado()) {
    header("Location: index.php");
    exit;
}

// Incluir cabeçalho
$titulo_pagina = "Cadastro";
include 'includes/header.php';

$mensagem_erro = '';
$mensagem_sucesso = '';

// Processar formulário de cadastro
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    $email = limparDados($_POST['email']);
    $senha = $_POST['senha'];
    $confirmarSenha = $_POST['confirmarSenha'];

    // Validar campos
    if (empty($email) || empty($senha) || empty($confirmarSenha)) {
        $mensagem_erro = "Por favor, preencha todos os campos.";
    } elseif ($senha != $confirmarSenha) {
        $mensagem_erro = "As senhas não coincidem.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensagem_erro = "Formato de e-mail inválido.";
    } else {
        $conn = conectarDB();
        try {
            // Verificar se o e-mail já está cadastrado
            $sql_check = "SELECT cpusuario FROM public.usuarios WHERE email = :email";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt_check->execute();

            if ($stmt_check->fetch()) {
                $mensagem_erro = "Este e-mail já está cadastrado.";
            } else {
                // Criptografar senha
                $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
                
                // Valores padrão para campos obrigatórios
                $nome_padrao = "Usuário"; // Nome temporário
                $cpf_padrao = ""; // Vazio, será preenchido depois
                $data_nascimento_padrao = date('Y-m-d'); // Data atual como placeholder
                
                // Inserir novo usuário na tabela 'usuarios' com dados mínimos
                $sql_insert = "INSERT INTO public.usuarios (nome, email, senha, cpf, dtanascimento) 
                               VALUES (:nome, :email, :senha, :cpf, :dtanascimento)";
                $stmt_insert = $conn->prepare($sql_insert);

                $stmt_insert->bindParam(':nome', $nome_padrao, PDO::PARAM_STR);
                $stmt_insert->bindParam(':email', $email, PDO::PARAM_STR);
                $stmt_insert->bindParam(':senha', $senhaHash, PDO::PARAM_STR);
                $stmt_insert->bindParam(':cpf', $cpf_padrao, PDO::PARAM_STR);
                $stmt_insert->bindParam(':dtanascimento', $data_nascimento_padrao, PDO::PARAM_STR);

                if ($stmt_insert->execute()) {
                    // Obter o ID do usuário recém-cadastrado
                    $usuario_id = $conn->lastInsertId();
                    
                    // Marcar o usuário como "perfil incompleto" na sessão
                    $_SESSION['perfil_incompleto'] = true;
                    
                    $mensagem_sucesso = "Cadastro realizado com sucesso! Faça login para completar seu perfil.";
                    // Limpar campos do POST para não repopular o form
                    $_POST = []; 
                    // Opcional: Redirecionar para login após um tempo
                    echo '<script>setTimeout(function() { window.location.href = "login.php"; }, 3000);</script>';
                } else {
                    $mensagem_erro = "Erro ao cadastrar. Tente novamente mais tarde.";
                }
            }
        } catch (PDOException $e) {
            $mensagem_erro = "Erro no cadastro. Tente novamente mais tarde.";
            error_log("Erro de cadastro (PDO): " . $e->getMessage());
        } finally {
            $conn = null; // Fechar conexão
        }
    }
}
?>

<section class="cadastro-container">
    <div class="cadastro-box">
        <h2>CRIAR CONTA</h2>

        <?php if (!empty($mensagem_erro)): ?>
            <p style="color: red; text-align: center;"><?php echo $mensagem_erro; ?></p>
        <?php endif; ?>
        <?php if (!empty($mensagem_sucesso)): ?>
            <p style="color: green; text-align: center;"><?php echo $mensagem_sucesso; ?></p>
        <?php endif; ?>

        <form method="post" action="cadastro.php">
            <label for="email">E-mail *</label>
            <input type="email" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">

            <label for="senha">Senha *</label>
            <input type="password" id="senha" name="senha" required>

            <label for="confirmarSenha">Confirmar Senha *</label>
            <input type="password" id="confirmarSenha" name="confirmarSenha" required>

            <div class="buttons">
                <button type="button" class="cancel" onclick="window.location.href='index.php'">CANCELAR</button>
                <button type="submit" name="register" class="confirm">CADASTRAR</button>
            </div>
        </form>
         <p style="text-align: center; margin-top: 15px;">Já tem uma conta? <a href="login.php" style="color: white;">Faça login</a></p>
    </div>
</section>

<?php
// Incluir rodapé
include 'includes/footer.php';
?>
