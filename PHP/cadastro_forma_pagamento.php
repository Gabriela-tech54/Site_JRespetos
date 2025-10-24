<?php
require_once __DIR__ . "/conexao.php";

// Função de redirecionamento
function redirecWith($url, $params = []) {
    if(!empty($params)) {
        $qs = http_build_query($params);
        $sep = (strpos($url,'?') === false) ? '?' : '&';
        $url .= $sep . $qs;
    }
    header("Location: $url");
    exit;
}

// ================= GET - Listar =================
if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["listar"])) {
    try {
        $stmt = $pdo->query("SELECT idFormas_pagamento AS id, nome FROM Formas_pagamento ORDER BY nome");
        $formas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $formato = strtolower($_GET["format"] ?? "option");

        if ($formato === "json") {
            header("Content-Type: application/json; charset=utf-8");
            echo json_encode(["ok" => true, "Formas_pagamento" => $formas], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Retorno padrão HTML
        header("Content-Type: text/html; charset=utf-8");
        foreach ($formas as $f) {
            $id = (int)$f["id"];
            $nome = htmlspecialchars($f["nome"], ENT_QUOTES, "UTF-8");
            echo "<option value=\"$id\">$nome</option>\n";
        }
        exit;

    } catch (Throwable $e) {
        header("Content-Type: application/json; charset=utf-8", true, 500);
        echo json_encode(["ok" => false, "error" => "Erro ao listar formas de pagamento", "detail" => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// ================= POST =================
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'editar') {
        $id = intval($_POST['id']);
        $nome = trim($_POST['nomepagamento']);
        $stmt = $pdo->prepare("UPDATE Formas_pagamento SET nome=:nome WHERE idFormas_pagamento=:id");
        $ok = $stmt->execute([':nome'=>$nome, ':id'=>$id]);
        echo json_encode(['ok'=>$ok]);
        exit;
    }

    if ($acao === 'excluir') {
        $id = intval($_POST['id']);
        $stmt = $pdo->prepare("DELETE FROM Formas_pagamento WHERE idFormas_pagamento=:id");
        $ok = $stmt->execute([':id'=>$id]);
        echo json_encode(['ok'=>$ok]);
        exit;
    }

    // ================= Cadastro =================
    $nomepagamento = $_POST['nomepagamento'] ?? '';
    if(trim($nomepagamento) === '') {
        redirecWith("../paginas_lojista/fretepagamentolojista.html", ["erro"=> "Preencha o campo nome"]);
    }

    $stmt = $pdo->prepare("INSERT INTO Formas_pagamento (nome) VALUES (:nome)");
    $ok = $stmt->execute([':nome' => $nomepagamento]);

    if ($ok) {
        redirecWith("../paginas_lojista/fretepagamentolojista.html", ["cadastro"=>"ok"]);
    } else {
        redirecWith("../paginas_lojista/fretepagamentolojista.html", ["erro"=>"Erro ao cadastrar"]);
    }
}
