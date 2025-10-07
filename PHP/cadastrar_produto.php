<?php
require_once __DIR__ . "/conexao.php"; // conexão com o banco

// Função para redirecionar com parâmetros
function redirecWith($url, $params = []) {
    if (!empty($params)) {
        $qs = http_build_query($params);
        $sep = (strpos($url, '?') === false) ? '?' : '&';
        $url .= $sep . $qs;
    }
    header("Location: $url");
    exit;
}

// Função para ler imagem como blob
function readImageToBlob(?array $file): ?string {
    if (!$file || !isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) return null;
    $content = file_get_contents($file['tmp_name']);
    return $content === false ? null : $content;
}

try {
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        redirecWith("../paginas_lojista/cadastroproduto.html", ["erro" => "Método inválido"]);
    }

    // Captura dados do formulário
    $nome = $_POST["nome"] ?? '';
    $descricao = $_POST["descricao"] ?? '';
    $quantidade = (int)($_POST["quantidade"] ?? 1);
    $preco = (float)($_POST["preco"] ?? 0);
    $tamanho = $_POST["tamanho"] ?? null;
    $cor = $_POST["cor"] ?? null;
    $codigo = (int)($_POST["codigo"] ?? 0);
    $preco_promocional = (float)($_POST["preco_promocional"] ?? null);
    $categoria_id = (int)($_POST["categoria"] ?? 0); // Categoria obrigatória
    $status = ($_POST["status"] ?? 'Ativo') === 'Ativo' ? 1 : 0;

    // Captura imagem
    $img = readImageToBlob($_FILES["imagem"] ?? null);
    $img_descricao = "Principal";

    // Validação básica
    $erros = [];
    if ($nome === "" || $descricao === "" || $quantidade <= 0 || $preco <= 0) {
        $erros[] = "Preencha todos os campos obrigatórios corretamente.";
    }
    if (!empty($erros)) {
        redirecWith("../paginas_lojista/cadastroproduto.html", ["erro_produto" => implode(",", $erros)]);
    }

    // Inicia transação
    $pdo->beginTransaction();

    // Inserir produto
    $sqlProduto = "INSERT INTO Produtos 
        (nome, descricao, quantidade, preco, tamanho, cor, codigo, preco_promocional, Categorias_produtos_idCategorias_produtos) 
        VALUES 
        (:nome, :descricao, :quantidade, :preco, :tamanho, :cor, :codigo, :preco_promocional, :categoria_id)";
    $stmtProduto = $pdo->prepare($sqlProduto);
    $stmtProduto->execute([
        ":nome" => $nome,
        ":descricao" => $descricao,
        ":quantidade" => $quantidade,
        ":preco" => $preco,
        ":tamanho" => $tamanho,
        ":cor" => $cor,
        ":codigo" => $codigo,
        ":preco_promocional" => $preco_promocional,
        ":categoria_id" => $categoria_id
    ]);

    $idProduto = (int)$pdo->lastInsertId();

    // Inserir imagem se existir
    if ($img !== null) {
        $sqlImagem = "INSERT INTO Imagens_produtos (foto, descricao) VALUES (:foto, :descricao)";
        $stmtImagem = $pdo->prepare($sqlImagem);
        $stmtImagem->bindParam(':foto', $img, PDO::PARAM_LOB);
        $stmtImagem->bindParam(':descricao', $img_descricao);
        $stmtImagem->execute();

        $idImg = (int)$pdo->lastInsertId();

        // Vincular produto à imagem
        $sqlVinculo = "INSERT INTO Produtos_e_Imagens_produtos 
            (Produtos_idProdutos, Produtos_Categorias_produtos_idCategorias_produtos, Imagens_produtos_idImagens_produtos) 
            VALUES (:idProduto, :categoria_id, :idImg)";
        $stmtVinculo = $pdo->prepare($sqlVinculo);
        $stmtVinculo->execute([
            ":idProduto" => $idProduto,
            ":categoria_id" => $categoria_id,
            ":idImg" => $idImg
        ]);
    }

    $pdo->commit();
    redirecWith("../paginas_lojista/cadastroproduto.html", ["sucesso" => 1]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    redirecWith("../paginas_lojista/cadastroproduto.html", ["erro" => "Erro no banco de dados: " . $e->getMessage()]);
}
?>
