<?php
// Incluir configurações
require_once 'includes/config.php';

// Se já estiver logado, redirecionar para a página inicial
if (estaLogado()) {
    header("Location: index.php");
    exit;
}

// Incluir cabeçalho
$titulo_pagina = "Login";
include 'includes/header.php';

$mensagem_erro = '';

// Processar formulário de login
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $email = limparDados($_POST['email']);
    $senha_form = $_POST['senha']; // Senha enviada pelo formulário

    // Validar campos
    if (empty($email) || empty($senha_form)) {
        $mensagem_erro = "Por favor, preencha todos os campos.";
    } else {
        $conn = conectarDB();
        try {
            // Verificar se o usuário existe (usando a tabela 'usuarios' e colunas do SQL)
            $sql = "SELECT cpusuario, nome, email, senha, cpf FROM public.usuarios WHERE email = :email";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($usuario) {
                // Verificar senha usando password_verify
                if (password_verify($senha_form, $usuario['senha'])) {
                    // Login bem-sucedido
                    $_SESSION['usuario_id'] = $usuario['cpusuario']; // Usar cpusuario como ID
                    $_SESSION['usuario_nome'] = $usuario['nome'];
                    $_SESSION['usuario_email'] = $usuario['email'];
                    
                    // Verificar se o perfil está incompleto (CPF vazio ou nome padrão)
                    if (empty($usuario['cpf']) || $usuario['nome'] == 'Usuário') {
                        $_SESSION['perfil_incompleto'] = true;
                        // Redirecionar para completar o perfil
                        header("Location: completar_perfil.php");
                        exit;
                    }

                    // Redirecionar para a página anterior ou para a página inicial
                    if (isset($_SESSION['redirect_after_login'])) {
                        $redirect = $_SESSION['redirect_after_login'];
                        unset($_SESSION['redirect_after_login']);
                        header("Location: $redirect");
                    } else {
                        header("Location: index.php");
                    }
                    exit;
                } else {
                    $mensagem_erro = "Senha incorreta.";
                }
            } else {
                $mensagem_erro = "E-mail não encontrado.";
            }
        } catch (PDOException $e) {
            $mensagem_erro = "Erro no login. Tente novamente mais tarde.";
        } finally {
            $conn = null; // Fechar conexão
        }
    }
}
?>

<section class="login-container">
    <div class="login-box">
        <h2>ENTRAR</h2>

        <?php if (!empty($mensagem_erro)): ?>
            <p style="color: red; text-align: center;"><?php echo $mensagem_erro; ?></p>
        <?php endif; ?>

        <form method="post" action="login.php">
            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            
            <label for="senha">Senha</label>
            <input type="password" id="senha" name="senha" required>
            
            <div class="buttons">
                <button type="button" class="cancel" onclick="window.location.href='index.php'">CANCELAR</button>
                <button type="submit" name="login" class="confirm">ENTRAR</button>
            </div>
        </form>
        <p style="text-align: center; margin-top: 15px;">Não tem uma conta? <a href="cadastro.php" style="color: black;">Cadastre-se</a></p>
    </div>
</section>

<?php
// Incluir rodapé
include 'includes/footer.php';
?>
