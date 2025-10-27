<?php
require_once "conexao.php";
session_start();

if (!isset($_SESSION['idCliente'])) {
    header("Content-Type: image/png");
    readfile("../IMG/usuario_padrao.png");
    exit;
}

$idCliente = $_SESSION['idCliente'];

// Se houver upload de arquivo
if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === 0) {
    $arquivo = $_FILES['avatar']['tmp_name'];
    $imagem_blob = file_get_contents($arquivo);

    $stmt = $pdo->prepare("UPDATE Cliente SET foto_perfil = :foto WHERE idCliente = :id");
    $stmt->execute([
        ':foto' => $imagem_blob,
        ':id' => $idCliente
    ]);

    // Redireciona para o perfil ou adiciona parâmetro de sucesso
    header("Location: ../PAGINAS_CLIENTE/perfil.html?upload=ok");
    exit;
}

// Se for acessado diretamente pelo <img>, retorna a imagem
$stmt = $pdo->prepare("SELECT foto_perfil FROM Cliente WHERE idCliente = :id");
$stmt->execute([':id' => $idCliente]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if ($cliente && $cliente['foto_perfil']) {
    header("Content-Type: image/jpeg");
    echo $cliente['foto_perfil'];
} else {
    header("Content-Type: image/png");
    readfile("../IMG/usuario_padrao.png");
}
?>
