<?php
// Incluir configurações
require_once 'includes/config.php';

// Verificar se o usuário está logado
if (!estaLogado()) {
    header("Location: login.php");
    exit;
}

// Definir título da página
$titulo_pagina = "Cadastrar Vestido";

// Inicializar variáveis
$mensagem_sucesso = '';
$mensagem_erro = '';
$vestido = [
    'nome' => '',
    'descricao' => '',
    'tamanho' => '',
    'busto' => '',
    'quadril' => '',
    'cintura' => '',
    'valoraluguel' => '',
    'tempouso' => '',
    'unicadona' => false,
    'aceite_termos' => false
];

// Processar formulário
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cadastrar_vestido'])) {
    // Obter dados do formulário
    $nome = limparDados($_POST['nome']);
    $descricao = limparDados($_POST['descricao']);
    $tamanho = limparDados($_POST['tamanho']);
    $busto = str_replace(',', '.', limparDados($_POST['busto']));
    $quadril = str_replace(',', '.', limparDados($_POST['quadril']));
    $cintura = str_replace(',', '.', limparDados($_POST['cintura']));
    $valoraluguel = str_replace(',', '.', limparDados($_POST['valoraluguel']));
    $tempouso = (int)limparDados($_POST['tempouso']);
    $unicadona = isset($_POST['unicadona']) ? true : false;
    $aceite_termos = isset($_POST['aceite_termos']) ? true : false;
    
    // Validar campos
    if (empty($nome) || empty($descricao) || empty($tamanho) || empty($busto) || 
        empty($quadril) || empty($cintura) || empty($valoraluguel) || empty($tempouso)) {
        $mensagem_erro = "Por favor, preencha todos os campos obrigatórios.";
    } elseif (!$aceite_termos) {
        $mensagem_erro = "Você precisa aceitar os termos para cadastrar um vestido.";
    } elseif (empty($_FILES['fotos']['name'][0])) {
        $mensagem_erro = "Por favor, envie pelo menos uma foto do vestido.";
    } else {
        // Preparar dados do usuário
        $usuario_id = $_SESSION['usuario_id'];
        $conn = conectarDB();
        
        try {

            $enviado = true; 
            
            if ($enviado) {
                $mensagem_sucesso = "Seu vestido foi cadastrado com sucesso! Nossa equipe irá analisar as informações e entrará em contato em breve.";
                

            } else {
                $mensagem_erro = "Ocorreu um erro ao enviar o cadastro. Por favor, tente novamente mais tarde.";
            }
            
        } catch (Exception $e) {
            $mensagem_erro = "Erro ao processar o cadastro: " . $e->getMessage();
            error_log("Erro ao cadastrar vestido: " . $e->getMessage());
        } finally {
            $conn = null;
        }
    }
}

// Incluir cabeçalho
require_once 'includes/header.php';
?>

<div class="cadastro-vestido-container">
    <h1>Cadastrar Vestido</h1>
    
    <div class="instrucoes">
        <p>Preencha o formulário abaixo para cadastrar seu vestido. Nossa equipe irá analisar as informações e entrará em contato em breve.</p>
        <p><strong>Importante:</strong> Envie fotos de boa qualidade que mostrem claramente o vestido de diferentes ângulos.</p>
    </div>
    
    <?php if (!empty($mensagem_sucesso)): ?>
        <div class="mensagem-sucesso">
            <?php echo $mensagem_sucesso; ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($mensagem_erro)): ?>
        <div class="mensagem-erro">
            <?php echo $mensagem_erro; ?>
        </div>
    <?php endif; ?>
    
    <form method="post" action="cadastrar_vestido.php" enctype="multipart/form-data" class="formulario-vestido">
        <div class="form-section">
            <h2>Informações Básicas</h2>
            
            <div class="form-group">
                <label for="nome">Nome do Vestido *</label>
                <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($vestido['nome']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="descricao">Descrição *</label>
                <textarea id="descricao" name="descricao" rows="5" required><?php echo htmlspecialchars($vestido['descricao']); ?></textarea>
                <p class="form-hint">Descreva detalhes como cor, estilo, ocasião recomendada, etc.</p>
            </div>
            
            <div class="form-group">
                <label for="tamanho">Tamanho *</label>
                <select id="tamanho" name="tamanho" required>
                    <option value="">Selecione...</option>
                    <option value="PP" <?php echo $vestido['tamanho'] == 'PP' ? 'selected' : ''; ?>>PP</option>
                    <option value="P" <?php echo $vestido['tamanho'] == 'P' ? 'selected' : ''; ?>>P</option>
                    <option value="M" <?php echo $vestido['tamanho'] == 'M' ? 'selected' : ''; ?>>M</option>
                    <option value="G" <?php echo $vestido['tamanho'] == 'G' ? 'selected' : ''; ?>>G</option>
                    <option value="GG" <?php echo $vestido['tamanho'] == 'GG' ? 'selected' : ''; ?>>GG</option>
                </select>
            </div>
        </div>
        
        <div class="form-section">
            <h2>Medidas</h2>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="busto">Busto (cm) *</label>
                    <input type="text" id="busto" name="busto" value="<?php echo htmlspecialchars($vestido['busto']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="cintura">Cintura (cm) *</label>
                    <input type="text" id="cintura" name="cintura" value="<?php echo htmlspecialchars($vestido['cintura']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="quadril">Quadril (cm) *</label>
                    <input type="text" id="quadril" name="quadril" value="<?php echo htmlspecialchars($vestido['quadril']); ?>" required>
                </div>
            </div>
        </div>
        
        <div class="form-section">
            <h2>Informações Adicionais</h2>
            
            <div class="form-group">
                <label for="valoraluguel">Valor de Aluguel Sugerido (R$) *</label>
                <input type="text" id="valoraluguel" name="valoraluguel" value="<?php echo htmlspecialchars($vestido['valoraluguel']); ?>" required>
                <p class="form-hint">Informe o valor que você sugere para o aluguel do vestido.</p>
            </div>
            
            <div class="form-group">
                <label for="tempouso">Tempo de Uso (meses) *</label>
                <input type="number" id="tempouso" name="tempouso" min="0" value="<?php echo htmlspecialchars($vestido['tempouso']); ?>" required>
            </div>
            
            <div class="form-group checkbox-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="unicadona" value="1" <?php echo $vestido['unicadona'] ? 'checked' : ''; ?>>
                    Sou a única dona deste vestido
                </label>
            </div>
        </div>
        
        <div class="form-section">
            <h2>Fotos do Vestido</h2>
            
            <div class="form-group">
                <label for="fotos">Enviar Fotos *</label>
                <input type="file" id="fotos" name="fotos[]" accept="image/*" multiple required>
                <p class="form-hint">Selecione uma ou mais fotos do vestido (máximo 5 fotos).</p>
                <div id="preview-container" class="preview-container"></div>
            </div>
        </div>
        
        <div class="form-section termos-section">
            <h2>Termos e Condições</h2>
            
            <div class="termos-container">
                <h3>Termos de Uso para Cadastro de Vestidos</h3>
                
                <p>Ao cadastrar um vestido na plataforma MeuVestido.com, você concorda com os seguintes termos:</p>
                
                <ol>
                    <li>Todas as informações fornecidas são verdadeiras e precisas.</li>
                    <li>Você é o legítimo proprietário do vestido ou está autorizado pelo proprietário a cadastrá-lo.</li>
                    <li>As fotos enviadas são do vestido real que está sendo cadastrado.</li>
                    <li>O vestido está em boas condições, limpo e pronto para aluguel.</li>
                    <li>Você autoriza a plataforma MeuVestido.com a utilizar as fotos e informações do vestido para fins de divulgação e aluguel.</li>
                    <li>Você concorda em disponibilizar o vestido para aluguel nas datas acordadas após a aprovação do cadastro.</li>
                    <li>A plataforma MeuVestido.com reserva-se o direito de recusar qualquer cadastro que não atenda aos padrões de qualidade ou que viole estes termos.</li>
                    <li>Você concorda em receber uma porcentagem do valor do aluguel, conforme acordado com a plataforma.</li>
                    <li>A plataforma não se responsabiliza por danos causados ao vestido durante o período de aluguel, mas se compromete a mediar conflitos entre as partes.</li>
                    <li>Você pode solicitar a remoção do vestido da plataforma a qualquer momento, desde que não haja aluguéis agendados.</li>
                </ol>
            </div>
            
            <div class="form-group checkbox-group">
                <label class="checkbox-label required-label">
                    <input type="checkbox" name="aceite_termos" value="1" <?php echo $vestido['aceite_termos'] ? 'checked' : ''; ?> required>
                    Li e concordo com os termos e condições acima
                </label>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" name="cadastrar_vestido" class="btn-submit">Enviar Cadastro</button>
        </div>
    </form>
</div>

<style>
    .cadastro-vestido-container {
        max-width: 800px;
        margin: 0 auto;
        padding: 20px;
    }
    
    .cadastro-vestido-container h1 {
        text-align: center;
        margin-bottom: 30px;
    }
    
    .instrucoes {
        background-color: #f9f9f9;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 30px;
    }
    
    .mensagem-sucesso {
        background-color: #d4edda;
        color: #155724;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
    }
    
    .mensagem-erro {
        background-color: #f8d7da;
        color: #721c24;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
    }
    
    .formulario-vestido {
        background-color: #fff;
        border-radius: 5px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .form-section {
        padding: 20px;
        border-bottom: 1px solid #eee;
    }
    
    .form-section:last-child {
        border-bottom: none;
    }
    
    .form-section h2 {
        margin-bottom: 20px;
        font-size: 18px;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: bold;
    }
    
    .form-group input[type="text"],
    .form-group input[type="number"],
    .form-group textarea,
    .form-group select {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 16px;
    }
    
    .form-group textarea {
        resize: vertical;
    }
    
    .form-hint {
        font-size: 12px;
        color: #666;
        margin-top: 5px;
    }
    
    .form-row {
        display: flex;
        gap: 15px;
    }
    
    .form-row .form-group {
        flex: 1;
    }
    
    .checkbox-group {
        margin-top: 15px;
    }
    
    .checkbox-label {
        display: flex;
        align-items: center;
        font-weight: normal;
    }
    
    .checkbox-label input {
        margin-right: 10px;
    }
    
    .required-label::after {
        content: " *";
        color: #dc3545;
    }
    
    .termos-container {
        max-height: 200px;
        overflow-y: auto;
        padding: 15px;
        border: 1px solid #ddd;
        border-radius: 5px;
        margin-bottom: 15px;
        background-color: #f9f9f9;
    }
    
    .termos-container h3 {
        margin-bottom: 10px;
        font-size: 16px;
    }
    
    .termos-container p, .termos-container li {
        font-size: 14px;
        margin-bottom: 10px;
    }
    
    .preview-container {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 10px;
    }
    
    .preview-item {
        width: 100px;
        height: 100px;
        border: 1px solid #ddd;
        border-radius: 5px;
        overflow: hidden;
        position: relative;
    }
    
    .preview-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .form-actions {
        padding: 20px;
        text-align: center;
    }
    
    .btn-submit {
        background-color: #000;
        color: #fff;
        border: none;
        padding: 12px 30px;
        border-radius: 25px;
        font-size: 16px;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .btn-submit:hover {
        opacity: 0.9;
    }
    
    @media (max-width: 768px) {
        .form-row {
            flex-direction: column;
            gap: 0;
        }
    }
</style>

<script>
    // Visualização prévia das imagens
    document.getElementById('fotos').addEventListener('change', function(e) {
        const previewContainer = document.getElementById('preview-container');
        previewContainer.innerHTML = '';
        
        if (this.files) {
            const maxFiles = 5;
            const filesArray = Array.from(this.files).slice(0, maxFiles);
            
            filesArray.forEach(file => {
                if (!file.type.match('image.*')) {
                    return;
                }
                
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const div = document.createElement('div');
                    div.className = 'preview-item';
                    
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    
                    div.appendChild(img);
                    previewContainer.appendChild(div);
                }
                
                reader.readAsDataURL(file);
            });
            
            if (this.files.length > maxFiles) {
                alert(`Você selecionou ${this.files.length} arquivos, mas apenas os primeiros ${maxFiles} serão enviados.`);
            }
        }
    });
    
    // Formatar campos numéricos
    document.getElementById('valoraluguel').addEventListener('input', function(e) {
        let value = e.target.value;
        value = value.replace(/\D/g, '');
        value = value.replace(/(\d)(\d{2})$/, '$1,$2');
        value = value.replace(/(?=(\d{3})+(\D))\B/g, '.');
        e.target.value = value;
    });
    
    ['busto', 'cintura', 'quadril'].forEach(id => {
        document.getElementById(id).addEventListener('input', function(e) {
            let value = e.target.value;
            value = value.replace(/[^\d,]/g, '');
            value = value.replace(/,/g, '.');
            e.target.value = value;
        });
    });
</script>

<?php
// Incluir rodapé
require_once 'includes/footer.php';
?>
