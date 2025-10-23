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

// ========================= LISTAGEM DE PRODUTOS ========================= //
if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["listar"])) {
    try {
        if (ob_get_length()) ob_clean(); // limpa qualquer saída
        header("Content-Type: application/json; charset=utf-8");

        $sqlListar = "
          SELECT 
                p.idProdutos, 
                p.nome, 
                p.descricao, 
                p.quantidade, 
                p.preco, 
                p.situacao,
                ip.foto AS imagem_blob
            FROM Produtos p
            LEFT JOIN Produtos_e_Imagens_produtos pip ON pip.Produtos_idProdutos = p.idProdutos
            LEFT JOIN Imagens_produtos ip ON ip.idImagens_produtos = pip.Imagens_produtos_idImagens_produtos
            GROUP BY p.idProdutos
            ORDER BY p.idProdutos DESC
        ";
        $stmtListar = $pdo->query($sqlListar);
        $listar = $stmtListar->fetchAll(PDO::FETCH_ASSOC);

        $saida = array_map(function ($item) {
            return [
                "idProduto" => (int)$item["idProdutos"],
                "nome"      => $item["nome"] ?? "",
                "descricao" => $item["descricao"] ?? "",
                "quantidade"=> (int)($item["quantidade"] ?? 0),
                "preco"     => (float)($item["preco"] ?? 0),
                "situacao"  => !empty($item["situacao"]) ? "1" : "0",
                "imagem"    => !empty($item["imagem_blob"]) ? base64_encode($item["imagem_blob"]) : null
            ];
        }, $listar);

        echo json_encode(["ok" => true, "produtos" => $saida], JSON_UNESCAPED_UNICODE);
        exit;

    } catch (Throwable $e) {
        if (ob_get_length()) ob_clean();
        header("Content-Type: application/json; charset=utf-8", true, 500);
        echo json_encode([
            "ok" => false,
            "error" => "Erro ao listar produtos",
            "detail" => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}








// ============================ CADASTRO ============================ //
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


// ============================ EXCLUSÃO ============================ //
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'excluir') {
    try {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(["ok" => false, "error" => "ID inválido para exclusão"]);
            exit;
        }

        $st = $pdo->prepare("DELETE FROM Produtos WHERE idProdutos = :id");
        $st->bindValue(':id', $id, PDO::PARAM_INT);
        $st->execute();

        echo json_encode(["ok" => true, "excluir" => "ok"]);
        exit;

    } catch (Throwable $e) {
        echo json_encode(["ok" => false, "error" => $e->getMessage()]);
        exit;
    }
}

// ============================ EDIÇÃO ============================ //
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'atualizar') {
    try {
        $id = (int)($_POST['id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $quantidade = (int)($_POST['quantidade'] ?? 0);
        $preco = (float)($_POST['preco'] ?? 0);
        $situacao = (int)($_POST['situacao'] ?? 0);

        if ($id <= 0) {
            echo json_encode(["ok" => false, "error" => "ID inválido para edição"]);
            exit;
        }

        $sql = "UPDATE produtos SET nome = :nome, descricao = :descricao, quantidade = :quantidade, preco = :preco, situacao = :situacao WHERE idProdutos = :id";
        $st = $pdo->prepare($sql);
        $st->bindValue(':nome', $nome, PDO::PARAM_STR);
        $st->bindValue(':descricao', $descricao, PDO::PARAM_STR);
        $st->bindValue(':quantidade', $quantidade, PDO::PARAM_INT);
        $st->bindValue(':preco', $preco);
        $st->bindValue(':situacao', $situacao, PDO::PARAM_INT);
        $st->bindValue(':id', $id, PDO::PARAM_INT);
        $st->execute();

        echo json_encode(["ok" => true]);
        exit;

    } catch (\Throwable $e) {
        echo json_encode(["ok" => false, "error" => $e->getMessage()]);
        exit;
    }
}