<?php
// perfil.php
require_once "../php/conexao.php";
session_start();

// Verifica se o cliente está logado
if (!isset($_SESSION['idCliente'])) {
    header("Location: ../PAGINAS_CLIENTE/login.html");
    exit;
}

$idCliente = $_SESSION['idCliente'];

// Buscar foto e dados do banco
$stmt = $pdo->prepare("SELECT foto_perfil, nome, email FROM Cliente WHERE idCliente = :id");
$stmt->execute([':id' => $idCliente]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

// Se tiver foto, converte para base64, senão usa padrão
$foto = $cliente['foto_perfil'] 
    ? 'data:image/jpeg;base64,' . base64_encode($cliente['foto_perfil']) 
    : 'https://cdn-icons-png.flaticon.com/512/149/149071.png';

// Mensagem de upload
$mensagem = '';
if (isset($_GET['upload']) && $_GET['upload'] === 'ok') {
    $mensagem = "Foto enviada com sucesso!";
} elseif (isset($_GET['upload']) && $_GET['upload'] === 'erro') {
    $mensagem = "Erro ao enviar a foto.";
}
?>
