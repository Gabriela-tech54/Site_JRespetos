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


// LISTAGEM DE MARCAS COM IMAGEM
if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["listar"])){
// Define o tipo de resposta: JSON e com codificação UTF-8
  header('Content-Type: application/json; charset=utf-8');

  try {
    // Faz a consulta no banco — busca id, nome e imagem (blob)
    $stmt = $pdo->query("SELECT idMarcas, nome, imagem FROM Marcas ORDER BY idMarcas DESC");

    // Pega todas as linhas retornadas como array associativo
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Mapeia cada linha para o formato desejado:
    //  - converte o id para inteiro
    //  - mantém o nome como texto
    //  - converte o blob da imagem para base64 (ou null se não houver imagem)
    $marcas = array_map(function ($r) {
      return [
        'idMarcas' => (int)$r['idMarcas'],
        'nome'     => $r['nome'],
        'imagem'   => !empty($r['imagem']) ? base64_encode($r['imagem']) : null
      ];
    }, $rows);

    // Retorna o JSON com:
    //  - ok: true  → indica sucesso
    //  - count: quantidade de marcas encontradas
    //  - marcas: array com todos os dados
    echo json_encode(
      ['ok'=>true,'count'=>count($marcas),'marcas'=>$marcas],
      JSON_UNESCAPED_UNICODE // mantém acentos corretamente
    );

  } catch (Throwable $e) {
    // Se acontecer qualquer erro (ex: problema no banco),
    // envia código HTTP 500 e o erro no formato JSON
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
  }

  //  Interrompe a execução do restante do arquivo.
  
  exit;

}






try {
/* CADASTRAR */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  redirect_with('../PAGINAS_LOGISTA/cadastro_produtos_logista.html', [
    'erro_marca' => 'Método inválido'
  ]);
}


  $nome = trim($_POST['nomemarca'] ?? '');
  $imgBlob = read_image_to_blob($_FILES['imagemmarca'] ?? null);

  if ($nome === '') {
    redirect_with('../PAGINAS_LOGISTA/cadastro_produtos_logista.html', [
      'erro_marca' => 'Preencha o nome da marca.'
    ]);
  }

  $sql = "INSERT INTO Marcas (nome, imagem) VALUES (:n, :i)";
  $st  = $pdo->prepare($sql);
  $st->bindValue(':n', $nome, PDO::PARAM_STR);
  if ($imgBlob === null) $st->bindValue(':i', null, PDO::PARAM_NULL);
  else $st->bindValue(':i', $imgBlob, PDO::PARAM_LOB);
  $st->execute();

  redirect_with('../PAGINAS_LOGISTA/cadastro_produtos_logista.html', [
    'cadastro_marca' => 'ok'
  ]);
} catch (Throwable $e) {
  redirect_with('../PAGINAS_LOGISTA/cadastro_produtos_logista.html', [
    'erro_marca' => 'Erro no banco de dados: ' . $e->getMessage()
  ]);
}


?>
