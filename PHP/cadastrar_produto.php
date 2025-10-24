<?php
require_once __DIR__ . "/conexao.php";

// Função auxiliar para retorno JSON e encerrar
function jsonResponse($ok, $data = []) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['ok' => $ok], $data), JSON_UNESCAPED_UNICODE);
    exit;
}

// ====================== LISTAR PRODUTOS ======================
if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["listar"])) {
    try {
        $sqlListar = "
         SELECT 
                p.idProdutos, 
                p.nome, 
                p.descricao, 
                p.quantidade, 
                p.preco, 
                p.situacao,
                ip.foto AS imagem_blob
            FROM Produtos p
            LEFT JOIN Produtos_e_Imagens_produtos pip ON pip.Produtos_idProdutos = p.idProdutos
            LEFT JOIN Imagens_produtos ip ON ip.idImagens_produtos = pip.Imagens_produtos_idImagens_produtos
            GROUP BY p.idProdutos
            ORDER BY p.idProdutos DESC
        ";
        $stmtListar = $pdo->query($sqlListar);
        $listar = $stmtListar->fetchAll(PDO::FETCH_ASSOC);

        $saida = array_map(function ($item) {
            return [
                "idProduto" => (int)$item["idProdutos"],
                "nome"      => $item["nome"] ?? "",
                "descricao" => $item["descricao"] ?? "",
                "quantidade"=> (int)($item["quantidade"] ?? 0),
                "preco"     => (float)($item["preco"] ?? 0),
                "situacao"  => !empty($item["situacao"]) ? "1" : "0",
                "imagem"    => !empty($item["imagem_blob"]) ? base64_encode($item["imagem_blob"]) : null
            ];
        }, $listar);

        jsonResponse(true, ['produtos' => $saida]);

    } catch (Throwable $e) {
        jsonResponse(false, ['error' => "Erro ao listar produtos: ".$e->getMessage()]);
    }
}

// ====================== CADASTRAR PRODUTO ======================
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST['acao'] ?? '') === 'cadastrar') {
    try {
        $nome = trim($_POST["nome"] ?? '');
        $descricao = trim($_POST["descricao"] ?? '');
        $quantidade = (int)($_POST["quantidade"] ?? 0);
        $preco = (float)($_POST["preco"] ?? 0);
        $situacao = isset($_POST["situacao"]) ? (int)$_POST["situacao"] : 1;

        if ($nome === '' || $descricao === '' || $quantidade <= 0 || $preco <= 0) {
            jsonResponse(false, ['error' => 'Preencha os campos obrigatórios.']);
        }

        $pdo->beginTransaction();

        // Inserir produto
        $sqlProdutos = "INSERT INTO Produtos (nome, descricao, quantidade, preco, situacao) 
                        VALUES (:nome, :descricao, :quantidade, :preco, :situacao)";
        $stmProdutos = $pdo->prepare($sqlProdutos);
        $stmProdutos->execute([
            ":nome" => $nome,
            ":descricao" => $descricao,
            ":quantidade" => $quantidade,
            ":preco" => $preco,
            ":situacao" => $situacao
        ]);
        $idproduto = (int)$pdo->lastInsertId();

        // Inserir imagem se houver
        if (!empty($_FILES['imagem']['tmp_name'])) {
            $img = file_get_contents($_FILES['imagem']['tmp_name']);
            $sqlImg = "INSERT INTO Imagens_produtos(foto) VALUES (:foto)";
            $stmImg = $pdo->prepare($sqlImg);
            $stmImg->bindParam(':foto', $img, PDO::PARAM_LOB);
            $stmImg->execute();
            $idImg = (int)$pdo->lastInsertId();

            $sqlVincular = "INSERT INTO Produtos_e_Imagens_produtos 
                             (Produtos_idProdutos, Imagens_produtos_idImagens_produtos) 
                             VALUES (:idpro, :idimg)";
            $stmVincular = $pdo->prepare($sqlVincular);
            $stmVincular->execute([':idpro'=>$idproduto, ':idimg'=>$idImg]);
        }

        $pdo->commit();
        jsonResponse(true);

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        jsonResponse(false, ['error' => "Erro ao cadastrar: ".$e->getMessage()]);
    }
}

// ====================== ATUALIZAR PRODUTO ======================
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST['acao'] ?? '') === 'atualizar') {
    try {
        $id = (int)($_POST['id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $quantidade = (int)($_POST['quantidade'] ?? 0);
        $preco = (float)($_POST['preco'] ?? 0);
        $situacao = isset($_POST['situacao']) ? (int)$_POST['situacao'] : 1;

        if ($id <= 0) jsonResponse(false, ['error'=>'ID inválido para edição']);
        if ($nome === '' || $descricao === '' || $quantidade < 0 || $preco < 0) {
            jsonResponse(false, ['error'=>'Preencha todos os campos corretamente.']);
        }

        $sql = "UPDATE Produtos 
                SET nome=:nome, descricao=:descricao, quantidade=:quantidade, preco=:preco, situacao=:situacao 
                WHERE idProdutos=:id";
        $st = $pdo->prepare($sql);
        $st->execute([
            ':nome'=>$nome,
            ':descricao'=>$descricao,
            ':quantidade'=>$quantidade,
            ':preco'=>$preco,
            ':situacao'=>$situacao,
            ':id'=>$id
        ]);

        jsonResponse(true);

    } catch (Throwable $e) {
        jsonResponse(false, ['error'=>"Erro ao atualizar: ".$e->getMessage()]);
    }
}

// ====================== EXCLUIR PRODUTO ======================
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST['acao'] ?? '') === 'excluir') {
    try {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) jsonResponse(false, ['error'=>'ID inválido para exclusão']);

        $stmt = $pdo->prepare("DELETE FROM Produtos WHERE idProdutos=:id");
        $stmt->execute([':id'=>$id]);

        jsonResponse(true);

    } catch (Throwable $e) {
        jsonResponse(false, ['error'=>"Erro ao excluir: ".$e->getMessage()]);
    }
}

