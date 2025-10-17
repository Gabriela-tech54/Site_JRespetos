<?php

// Conectando este arquivo ao banco de dados
require_once __DIR__ ."/conexao.php";

// função para capturar os dados passados de uma página a outra
function redirecWith($url,$params=[]){
// verifica se os os paramentros não vieram vazios
 if(!empty($params)){
// separar os parametros em espaços diferentes
$qs= http_build_query($params);
$sep = (strpos($url,'?') === false) ? '?': '&';
$url .= $sep . $qs;
}
// joga a url para o cabeçalho no navegador
header("Location:  $url");
// fecha o script
exit;
}
// ========================= LISTAGEM DE PRODUTOS ========================= //
require_once __DIR__ . "/conexao.php";

try {
    // Só processa GET com ?listar=1
    if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["listar"])) {

        // Define o formato de saída: JSON (padrão)
        $formato = isset($_GET["format"]) ? strtolower($_GET["format"]) : "json";

        // Consulta os produtos e suas imagens (LEFT JOIN garante produtos sem imagem)
        $sql = "
            SELECT 
                p.idProdutos,
                p.nome,
                p.descricao,
                p.quantidade,
                p.preco,
                p.situacao,
                p.tamanho,
                p.cor,
                p.codigo,
                ip.foto AS imagem_blob
            FROM Produtos p
            LEFT JOIN Produtos_e_Imagens_produtos pip ON pip.Produtos_idProdutos = p.idProdutos
            LEFT JOIN Imagens_produtos ip ON ip.idImagens_produtos = pip.Imagens_produtos_idImagens_produtos
            ORDER BY p.idProdutos DESC
        ";

        $stmt = $pdo->query($sql);
        $listar = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($formato === "json") {
            // Normaliza os dados e converte imagens para Base64
            $saida = array_map(function($item) {
                return [
                    "idProduto" => (int)$item["idProdutos"],
                    "nome"      => $item["nome"] ?? "",
                    "descricao" => $item["descricao"] ?? "",
                    "quantidade"=> isset($item["quantidade"]) ? (int)$item["quantidade"] : 0,
                    "preco"     => isset($item["preco"]) ? (float)$item["preco"] : 0,
                    "situacao"  => !empty($item["situacao"]) ? "1" : "0",
                    "tamanho"   => $item["tamanho"] ?? "",
                    "cor"       => $item["cor"] ?? "",
                    "codigo"    => $item["codigo"] ?? "",
                    "imagem"    => !empty($item["imagem_blob"]) ? base64_encode($item["imagem_blob"]) : null
                ];
            }, $listar);

            // Retorna JSON
            header("Content-Type: application/json; charset=utf-8");
            echo json_encode([
                "ok" => true,
                "count" => count($saida),
                "produtos" => $saida
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Caso queira futuramente um retorno HTML (ex: <option>)
        header("Content-Type: text/html; charset=utf-8");
        foreach ($listar as $item) {
            $id   = (int)$item["idProdutos"];
            $nome = htmlspecialchars($item["nome"], ENT_QUOTES, "UTF-8");
            echo "<option value=\"{$id}\">{$nome}</option>\n";
        }
        exit;
    }
} catch (Throwable $e) {
    // Retorno JSON em caso de erro
    if (isset($formato) && $formato === "json") {
        header("Content-Type: application/json; charset=utf-8", true, 500);
        echo json_encode([
            "ok" => false,
            "error" => "Erro ao listar produtos",
            "detail" => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    } else {
        header("Content-Type: text/html; charset=utf-8", true, 500);
        echo "<option disabled>Erro ao carregar produtos</option>";
    }
    exit;
}




try{
    // SE O METODO DE ENVIO FOR DIFERENTE DO POST
    if($_SERVER["REQUEST_METHOD"] !== "POST"){
        //VOLTAR À TELA DE CADASTRO E EXIBIR ERRO
        redirecWith("../paginas_lojista/promocaoebanners.html",
           ["erro"=> "Metodo inválido"]);
    }
    // variaveis
    $titulo = $_POST["titulo"];
   // pega o arquivo enviado (imagem)
$imagem = null;
if (isset($_FILES['img']) && $_FILES['img']['error'] === UPLOAD_ERR_OK) {
    // lê o conteúdo do arquivo em binário
    $imagem = file_get_contents($_FILES['img']['tmp_name']);
}

    $status = $_POST["status"];

    // validação
    $erros_validacao=[];
    //se qualquer campo for vazio
    if($titulo === "" || $img ==="" || $status){
        $erros_validacao[]="Preencha todos os campos";
    }

    /* Inserir o frete no banco de dados */
    $sql ="INSERT INTO 
    Banners (imagem,nome,situacao)
     Values (:imagem,:nome,:situacao)";
     // executando o comando no banco de dados
     $inserir = $pdo->prepare($sql)->execute([
        ":imagem" => $imagem,
        ":nome"=> $nome,
        ":situacao"=> $situacao,     
     ]);

     /* Verificando se foi cadastrado no banco de dados */
     if($inserir){
        redirecWith("../paginas_lojista/promocaoebanners.html",
        ["cadastro" => "ok"]) ;
     }else{
        redirecWith("../paginas_lojista/promocaoebanners.html"
        ,["erro" =>"Erro ao cadastrar no banco
         de dados"]);
     }
}catch(\Exception $e){
redirecWith("../paginas_lojista/promocaoebanners.html",
      ["erro" => "Erro no banco de dados: "
      .$e->getMessage()]);
}


?>