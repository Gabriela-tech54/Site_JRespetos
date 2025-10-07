<?php
require_once __DIR__ ."/conexao.php";

try {
    if($_SERVER['REQUEST_METHOD'] !== "POST"){
        die("Erro: método inválido.");
    }

    $bairro = $_POST["bairro"] ?? '';
    $valor = $_POST["valor"] ?? '';
    $transportadora = $_POST["transportadora"] ?? '';

    // Validação mínima
    if($bairro === "" || $valor === "" || $transportadora === ""){
        die("Erro: preencha todos os campos obrigatórios (incluindo transportadora).");
    }

    // Ajustando para a tabela e colunas corretas
    $sql = "INSERT INTO Frete (bairro, valor, transportadora) VALUES (:bairro, :valor, :transportadora)";
    $stmt = $pdo->prepare($sql);
    $executou = $stmt->execute([
        ":bairro" => $bairro,
        ":valor" => $valor,
        ":transportadora" => $transportadora
    ]);

    if($executou){
        echo "Cadastro realizado com sucesso!";
    } else {
        $erro = $stmt->errorInfo();
        die("Erro ao cadastrar: ".$erro[2]);
    }

} catch(PDOException $e){
    die("Erro no banco: ".$e->getMessage());
}
?>
