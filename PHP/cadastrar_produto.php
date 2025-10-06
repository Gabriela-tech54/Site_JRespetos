<?php
require_once __DIR__ . "/conexao.php";

function redirecWith($url, $params = []) {
    if (!empty($params)) {
        $qs = http_build_query($params);
        $sep = (strpos($url, '?') === false) ? '?' : '&';
        $url .= $sep . $qs;
    }
    header("Location: $url");
    exit;
}

function readImageToBlob(?array $file): ?string {
    if (!$file || !isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) return null;
    $content = file_get_contents($file['tmp_name']);
    return $content === false ? null : $content;
}

try {
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        redirecWith("../paginas_lojista/cadastroproduto.html", ["erro" => "Método inválido"]);
    }

    // Captura dos dados do formulário
    $nome = $_POST["nome"] ?? "";
    $descricao = $_POST["descricao"] ?? "";
    $quantidade = (int)($_POST["quantidade"] ?? 0);
    $preco = (double)($_POST["preco"] ?? 0);
    $tamanho = $_POST["tamanho"] ?? null;
    $cor = $_POST["cor"] ?? null;
    $codigo = (int)($_POST["codigo"] ?? 0);
    $preco_promocional = (double)($_POST["preco_promocional"] ?? 0);
    $categoria = (int)($_POST["categoria"] ?? 0);

    $imagem = readImageToBlob($_FILES["imagem"] ?? null);

    // Validação simples
    $erros_validacao = [];
    if ($nome === "" || $descricao === "" || $quantidade <= 0 || $preco <= 0 || $categoria === 0) {
        $erros_validacao[] = "Preencha todos os campos obrigatórios.";
    }

    if (!empty($erros_validacao)) {
        redirecWith("../paginas_lojista/cadastroproduto.html", ["erro_produto" => implode(",", $erros_validacao)]);
    }

    $pdo->beginTransaction();

    // INSERT PRODUTO
    $sqlProduto = "INSERT INTO Produtos (
        nome, descricao, quantidade, preco, tamanho, cor, codigo, preco_promocional, Categorias_produtos_idCategorias_produtos
    ) VALUES (
        :nome, :descricao, :quantidade, :preco, :tamanho, :cor, :codigo, :preco_promocional, :categoria
    )";

    $stmProduto = $pdo->prepare($sqlProduto);

    $inserirProduto = $stmProduto->execute([
        ":nome" => $nome,
        ":descricao" => $descricao,
        ":quantidade" => $quantidade,
        ":preco" => $preco,
        ":tamanho" => $tamanho,
        ":cor" => $cor,
        ":codigo" => $codigo,
        ":preco_promocional" => $preco_promocional,
        ":categoria" => $categoria
    ]);

    if (!$inserirProduto) {
        $pdo->rollBack();
        redirecWith("../paginas_lojista/cadastroproduto.html", ["erro" => "Falha ao cadastrar produto."]);
    }

    $idProduto = (int)$pdo->lastInsertId();

    // INSERIR IMAGEM (se existir)
    if ($imagem !== null) {
        $sqlImagem = "INSERT INTO Imagens_produtos (foto) VALUES (:imagem)";
        $stmImagem = $pdo->prepare($sqlImagem);
        $stmImagem->bindParam(':imagem', $imagem, PDO::PARAM_LOB);
        $inserirImagem = $stmImagem->execute();

        if (!$inserirImagem) {
            $pdo->rollBack();
            redirecWith("../paginas_lojista/cadastroproduto.html", ["erro" => "Falha ao cadastrar imagem."]);
        }

        $idImg = (int)$pdo->lastInsertId();

        // Vincular produto com imagem (se a tabela existir)
        $sqlVinculo = "INSERT INTO Produtos_e_Imagens_produtos (Produtos_idProdutos, Imagens_produtos_idImagens_produtos)
                       VALUES (:idpro, :idimg)";
        $stmVinculo = $pdo->prepare($sqlVinculo);
        $vinculado = $stmVinculo->execute([":idpro" => $idProduto, ":idimg" => $idImg]);

        if (!$vinculado) {
            $pdo->rollBack();
            redirecWith("../paginas_lojista/cadastroproduto.html", ["erro" => "Falha ao vincular produto e imagem."]);
        }
    }

    $pdo->commit();
    redirecWith("../paginas_lojista/cadastroproduto.html", ["sucesso" => "Produto cadastrado com sucesso!"]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    redirecWith("../paginas_lojista/cadastroproduto.html", ["erro" => "Erro no banco de dados: " . $e->getMessage()]);
}
?>
