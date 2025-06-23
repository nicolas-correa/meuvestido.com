<?php
// Funções de configuração e utilitárias
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Configurações do banco de dados PostgreSQL
define("DB_HOST", "localhost");
define("DB_PORT", "5432"); // Porta padrão do PostgreSQL
define("DB_NAME", "meuvestido");
define("DB_USER", "postgres"); // Usuário padrão do PostgreSQL
define("DB_PASS", "nicolas"); 

// Conectar ao banco de dados usando PDO
function conectarDB() {
    $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
    try {
        $conn = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, 
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false, 
        ]);
        return $conn;
    } catch (PDOException $e) {
        die("Falha na conexão com o banco de dados: " . $e->getMessage());
    }
}

// Limpar dados de entrada
function limparDados($dados) {
    $dados = trim($dados);
    $dados = stripslashes($dados);
    $dados = htmlspecialchars($dados, ENT_QUOTES, 'UTF-8');
    return $dados;
}

// Verificar se o usuário está logado
function estaLogado() {
    return isset($_SESSION["usuario_id"]); // Usaremos cpusuario como ID
}

// Verificar login e redirecionar se não estiver logado
function verificarLogin() {
    if (!estaLogado()) {
        $_SESSION["redirect_after_login"] = $_SERVER["REQUEST_URI"];
        header("Location: login.php");
        exit;
    }
}

// Formatar preço
function formatarPreco($preco) {
    if (!is_numeric($preco)) {
        return "R$ 0,00";
    }
    return "R$ " . number_format((float)$preco, 2, ",", ".");
}

// --- Funções do Carrinho  ---
function adicionarAoCarrinho($produto_id, $nome, $preco, $imagem, $quantidade = 1, $tamanho = '') {
    if (!isset($_SESSION["carrinho"])) {
        $_SESSION["carrinho"] = [];
    }
    $item_id = $produto_id . "-" . $tamanho; // ID único para produto+tamanho

    if (isset($_SESSION["carrinho"][$item_id])) {
        $_SESSION["carrinho"][$item_id]["quantidade"] += $quantidade;
    } else {
        $_SESSION["carrinho"][$item_id] = [
            "produto_id" => $produto_id, // cpvestido
            "nome" => $nome,
            "preco" => $preco, // valoraluguel
            "imagem" => $imagem, // caminho_imagem da tabela imagens
            "quantidade" => $quantidade,
            "tamanho" => $tamanho,
        ];
    }
}

function removerDoCarrinho($item_id) {
    if (isset($_SESSION["carrinho"][$item_id])) {
        unset($_SESSION["carrinho"][$item_id]);
    }
}

function atualizarQuantidadeCarrinho($item_id, $quantidade) {
    if (isset($_SESSION["carrinho"][$item_id])) {
        $_SESSION["carrinho"][$item_id]["quantidade"] = max(1, min(10, (int)$quantidade));
    }
}

function calcularTotalCarrinho() {
    $total = 0;
    if (isset($_SESSION["carrinho"])) {
        foreach ($_SESSION["carrinho"] as $item) {
            $total += (float)$item["preco"] * (int)$item["quantidade"];
        }
    }
    return $total;
}

// --- Funções de Produtos ---
function obterProduto($id) {
    $conn = conectarDB();
    try {
        // Seleciona dados do vestido, incluindo busto, cintura, quadril, e a primeira imagem associada
        $sql = "SELECT 
                    v.cpvestido, 
                    v.nome, 
                    v.descricao, 
                    v.tamanho, 
                    v.valoraluguel, 
                    v.disponivel,
                    v.busto,    
                    v.cintura,  
                    v.quadril,  
                    (SELECT i.caminho_imagem FROM public.imagens i WHERE i.cevestido = v.cpvestido LIMIT 1) as imagem_principal
                FROM public.vestidos v 
                WHERE v.cpvestido = :id AND v.disponivel = true";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $produto = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $produto ?: false;

    } catch (PDOException $e) {
        error_log("Erro ao obter produto: " . $e->getMessage());
        return false;
    } finally {
        $conn = null; // Fechar conexão
    }
}

// Função para obter todas as imagens de um vestido
function obterImagensProduto($produto_id) {
    $conn = conectarDB();
    try {
        $sql = "SELECT cpimagem, caminho_imagem, nome FROM public.imagens WHERE cevestido = :produto_id ORDER BY cpimagem";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":produto_id", $produto_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro ao obter imagens do produto: " . $e->getMessage());
        return [];
    } finally {
        $conn = null;
    }
}
?>