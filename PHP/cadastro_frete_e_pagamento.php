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

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirecWith("../paginas/carrinho.html", ["erro" => "Método inválido"]);
    }

    // Dados do formulário
    $rua = $_POST['rua'];
    $numero = $_POST['numero'];
    $bairro = $_POST['bairro'];
    $cep = $_POST['cep'];
    $pagamento = $_POST['pagamento'];

    if (!$rua || !$numero || !$bairro || !$cep || !$pagamento) {
        redirecWith("../paginas/carrinho.html", ["erro" => "Preencha todos os campos"]);
    }

    // ================== Inserir endereço ==================
    $sqlEndereco = "INSERT INTO Endereco (rua, numero, bairro, cep) 
                    VALUES (:rua, :numero, :bairro, :cep)";
    $stmtEndereco = $pdo->prepare($sqlEndereco);
    $stmtEndereco->execute([
        ":rua" => $rua,
        ":numero" => $numero,
        ":bairro" => $bairro,
        ":cep" => $cep
    ]);
    $idEndereco = $pdo->lastInsertId();

    // ================== Consultar frete para o bairro ==================
    $sqlFrete = "SELECT valor, transportadora FROM Frete WHERE bairro = :bairro LIMIT 1";
    $stmtFrete = $pdo->prepare($sqlFrete);
    $stmtFrete->execute([":bairro" => $bairro]);
    $frete = $stmtFrete->fetch(PDO::FETCH_ASSOC);

    if (!$frete) {
        redirecWith("../paginas/carrinho.html", ["erro" => "Não há frete cadastrado para este bairro"]);
    }

    $valor_frete = $frete['valor'];
    $transportadora = $frete['transportadora'];

    // ================== Inserir pedido ==================
    $sqlPedido = "INSERT INTO Pedido (endereco_id, pagamento, valor_frete, transportadora) 
                  VALUES (:endereco_id, :pagamento, :valor_frete, :transportadora)";
    $stmtPedido = $pdo->prepare($sqlPedido);
    $stmtPedido->execute([
        ":endereco_id" => $idEndereco,
        ":pagamento" => $pagamento,
        ":valor_frete" => $valor_frete,
        ":transportadora" => $transportadora
    ]);

    redirecWith("../paginas/carrinho.html", ["cadastro" => "ok"]);

} catch (Exception $e) {
    redirecWith("../paginas/carrinho.html", ["erro" => "Erro: " . $e->getMessage()]);
}
?>
