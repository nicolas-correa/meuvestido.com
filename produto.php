<?php
// Incluir configurações
require_once 'includes/config.php';

// Obter ID do produto da URL
$produto_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Buscar produto no banco de dados 
$produto = null;
$datas_indisponiveis = [];

if ($produto_id > 0) {
    $produto = obterProduto($produto_id); // Função já busca dados de vestidos e imagem principal
    $imagens_galeria = obterImagensProduto($produto_id); // Busca todas as imagens para a galeria

    // Buscar feedbacks/avaliações do produto
    $feedbacks = [];
    $conn = conectarDB();
    try {
        $sql = "SELECT f.cpfeedback, f.comentario, f.dtafeedback, u.nome as nome_usuario 
                FROM public.feedback f 
                JOIN public.usuarios u ON f.ceusuariofeedback = u.cpusuario 
                WHERE f.cevestidofeedback = :produto_id 
                ORDER BY f.dtafeedback DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':produto_id', $produto_id, PDO::PARAM_INT);
        $stmt->execute();
        $feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Buscar datas em que o vestido já está alugado
        $sql_datas = "SELECT datainicio, datafim FROM public.aluguel 
                      WHERE cevestidoalugado = :produto_id 
                      AND datafim >= CURRENT_DATE
                      ORDER BY datainicio";
        $stmt_datas = $conn->prepare($sql_datas);
        $stmt_datas->bindParam(':produto_id', $produto_id, PDO::PARAM_INT);
        $stmt_datas->execute();
        $datas_indisponiveis = $stmt_datas->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log("Erro ao buscar dados do produto: " . $e->getMessage());
    } finally {
        $conn = null;
    }
}

$mensagem_sucesso = $_SESSION['mensagem_sucesso'] ?? '';
$mensagem_erro = $_SESSION['mensagem_erro'] ?? '';
unset($_SESSION['mensagem_sucesso'], $_SESSION['mensagem_erro']); // Limpar mensagens após exibir

// Processar adição ao carrinho
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_to_cart'])) {
    if (!estaLogado()) {
        // Se não estiver logado, redirecionar para login com mensagem
        $_SESSION['mensagem_erro'] = "Você precisa estar logado para adicionar itens ao carrinho.";
        // Salvar URL atual para redirecionamento após login
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header("Location: login.php");
        exit;
    } elseif (isset($_SESSION['perfil_incompleto']) && $_SESSION['perfil_incompleto'] === true) {
        // Se o perfil estiver incompleto, redirecionar para completar
        $_SESSION['mensagem_erro'] = "Por favor, complete seu perfil antes de adicionar itens ao carrinho.";
        header("Location: completar_perfil.php");
        exit;
    } elseif ($produto) {
        // Adicionar ao carrinho (usando sessão)
        $tamanho_selecionado = isset($_POST['tamanho']) ? limparDados($_POST['tamanho']) : $produto['tamanho']; // Usa o tamanho do BD como padrão se não for selecionado

        adicionarAoCarrinho(
            $produto['cpvestido'],
            $produto['nome'],
            $produto['valoraluguel'],
            $produto['imagem_principal'] ?: 'images/placeholder.png', // Usa placeholder se não houver imagem
            1, // Quantidade inicial
            $tamanho_selecionado
        );
        $_SESSION['mensagem_sucesso'] = "Vestido adicionado ao carrinho!";
        // Redirecionar para o carrinho para ver o item adicionado
        header("Location: carrinho.php");
        exit;
    } else {
        // Caso o produto não seja encontrado ao tentar adicionar
        $_SESSION['mensagem_erro'] = "Erro ao adicionar o produto ao carrinho.";
        header("Location: produto.php?id=" . $produto_id); // Recarrega a página
        exit;
    }
}

// Processar envio de feedback
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['enviar_feedback'])) {
    if (!estaLogado()) {
        // Se não estiver logado, redirecionar para login com mensagem
        $_SESSION['mensagem_erro'] = "Você precisa estar logado para enviar avaliações.";
        // Salvar URL atual para redirecionamento após login
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header("Location: login.php");
        exit;
    } elseif (isset($_SESSION['perfil_incompleto']) && $_SESSION['perfil_incompleto'] === true) {
        // Se o perfil estiver incompleto, redirecionar para completar
        $_SESSION['mensagem_erro'] = "Por favor, complete seu perfil antes de enviar avaliações.";
        header("Location: completar_perfil.php");
        exit;
    } else {
        $comentario = limparDados($_POST['comentario']);
        $usuario_id = $_SESSION['usuario_id'];

        if (empty($comentario)) {
            $_SESSION['mensagem_erro'] = "Por favor, escreva um comentário antes de enviar.";
            header("Location: produto.php?id=" . $produto_id);
            exit;
        }

        $conn = conectarDB();
        try {
            $data_atual = date('Y-m-d');
            $sql = "INSERT INTO public.feedback (ceusuariofeedback, cevestidofeedback, comentario, dtafeedback) 
                    VALUES (:usuario_id, :produto_id, :comentario, :data)";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
            $stmt->bindParam(':produto_id', $produto_id, PDO::PARAM_INT);
            $stmt->bindParam(':comentario', $comentario, PDO::PARAM_STR);
            $stmt->bindParam(':data', $data_atual, PDO::PARAM_STR);

            if ($stmt->execute()) {
                $_SESSION['mensagem_sucesso'] = "Avaliação enviada com sucesso!";
            } else {
                $_SESSION['mensagem_erro'] = "Erro ao enviar avaliação. Tente novamente.";
            }
        } catch (PDOException $e) {
            $_SESSION['mensagem_erro'] = "Erro ao processar avaliação. Tente novamente mais tarde.";
            error_log("Erro ao enviar feedback: " . $e->getMessage());
        } finally {
            $conn = null;
        }

        header("Location: produto.php?id=" . $produto_id);
        exit;
    }
}

// Incluir cabeçalho
$titulo_pagina = $produto ? $produto['nome'] : "Produto não encontrado";
include 'includes/header.php';
?>

<section class="product-container">
    <?php if ($produto): ?>
        <div class="product-content">
            <div class="product-gallery">
                <div class="thumbnail-list">
                    <?php if (!empty($imagens_galeria)): ?>
                        <?php foreach ($imagens_galeria as $img): ?>
                            <img src="<?php echo htmlspecialchars($img['caminho_imagem']); ?>"
                                alt="Miniatura <?php echo htmlspecialchars($img['nome']); ?>"
                                onclick="document.getElementById('mainProductImage').src=this.src; document.getElementById('mainProductImage').alt=this.alt;"
                                style="width: 60px; height: auto; cursor: pointer; border: 1px solid #ccc; margin-bottom: 5px; border-radius: 5px;">
                        <?php endforeach; ?>
                    <?php elseif ($produto['imagem_principal']): ?>
                        <!-- Fallback se só tiver a imagem principal -->
                        <img src="<?php echo htmlspecialchars($produto['imagem_principal']); ?>"
                            alt="Miniatura <?php echo htmlspecialchars($produto['nome']); ?>"
                            style="width: 60px; height: auto; border: 1px solid #ccc; margin-bottom: 5px; border-radius: 5px;">
                    <?php endif; ?>
                </div>
                <div class="main-image">
                    <img id="mainProductImage"
                        src="<?php echo htmlspecialchars($produto['imagem_principal'] ?: 'images/placeholder.png'); ?>"
                        alt="<?php echo htmlspecialchars($produto['nome']); ?>">
                </div>
            </div>

            <div class="product-details">
                <h2><?php echo htmlspecialchars($produto['nome']); ?></h2>

                <?php if (!empty($mensagem_sucesso)): ?>
                    <p style="color: green;"><?php echo $mensagem_sucesso; ?></p>
                <?php endif; ?>
                <?php if (!empty($mensagem_erro)): ?>
                    <p style="color: red;"><?php echo $mensagem_erro; ?></p>
                <?php endif; ?>

                <p><?php echo nl2br(htmlspecialchars($produto['descricao'])); ?></p>

                <!-- Avaliações - Exibir média ou placeholder -->
                <p class="rating">
                    <?php
                    // Exibir número de avaliações
                    $num_avaliacoes = count($feedbacks);
                    echo "★★★★★ (" . $num_avaliacoes . " avaliação" . ($num_avaliacoes != 1 ? "ões" : "") . ")";
                    ?>
                </p>

                <p class="price"><?php echo formatarPreco($produto['valoraluguel']); ?>
                    <!-- Parcelamento pode ser calculado ou fixo -->
                    <span class="installments">(ou em até 3x de <?php echo formatarPreco($produto['valoraluguel'] / 5); ?>
                        sem juros)</span>
                </p>

                <!-- Exibir datas indisponíveis -->
                <?php if (!empty($datas_indisponiveis)): ?>
                    <div style="margin-bottom: 20px; padding: 10px; background-color: #f8f9fa; border-radius: 5px;">
                        <p style="font-weight: bold; margin-bottom: 5px;">Datas indisponíveis para aluguel:</p>
                        <ul style="margin: 0; padding-left: 20px;">
                            <?php foreach ($datas_indisponiveis as $periodo): ?>
                                <li>
                                    <?php
                                    $data_inicio = new DateTime($periodo['datainicio']);
                                    $data_fim = new DateTime($periodo['datafim']);
                                    echo $data_inicio->format('d/m/Y') . ' até ' . $data_fim->format('d/m/Y');
                                    ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post" action="produto.php?id=<?php echo $produto_id; ?>">
                    <div class="product-options">
                        <p>Tamanho:</p>
                        <!-- Exibe o tamanho do banco de dados como texto (não editável) -->
                        <div class="size-display"
                            style="margin-bottom: 10px; padding: 10px; width: 100%; background-color: lightgray; border: none;">
                            <?php
                            // Pega o tamanho diretamente do banco de dados
                            $tamanho = $produto['tamanho'] ?: 'Tamanho não especificado';
                            echo htmlspecialchars($tamanho);
                            ?>
                        </div>
                        <!-- Adiciona um campo hidden para enviar o tamanho no formulário -->
                        <input type="hidden" name="tamanho" value="<?php echo htmlspecialchars($tamanho); ?>">

                        <!-- Botão para abrir o modal da tabela de medidas -->
                        <button type="button" class="info-button" onclick="openMeasurementsModal()">Tabela de
                            Medidas</button>
                    </div>
                    <button type="submit" name="add_to_cart" class="add-to-cart">adicionar à sacola</button>
                </form>
            </div>
        </div>

        <!-- Modal da Tabela de Medidas -->
        <div id="measurementsModal" class="modal">
            <div class="modal-content">
                <span class="close-button" onclick="closeMeasurementsModal()">&times;</span>
                <h3>Tabela de Medidas</h3>

                <div class="measurements-tabs">
                    <button class="tab-button active" onclick="openTab(event, 'sizeGuide')">Guia de Tamanhos</button>
                    <button class="tab-button" onclick="openTab(event, 'fitCheck')">Verificar se Serve</button>
                </div>

                <div id="sizeGuide" class="tab-content" style="display: block;">
                    <div class="measurement-info">
                        <p><strong>Como medir:</strong></p>
                        <ul>
                            <li><strong>Busto:</strong> Meça a circunferência na parte mais cheia do busto.</li>
                            <li><strong>Cintura:</strong> Meça a circunferência da cintura natural (a parte mais estreita).
                            </li>
                            <li><strong>Quadril:</strong> Meça a circunferência na parte mais larga do quadril.</li>
                        </ul>
                    </div>

                    <div class="dress-measurements-display">
                        <h4>Medidas deste Vestido (Tamanho
                            <?php echo htmlspecialchars($produto['tamanho'] ?: 'Não especificado'); ?>):
                        </h4>
                        <ul>
                            <li><strong>Busto:</strong>
                                <?php echo isset($produto['busto']) && $produto['busto'] > 0 ? htmlspecialchars($produto['busto']) . ' cm' : 'Não informado'; ?>
                            </li>
                            <li><strong>Cintura:</strong>
                                <?php echo isset($produto['cintura']) && $produto['cintura'] > 0 ? htmlspecialchars($produto['cintura']) . ' cm' : 'Não informado'; ?>
                            </li>
                            <li><strong>Quadril:</strong>
                                <?php echo isset($produto['quadril']) && $produto['quadril'] > 0 ? htmlspecialchars($produto['quadril']) . ' cm' : 'Não informado'; ?>
                            </li>
                        </ul>
                        <p style="font-size: 0.9em; color: #666;">
                            *As medidas podem ter uma pequena variação.
                            <?php if (!isset($produto['busto']) || $produto['busto'] <= 0): ?>
                                <br>As medidas exatas para este vestido não estão disponíveis. A verificação de "Servirá" usará
                                medidas padrão para o tamanho "<?php echo htmlspecialchars($produto['tamanho']); ?>".
                            <?php endif; ?>
                        </p>
                    </div>
                </div>

                <div id="fitCheck" class="tab-content">
                    <p>Insira suas medidas para verificar se este vestido servirá em você:</p>

                    <div class="measurement-form">
                        <div class="form-group">
                            <label for="userBust">Seu Busto (cm):</label>
                            <input type="number" id="userBust" min="60" max="150" step="0.5" placeholder="Ex: 86">
                        </div>

                        <div class="form-group">
                            <label for="userWaist">Sua Cintura (cm):</label>
                            <input type="number" id="userWaist" min="50" max="130" step="0.5" placeholder="Ex: 66">
                        </div>

                        <div class="form-group">
                            <label for="userHip">Seu Quadril (cm):</label>
                            <input type="number" id="userHip" min="70" max="150" step="0.5" placeholder="Ex: 94">
                        </div>

                        <button type="button" onclick="checkFit()" class="check-fit-button">Verificar</button>
                    </div>

                    <div id="fitResult" class="fit-result" style="display: none;">
                        <h4>Resultado:</h4>
                        <div id="bustResult" class="result-item"></div>
                        <div id="waistResult" class="result-item"></div>
                        <div id="hipResult" class="result-item"></div>
                        <div id="overallResult" class="overall-result"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Seção de Avaliações/Feedbacks -->
        <div class="product-feedback"
            style="margin-top: 40px; padding: 20px; background-color: #f8f9fa; border-radius: 10px;">
            <h3 style="margin-top: 0;">Avaliações</h3>

            <!-- Formulário para enviar avaliação -->
            <div
                style="margin-bottom: 30px; background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                <h4 style="margin-top: 0;">Deixe sua avaliação</h4>
                <form method="post" action="produto.php?id=<?php echo $produto_id; ?>">
                    <textarea name="comentario" rows="4"
                        style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; margin-bottom: 10px;"
                        placeholder="Compartilhe sua experiência com este vestido..."></textarea>
                    <button type="submit" name="enviar_feedback"
                        style="background-color: black; color: white; padding: 10px 20px; border: none; border-radius: 20px; cursor: pointer;">Enviar
                        Avaliação</button>
                </form>
            </div>

            <!-- Lista de avaliações existentes -->
            <div class="feedback-list">
                <?php if (empty($feedbacks)): ?>
                    <p style="text-align: center; padding: 20px;">Este vestido ainda não possui avaliações. Seja o primeiro a
                        avaliar!</p>
                <?php else: ?>
                    <?php foreach ($feedbacks as $feedback): ?>
                        <div
                            style="margin-bottom: 20px; padding: 15px; background-color: white; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                <strong><?php echo htmlspecialchars($feedback['nome_usuario']); ?></strong>
                                <span style="color: #666; font-size: 0.9em;">
                                    <?php
                                    $data = new DateTime($feedback['dtafeedback']);
                                    echo $data->format('d/m/Y');
                                    ?>
                                </span>
                            </div>
                            <p style="margin: 0;"><?php echo nl2br(htmlspecialchars($feedback['comentario'])); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 50px;">
            <h2>Produto não encontrado</h2>
            <p>Desculpe, o vestido que você procura não está disponível ou não existe.</p>
            <a href="index.php" class="btn btn-primary"
                style="background-color: black; color: white; padding: 10px 20px; text-decoration: none; border-radius: 20px; display: inline-block; margin-top: 20px;">Voltar
                à página inicial</a>
        </div>
    <?php endif; ?>
</section>

<style>
    /* Estilos para o Modal */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0, 0, 0, 0.5);
    }

    .dress-measurements-display {
        background-color: #e9ecef;
        padding: 15px;
        border-radius: 8px;
        margin-top: 20px;
        border: 1px solid #dee2e6;
    }

    .dress-measurements-display h4 {
        margin-top: 0;
        color: #343a40;
        margin-bottom: 10px;
    }

    .dress-measurements-display ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .dress-measurements-display li {
        margin-bottom: 5px;
        color: #495057;
    }

    .modal-content {
        background-color: #fff;
        margin: 5% auto;
        padding: 20px;
        border-radius: 10px;
        width: 80%;
        max-width: 600px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        position: relative;
    }

    .close-button {
        position: absolute;
        top: 10px;
        right: 15px;
        font-size: 24px;
        font-weight: bold;
        color: #aaa;
        cursor: pointer;
    }

    .close-button:hover {
        color: #000;
    }

    /* Estilos para as abas */
    .measurements-tabs {
        display: flex;
        border-bottom: 1px solid #ddd;
        margin-bottom: 20px;
    }

    .tab-button {
        background-color: #f1f1f1;
        border: none;
        outline: none;
        cursor: pointer;
        padding: 10px 15px;
        margin-right: 5px;
        border-radius: 5px 5px 0 0;
        transition: 0.3s;
    }

    .tab-button:hover {
        background-color: #ddd;
    }

    .tab-button.active {
        background-color: #000;
        color: white;
    }

    .tab-content {
        display: none;
        padding: 15px 0;
    }

    /* Estilos para a tabela de tamanhos */
    .size-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }

    .size-table th,
    .size-table td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: center;
    }

    .size-table th {
        background-color: #f2f2f2;
    }

    .size-table tr:nth-child(even) {
        background-color: #f9f9f9;
    }

    /* Estilos para o formulário de verificação */
    .measurement-form {
        margin-bottom: 20px;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
    }

    .form-group input {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    .check-fit-button {
        background-color: #000;
        color: white;
        border: none;
        padding: 10px 15px;
        border-radius: 20px;
        cursor: pointer;
        margin-top: 10px;
    }

    /* Estilos para os resultados */
    .fit-result {
        background-color: #f9f9f9;
        padding: 15px;
        border-radius: 5px;
        margin-top: 20px;
    }

    .result-item {
        margin-bottom: 10px;
        padding: 8px;
        border-radius: 4px;
    }

    .result-perfect {
        background-color: #d4edda;
        color: #155724;
    }

    .result-tight {
        background-color: #fff3cd;
        color: #856404;
    }

    .result-loose {
        background-color: #d1ecf1;
        color: #0c5460;
    }

    .result-not-fit {
        background-color: #f8d7da;
        color: #721c24;
    }

    .overall-result {
        margin-top: 15px;
        padding: 10px;
        border-radius: 4px;
        font-weight: bold;
        text-align: center;
    }

    .measurement-info {
        background-color: #f9f9f9;
        padding: 15px;
        border-radius: 5px;
        margin-top: 20px;
    }

    .measurement-info ul {
        padding-left: 20px;
    }
</style>

<script>
    // Variáveis com as medidas do vestido
    const dressMeasurements = {
        bust: <?php echo isset($produto['busto']) ? $produto['busto'] : 0; ?>,
        waist: <?php echo isset($produto['cintura']) ? $produto['cintura'] : 0; ?>,
        hip: <?php echo isset($produto['quadril']) ? $produto['quadril'] : 0; ?>,
        size: "<?php echo isset($produto['tamanho']) ? $produto['tamanho'] : 'M'; ?>"
    };

    // Função para abrir o modal
    function openMeasurementsModal() {
        document.getElementById('measurementsModal').style.display = 'block';
    }

    // Função para fechar o modal
    function closeMeasurementsModal() {
        document.getElementById('measurementsModal').style.display = 'none';
    }

    // Função para alternar entre as abas
    function openTab(evt, tabName) {
        // Esconder todos os conteúdos das abas
        var tabContents = document.getElementsByClassName("tab-content");
        for (var i = 0; i < tabContents.length; i++) {
            tabContents[i].style.display = "none";
        }

        // Remover a classe "active" de todos os botões de aba
        var tabButtons = document.getElementsByClassName("tab-button");
        for (var i = 0; i < tabButtons.length; i++) {
            tabButtons[i].className = tabButtons[i].className.replace(" active", "");
        }

        // Mostrar o conteúdo da aba atual e adicionar a classe "active" ao botão
        document.getElementById(tabName).style.display = "block";
        evt.currentTarget.className += " active";
    }

    // Função para verificar se o vestido serve
    function checkFit() {
        // Obter os valores inseridos pelo usuário
        const userBust = parseFloat(document.getElementById('userBust').value);
        const userWaist = parseFloat(document.getElementById('userWaist').value);
        const userHip = parseFloat(document.getElementById('userHip').value);

        // Verificar se todos os campos foram preenchidos
        if (isNaN(userBust) || isNaN(userWaist) || isNaN(userHip)) {
            alert("Por favor, preencha todas as medidas.");
            return;
        }

        // Definir as margens de tolerância (em cm)
        const tightMargin = 1;  // Diferença para considerar apertado
        const looseMargin = 3;  // Diferença para considerar folgado
        const notFitMargin = 5; // Diferença para considerar que não serve

        // Obter as medidas do vestido
        const dressSize = dressMeasurements.size;
        let dressBust, dressWaist, dressHip;

        // Se as medidas específicas do vestido não estiverem disponíveis, usar valores padrão baseados no tamanho
        if (dressMeasurements.bust <= 0 || dressMeasurements.waist <= 0 || dressMeasurements.hip <= 0) {
            // Valores médios para cada tamanho
            switch (dressSize) {
                case 'PP':
                    dressBust = 80;
                    dressWaist = 60;
                    dressHip = 88;
                    break;
                case 'P':
                    dressBust = 84;
                    dressWaist = 64;
                    dressHip = 92;
                    break;
                case 'M':
                    dressBust = 88;
                    dressWaist = 68;
                    dressHip = 96;
                    break;
                case 'G':
                    dressBust = 92;
                    dressWaist = 72;
                    dressHip = 100;
                    break;
                case 'GG':
                    dressBust = 96;
                    dressWaist = 76;
                    dressHip = 104;
                    break;
                default:
                    dressBust = 88;
                    dressWaist = 68;
                    dressHip = 96;
            }
        } else {
            dressBust = dressMeasurements.bust;
            dressWaist = dressMeasurements.waist;
            dressHip = dressMeasurements.hip;
        }

        // Calcular as diferenças
        const bustDiff = userBust - dressBust;
        const waistDiff = userWaist - dressWaist;
        const hipDiff = userHip - dressHip;

        // Avaliar o ajuste para cada medida
        let bustResult, waistResult, hipResult;

        // Busto
        if (Math.abs(bustDiff) <= tightMargin) {
            bustResult = {
                status: 'perfect',
                message: 'Perfeito: O busto do vestido servirá bem em você.'
            };
        } else if (bustDiff > tightMargin && bustDiff <= notFitMargin) {
            bustResult = {
                status: 'tight',
                message: 'Apertado: O busto do vestido pode ficar um pouco apertado.'
            };
        } else if (bustDiff < -tightMargin && bustDiff >= -looseMargin) {
            bustResult = {
                status: 'loose',
                message: 'Folgado: O busto do vestido pode ficar um pouco folgado.'
            };
        } else if (bustDiff < -looseMargin && bustDiff >= -notFitMargin) {
            bustResult = {
                status: 'loose',
                message: 'Muito folgado: O busto do vestido ficará folgado demais.'
            };
        } else {
            bustResult = {
                status: 'not-fit',
                message: 'Não serve: O busto do vestido não servirá em você.'
            };
        }

        // Cintura
        if (Math.abs(waistDiff) <= tightMargin) {
            waistResult = {
                status: 'perfect',
                message: 'Perfeito: A cintura do vestido servirá bem em você.'
            };
        } else if (waistDiff > tightMargin && waistDiff <= notFitMargin) {
            waistResult = {
                status: 'tight',
                message: 'Apertado: A cintura do vestido pode ficar um pouco apertada.'
            };
        } else if (waistDiff < -tightMargin && waistDiff >= -looseMargin) {
            waistResult = {
                status: 'loose',
                message: 'Folgado: A cintura do vestido pode ficar um pouco folgada.'
            };
        } else if (waistDiff < -looseMargin && waistDiff >= -notFitMargin) {
            waistResult = {
                status: 'loose',
                message: 'Muito folgado: A cintura do vestido ficará folgada demais.'
            };
        } else {
            waistResult = {
                status: 'not-fit',
                message: 'Não serve: A cintura do vestido não servirá em você.'
            };
        }

        // Quadril
        if (Math.abs(hipDiff) <= tightMargin) {
            hipResult = {
                status: 'perfect',
                message: 'Perfeito: O quadril do vestido servirá bem em você.'
            };
        } else if (hipDiff > tightMargin && hipDiff <= notFitMargin) {
            hipResult = {
                status: 'tight',
                message: 'Apertado: O quadril do vestido pode ficar um pouco apertado.'
            };
        } else if (hipDiff < -tightMargin && hipDiff >= -looseMargin) {
            hipResult = {
                status: 'loose',
                message: 'Folgado: O quadril do vestido pode ficar um pouco folgado.'
            };
        } else if (hipDiff < -looseMargin && hipDiff >= -notFitMargin) {
            hipResult = {
                status: 'loose',
                message: 'Muito folgado: O quadril do vestido ficará folgado demais.'
            };
        } else {
            hipResult = {
                status: 'not-fit',
                message: 'Não serve: O quadril do vestido não servirá em você.'
            };
        }

        // Determinar o resultado geral
        let overallStatus, overallMessage;

        if (bustResult.status === 'not-fit' || waistResult.status === 'not-fit' || hipResult.status === 'not-fit') {
            overallStatus = 'not-fit';
            overallMessage = 'Este vestido provavelmente não servirá bem em você. Recomendamos escolher outro tamanho ou modelo.';
        } else if (bustResult.status === 'tight' || waistResult.status === 'tight' || hipResult.status === 'tight') {
            overallStatus = 'tight';
            overallMessage = 'Este vestido pode ficar um pouco apertado em algumas áreas. Considere um tamanho maior se preferir mais conforto.';
        } else if (bustResult.status === 'loose' || waistResult.status === 'loose' || hipResult.status === 'loose') {
            overallStatus = 'loose';
            overallMessage = 'Este vestido pode ficar um pouco folgado em algumas áreas. Considere um tamanho menor ou ajustes de costura.';
        } else {
            overallStatus = 'perfect';
            overallMessage = 'Este vestido deve servir perfeitamente em você! É uma ótima escolha.';
        }

        // Exibir os resultados
        document.getElementById('bustResult').innerHTML = bustResult.message;
        document.getElementById('bustResult').className = 'result-item result-' + bustResult.status;

        document.getElementById('waistResult').innerHTML = waistResult.message;
        document.getElementById('waistResult').className = 'result-item result-' + waistResult.status;

        document.getElementById('hipResult').innerHTML = hipResult.message;
        document.getElementById('hipResult').className = 'result-item result-' + hipResult.status;

        document.getElementById('overallResult').innerHTML = overallMessage;
        document.getElementById('overallResult').className = 'overall-result result-' + overallStatus;

        // Mostrar a seção de resultados
        document.getElementById('fitResult').style.display = 'block';
    }

    // Fechar o modal quando o usuário clicar fora dele
    window.onclick = function (event) {
        const modal = document.getElementById('measurementsModal');
        if (event.target === modal) {
            modal.style.display = "none";
        }
    }
</script>

<?php
// Incluir rodapé
include 'includes/footer.php';
?>