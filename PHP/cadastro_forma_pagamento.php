<?php
require_once __DIR__ . "/conexao.php";

// Função de redirecionamento
function redirecWith($url, $params = [])
{
    if (!empty($params)) {
        $qs = http_build_query($params);
        $sep = (strpos($url, '?') === false) ? '?' : '&';
        $url .= $sep . $qs;
    }
    header("Location: $url");
    exit;
}

try {
    // Verifica se veio por POST
    if ($_SERVER['REQUEST_METHOD'] !== "POST") {
        redirecWith("../paginas_lojista/fretepagamentolojista.html", ["erro" => "Método inválido"]);
    }

    // Recebe os dados
    $nomepagamento = trim($_POST["pagamento"] ?? "");

    // Validação
    if ($nomepagamento === "") {
        redirecWith("../paginas_lojista/fretepagamentolojista.html", ["erro" => "Preencha o campo de nome"]);
    }

    // Inserir no banco
    $sql = "INSERT INTO Formas_pagamento (nome) VALUES (:nomepagamento)";
    $stmt = $pdo->prepare($sql);
    $inserir = $stmt->execute([":nomepagamento" => $nomepagamento]);

    // Verificação
    if ($inserir) {
        redirecWith("../paginas/fretepagamentolojista.html", ["cadastro" => "ok"]);
    } else {
        redirecWith("../paginas_lojista/fretepagamentolojista.html", ["erro" => "Erro ao cadastrar no banco de dados"]);
    }/* Verificando se foi cadastrado no banco de dados */
     if($inserir){
        redirecWith("../paginas_lojista/fretepagamentolojista.html",
        ["cadastro" => "ok"]) ;
     }

} catch (Exception $e) {
    redirecWith("../paginas_lojista/fretepagamentolojista.html", ["erro" => "Erro no banco: " . $e->getMessage()]);
}
?>
