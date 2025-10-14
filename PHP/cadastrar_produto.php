<?php
require_once __DIR__ . "/conexao.php";

function redirecWith($url, $params = []) {
  if (!empty($params)) {
    $qs  = http_build_query($params);
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
    redirecWith("../paginas_logista/cadastroproduto.html", ["erro" => "Método inválido"]);
  }

  // Apenas ajustar para os nomes do HTML
  $nome = $_POST["nomeproduto"];
  $descricao = $_POST["descricao"];
  $quantidade = (int)$_POST["quantidade"];
  $preco = (double)$_POST["preco"];
  $situacao = (boolean)$_POST["status"];
  ;

  // Ajuste para ler a única imagem que existe no HTML
  $img = readImageToBlob($_FILES["imagem"] ?? null);

  // Categoria padrão
  $categoria = 1;

  // Validação básica
  if ($nome === "" || $descricao === "" || $quantidade <= 0 || $preco <= 0) {
    redirecWith("../paginas_lojista/cadastroproduto.html", ["erro" => "Preencha os campos obrigatórios."]);
  }

  $pdo->beginTransaction();

  // Inserir produto
  $sqlProdutos = "INSERT INTO Produtos
    (nome, descricao, quantidade, preco, situacao)
    VALUES (:nome, :descricao, :quantidade, :preco, :situacao)";

  $stmProdutos = $pdo->prepare($sqlProdutos);
  $stmProdutos->execute([
    ":nome" => $nome,
    ":descricao" => $descricao,
    ":quantidade" => $quantidade,
    ":preco" => $preco,
    ":situacao" => $situacao,
    
  ]);

  $idproduto = (int)$pdo->lastInsertId();

  // Inserir imagem se houver
  if ($img !== null) {
    $sqlImg = "INSERT INTO Imagens_produtos(foto) VALUES (:foto)";
    $stmImg = $pdo->prepare($sqlImg);
    $stmImg->bindParam(':foto', $img, PDO::PARAM_LOB);

    $stmImg->execute();
    $idImg = (int)$pdo->lastInsertId();

    // Vincular produto com imagem
    $sqlVincular = "INSERT INTO Produtos_e_Imagens_produtos (Produtos_idProdutos, Imagens_produtos_idImagens_produtos) VALUES (:idpro, :idimg)";
    $stmVincular = $pdo->prepare($sqlVincular);
    $stmVincular->execute([
      ":idpro" => $idproduto,
      ":idimg" => $idImg
    ]);
  }

  $pdo->commit();
  redirecWith("../paginas_lojista/cadastroproduto.html", ["Cadastro" => "ok"]);

} catch (Exception $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  redirecWith("../paginas_lojista/cadastroproduto.html", ["erro" => "Erro no banco de dados: " . $e->getMessage()]);
}
?>
