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
 
/* Lê arquivo de upload como blob (ou null) */
function readImageToBlob(?array $file): ?string {
  if (!$file || !isset($file['tmp_name']) || $file['error'] 
  !== UPLOAD_ERR_OK) return null;
  $content = file_get_contents($file['tmp_name']);
  return $content === false ? null : $content;
}

// capturando os dados e jogando em váriaveis
try{
    // SE O METODO DE ENVIO FOR DIFERENTE DO POST
    if($_SERVER["REQUEST_METHOD"] !== "POST"){
        redirecWith("../paginas_lojista/cadastroproduto.html",
           ["erro"=> "Metodo inválido"]);
    }

    // criar as váriaveis do produto
    $nome = $_POST["nome"];
    $descricao = $_POST["descricao"];
    $quantidade = (int)$_POST["quantidade"];
    $preco = (double)$_POST["preco"];
    $tamanho = $_POST["tamanho"];
    $cor = $_POST["cor"];    
    $codigo = (int)$_POST["codigo"];
    $preco_promocional = (double)$_POST[""];

    //criar as váriaveis das imagens
$img1   = readImageToBlob($_FILES["imgproduto1"] ?? null);
$img2   = readImageToBlob($_FILES["imgproduto2"] ?? null);
$img3   = readImageToBlob($_FILES["imgproduto3"] ?? null);

// validando os campos
$erros_validacao = [];
if($nome === ""|| $descricao === ""|| $quantidade === 0
|| $preco ===0){
    $erros_validacao[] = "Preencha os campos obrigatórios";
}
//se houver erros, volta para a tela com a mensagem
if(!empty($erros_validacao)) {
    redirecWith("../paginas_lojista/cadastroproduto.html",
    ["erro_produto" => implode(",", $erros_validacao)]);
}

// é utilizado para fazer vinculos de transações
$pdo ->beginTransaction();

//fazer o comando de inserir dentro da tabela de produtos
$sql = "INSERT INTO Produtos (nome,descricao,quantidade,
preco,tamanho,cor,codigo,preco_promocional)
        VALUES (:nome, :descricao, :quantidade, :preco, 
        :tamanho, :cor, :codigo, :preco_promocional)";

$stmProdutos = $pdo -> prepare($sqlProdutos);

    $inserirProdutos=$stmProdutos->execute([
    ":nome" => $nome,
    ":descricao" => $descricao,
    ":categoria" => $categoria,
    ":preco" => $preco,
    ":quantidade" => $quantidade
]);


/* Verificando se foi cadastrado no banco de dados */
     if($inserir){
        redirecWith("../paginas_lojista/cadastroproduto.html",
        ["cadastro" => "ok"]) ;
     }else{
        redirecWith("../paginas_lojista/cadastroproduto.html",["erro" 
        =>"Erro ao cadastrar no banco de dados"]);
     }
/* agora que tudo foi feito no Try, vamos elaborar 
    o catch com os possiveis erros */

}catch(Exception $e){
     redirecWith("../paginas_lojista/cadastroproduto.html",
      ["erro" => "Erro no banco de dados: " . $e->getMessage()]);
}
?>