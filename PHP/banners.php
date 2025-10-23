<?php
// Conexão com o banco de dados
require_once __DIR__ . "./conexao.php";

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

// ========================= LISTAGEM DE BANNERS ========================= //
if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["listar"])) {
    try {
        $sqlListar = "SELECT idBanners, nome, imagem, Situacao FROM Banners ORDER BY idBanners DESC";
        $stmtListar = $pdo->query($sqlListar);
        $listar = $stmtListar->fetchAll(PDO::FETCH_ASSOC);

        $saida = array_map(function ($item) {
            return [
                "idBanners" => (int)$item["idBanners"],
                "nome"      => $item["nome"] ?? "",
                "imagem"    => !empty($item["imagem"]) ? base64_encode($item["imagem"]) : null,
                "situacao"  => (int)$item["Situacao"] === 1 ? "sim" : "nao"
            ];
        }, $listar);

        header("Content-Type: application/json; charset=utf-8");
        echo json_encode(["ok" => true, "banners" => $saida], JSON_UNESCAPED_UNICODE);
        exit;

    } catch (Throwable $e) {
        header("Content-Type: application/json; charset=utf-8", true, 500);
        echo json_encode(["ok" => false, "error" => "Erro ao listar banners", "detail" => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// ========================= CADASTRO DE BANNERS ========================= //
if ($_SERVER["REQUEST_METHOD"] === "POST" && empty($_POST['acao'])) {
    try {
        $titulo = $_POST["titulo"] ?? '';
        $status = $_POST["status"] ?? '0';

        $imagem = null;
        if (isset($_FILES['img']) && $_FILES['img']['error'] === UPLOAD_ERR_OK) {
            $imagem = file_get_contents($_FILES['img']['tmp_name']);
        }

        if ($titulo === '' || $status === '' || $imagem === null) {
            redirecWith("../paginas_lojista/promocaoebanners.html", ["erro" => "Preencha todos os campos"]);
        }

        $sql = "INSERT INTO Banners (imagem, nome, Situacao) VALUES (:imagem, :nome, :situacao)";
        $inserir = $pdo->prepare($sql)->execute([
            ":imagem" => $imagem,
            ":nome" => $titulo,
            ":situacao" => (int)$status
        ]);

        if ($inserir) {
            redirecWith("../paginas_lojista/promocaoebanners.html", ["cadastro" => "ok"]);
        } else {
            redirecWith("../paginas_lojista/promocaoebanners.html", ["erro" => "Erro ao cadastrar no banco de dados"]);
        }

    } catch (\Exception $e) {
        redirecWith("../paginas_lojista/promocaoebanners.html", ["erro" => "Erro no banco de dados: ".$e->getMessage()]);
    }
}

// ============================ EXCLUSÃO ============================ //
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'excluir') {
    try {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(["ok" => false, "error" => "ID inválido para exclusão"]);
            exit;
        }

        $st = $pdo->prepare("DELETE FROM Banners WHERE idBanners = :id");
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
        $titulo = trim($_POST['titulo'] ?? '');
        $status = (int)($_POST['status'] ?? 0);

        $imagem = null;
        if (isset($_FILES['img']) && $_FILES['img']['error'] === UPLOAD_ERR_OK) {
            $imagem = file_get_contents($_FILES['img']['tmp_name']);
        }

        $setSql = "nome = :titulo, Situacao = :status";
        if ($imagem !== null) $setSql = "imagem = :imagem, " . $setSql;

        $sql = "UPDATE Banners SET $setSql WHERE idBanners = :id";
        $st = $pdo->prepare($sql);
        if ($imagem !== null) $st->bindValue(':imagem', $imagem, PDO::PARAM_LOB);
        $st->bindValue(':titulo', $titulo, PDO::PARAM_STR);
        $st->bindValue(':status', $status, PDO::PARAM_INT);
        $st->bindValue(':id', $id, PDO::PARAM_INT);
        $st->execute();

        echo json_encode(["ok" => true]);
        exit;

    } catch (\Throwable $e) {
        echo json_encode(["ok" => false, "error" => $e->getMessage()]);
        exit;
    }
}