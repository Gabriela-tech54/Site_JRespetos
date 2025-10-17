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

// ========================= LISTAGEM DE BANNERS ========================= //
if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["listar"])) {
    try {
        // Comando de listagem de banners
        $sqlListar = "SELECT idBanners, nome, imagem, Situacao FROM Banners ORDER BY idBanners DESC";
        $stmtListar = $pdo->query($sqlListar);
        $listar = $stmtListar->fetchAll(PDO::FETCH_ASSOC);

        $formato = isset($_GET["format"]) ? strtolower($_GET["format"]) : "json";

        if ($formato === "json") {
            $saida = array_map(function ($item) {
                return [
                    "idBanner" => (int)($item["idBanners"]),
                    "titulo"   => $item["nome"] ?? "",
                    "imagem"   => !empty($item["imagem"]) ? base64_encode($item["imagem"]) : null,
                    "promocao" => !empty($item["Situacao"]) ? "sim" : "nao"
                ];
            }, $listar);

            header("Content-Type: application/json; charset=utf-8");
            echo json_encode(["ok" => true, "banners" => $saida], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Retorno padrão HTML (se precisar futuramente)
        header("Content-Type: text/html; charset=utf-8");
        foreach ($listar as $item) {
            $id     = (int)$item["idBanners"];
            $titulo = htmlspecialchars($item["nome"], ENT_QUOTES, "UTF-8");
            $label  = $titulo . (!empty($item["Situacao"]) ? " (Promoção)" : "");
            echo "<option value=\"{$id}\">{$label}</option>\n";
        }
        exit;

    } catch (Throwable $e) {
        if ($formato === "json") {
            header("Content-Type: application/json; charset=utf-8", true, 500);
            echo json_encode(
                ["ok" => false, "error" => "Erro ao listar banners", "detail" => $e->getMessage()],
                JSON_UNESCAPED_UNICODE
            );
        } else {
            header("Content-Type: text/html; charset=utf-8", true, 500);
            echo "<option disabled>Erro ao carregar banners</option>";
        }
        exit;
    }
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