<?php
// Incluir configurações
require_once 'includes/config.php';

// Verificar se o usuário está logado (redireciona se não estiver)
verificarLogin();

// Incluir cabeçalho
$titulo_pagina = "Cadastro de Endereço";
include 'includes/header.php';

// Inicializar variáveis
$mensagem_sucesso = '';
$mensagem_erro = '';
$endereco = null;

// Buscar endereço atual do usuário (se existir)
$usuario_id = $_SESSION['usuario_id'];
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

// Processar formulário quando enviado
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['salvar_endereco'])) {
    // Capturar dados do formulário
    $rua = limparDados($_POST['rua']);
    $numero = (int)$_POST['numero'];
    $bairro = limparDados($_POST['bairro']);
    $cidade = limparDados($_POST['cidade']);
    $estado = limparDados($_POST['estado']);
    $cep = limparDados($_POST['cep']);
    $complemento = limparDados($_POST['complemento'] ?? '');
    
    // Validar campos obrigatórios
    if (empty($rua) || empty($bairro) || empty($cidade) || empty($estado) || empty($cep) || $numero <= 0) {
        $mensagem_erro = "Por favor, preencha todos os campos obrigatórios corretamente.";
    } else {
        $conn = conectarDB();
        try {
            // Verificar se já existe um endereço para atualizar ou inserir novo
            if ($endereco) {
                // Atualizar endereço existente
                $sql = "UPDATE public.enderecos 
                        SET rua = :rua, numero = :numero, bairro = :bairro, 
                            cidade = :cidade, estado = :estado, cep = :cep, complemento = :complemento 
                        WHERE cpendereco = :endereco_id";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':endereco_id', $endereco['cpendereco'], PDO::PARAM_INT);
            } else {
                // Inserir novo endereço
                $sql = "INSERT INTO public.enderecos 
                        (cemorador, rua, numero, bairro, cidade, estado, cep, complemento) 
                        VALUES 
                        (:usuario_id, :rua, :numero, :bairro, :cidade, :estado, :cep, :complemento)";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
            }
            
            // Parâmetros comuns para ambas as operações
            $stmt->bindParam(':rua', $rua, PDO::PARAM_STR);
            $stmt->bindParam(':numero', $numero, PDO::PARAM_INT);
            $stmt->bindParam(':bairro', $bairro, PDO::PARAM_STR);
            $stmt->bindParam(':cidade', $cidade, PDO::PARAM_STR);
            $stmt->bindParam(':estado', $estado, PDO::PARAM_STR);
            $stmt->bindParam(':cep', $cep, PDO::PARAM_STR);
            $stmt->bindParam(':complemento', $complemento, PDO::PARAM_STR);
            
            if ($stmt->execute()) {
                $mensagem_sucesso = "Endereço " . ($endereco ? "atualizado" : "cadastrado") . " com sucesso!";
                
                // Atualizar dados do endereço na variável para exibir no formulário
                if (!$endereco) {
                    // Se foi uma inserção, buscar o novo registro
                    $sql_select = "SELECT * FROM public.enderecos WHERE cemorador = :usuario_id LIMIT 1";
                    $stmt_select = $conn->prepare($sql_select);
                    $stmt_select->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
                    $stmt_select->execute();
                    $endereco = $stmt_select->fetch(PDO::FETCH_ASSOC);
                } else {
                    // Se foi uma atualização, atualizar a variável local
                    $endereco['rua'] = $rua;
                    $endereco['numero'] = $numero;
                    $endereco['bairro'] = $bairro;
                    $endereco['cidade'] = $cidade;
                    $endereco['estado'] = $estado;
                    $endereco['cep'] = $cep;
                    $endereco['complemento'] = $complemento;
                }
            } else {
                $mensagem_erro = "Erro ao salvar o endereço. Por favor, tente novamente.";
            }
        } catch (PDOException $e) {
            $mensagem_erro = "Erro ao processar o endereço. Por favor, tente novamente mais tarde.";
            error_log("Erro ao salvar endereço: " . $e->getMessage());
        } finally {
            $conn = null;
        }
    }
}
?>

<section style="max-width: 800px; margin: 30px auto; padding: 20px;">
    <h1 style="text-align: center; margin-bottom: 30px;">Cadastro de Endereço</h1>
    
    <?php if (!empty($mensagem_sucesso)): ?>
        <div style="background-color: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
            <?php echo $mensagem_sucesso; ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($mensagem_erro)): ?>
        <div style="background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
            <?php echo $mensagem_erro; ?>
        </div>
    <?php endif; ?>
    
    <div style="background-color: #8a5a88; color: white; padding: 30px; border-radius: 10px;">
        <form method="post" action="cadastro_endereco.php">
            <div style="margin-bottom: 15px;">
                <label for="rua" style="display: block; margin-bottom: 5px;">Rua/Logradouro *</label>
                <input type="text" id="rua" name="rua" required 
                       value="<?php echo $endereco ? htmlspecialchars($endereco['rua']) : ''; ?>"
                       style="width: 100%; padding: 10px; border-radius: 5px; border: none;">
            </div>
            
            <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                <div style="flex: 1;">
                    <label for="numero" style="display: block; margin-bottom: 5px;">Número *</label>
                    <input type="number" id="numero" name="numero" required min="1"
                           value="<?php echo $endereco ? htmlspecialchars($endereco['numero']) : ''; ?>"
                           style="width: 100%; padding: 10px; border-radius: 5px; border: none;">
                </div>
                
                <div style="flex: 3;">
                    <label for="complemento" style="display: block; margin-bottom: 5px;">Complemento</label>
                    <input type="text" id="complemento" name="complemento"
                           value="<?php echo $endereco && isset($endereco['complemento']) ? htmlspecialchars($endereco['complemento']) : ''; ?>"
                           style="width: 100%; padding: 10px; border-radius: 5px; border: none;">
                </div>
            </div>
            
            <div style="margin-bottom: 15px;">
                <label for="bairro" style="display: block; margin-bottom: 5px;">Bairro *</label>
                <input type="text" id="bairro" name="bairro" required
                       value="<?php echo $endereco ? htmlspecialchars($endereco['bairro']) : ''; ?>"
                       style="width: 100%; padding: 10px; border-radius: 5px; border: none;">
            </div>
            
            <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                <div style="flex: 2;">
                    <label for="cidade" style="display: block; margin-bottom: 5px;">Cidade *</label>
                    <input type="text" id="cidade" name="cidade" required
                           value="<?php echo $endereco ? htmlspecialchars($endereco['cidade']) : ''; ?>"
                           style="width: 100%; padding: 10px; border-radius: 5px; border: none;">
                </div>
                
                <div style="flex: 1;">
                    <label for="estado" style="display: block; margin-bottom: 5px;">Estado *</label>
                    <select id="estado" name="estado" required
                            style="width: 100%; padding: 10px; border-radius: 5px; border: none;">
                        <option value="">Selecione...</option>
                        <?php
                        $estados = [
                            'AC' => 'Acre', 'AL' => 'Alagoas', 'AP' => 'Amapá', 'AM' => 'Amazonas', 'BA' => 'Bahia',
                            'CE' => 'Ceará', 'DF' => 'Distrito Federal', 'ES' => 'Espírito Santo', 'GO' => 'Goiás',
                            'MA' => 'Maranhão', 'MT' => 'Mato Grosso', 'MS' => 'Mato Grosso do Sul', 'MG' => 'Minas Gerais',
                            'PA' => 'Pará', 'PB' => 'Paraíba', 'PR' => 'Paraná', 'PE' => 'Pernambuco', 'PI' => 'Piauí',
                            'RJ' => 'Rio de Janeiro', 'RN' => 'Rio Grande do Norte', 'RS' => 'Rio Grande do Sul',
                            'RO' => 'Rondônia', 'RR' => 'Roraima', 'SC' => 'Santa Catarina', 'SP' => 'São Paulo',
                            'SE' => 'Sergipe', 'TO' => 'Tocantins'
                        ];
                        
                        $estado_atual = $endereco ? $endereco['estado'] : '';
                        
                        foreach ($estados as $sigla => $nome) {
                            $selected = ($estado_atual == $sigla) ? 'selected' : '';
                            echo "<option value=\"$sigla\" $selected>$nome</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
            
            <div style="margin-bottom: 25px;">
                <label for="cep" style="display: block; margin-bottom: 5px;">CEP *</label>
                <input type="text" id="cep" name="cep" required
                       value="<?php echo $endereco ? htmlspecialchars($endereco['cep']) : ''; ?>"
                       placeholder="Somente números" pattern="\d*" maxlength="8"
                       style="width: 100%; padding: 10px; border-radius: 5px; border: none;">
            </div>
            
            <div style="display: flex; justify-content: space-between;">
                <button type="button" onclick="window.location.href='index.php'" 
                        style="background-color: #dc3545; color: white; padding: 10px 20px; border: none; border-radius: 20px; cursor: pointer;">
                    Cancelar
                </button>
                <button type="submit" name="salvar_endereco"
                        style="background-color: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 20px; cursor: pointer;">
                    Salvar Endereço
                </button>
            </div>
        </form>
    </div>
</section>

<?php
// Incluir rodapé
include 'includes/footer.php';
?>
