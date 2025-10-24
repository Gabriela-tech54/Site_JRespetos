<?php
require_once __DIR__ . "/conexao.php";

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
        $stmt = $pdo->query("SELECT idFrete AS id, bairro, valor, transportadora FROM Frete ORDER BY bairro, valor");
        $fretes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $formato = strtolower($_GET["format"] ?? "option");

        if ($formato === "json") {
            $saida = array_map(fn($f) => [
                "id" => (int)$f["id"],
                "bairro" => $f["bairro"],
                "valor" => (float)$f["valor"],
                "transportadora" => $f["transportadora"]
            ], $fretes);
            header("Content-Type: application/json; charset=utf-8");
            echo json_encode(["ok"=>true, "fretes"=>$saida], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Retorno padrão HTML
        header("Content-Type: text/html; charset=utf-8");
        foreach ($fretes as $f) {
            $id = (int)$f["id"];
            $bairro = htmlspecialchars($f["bairro"], ENT_QUOTES, "UTF-8");
            $transp = $f["transportadora"] ? " (" . htmlspecialchars($f["transportadora"], ENT_QUOTES, "UTF-8") . ")" : "";
            $valorFmt = number_format((float)$f["valor"], 2, ",", ".");
            echo "<option value=\"$id\">{$bairro}{$transp} - R$ {$valorFmt}</option>\n";
        }
        exit;

    } catch (Throwable $e) {
        header("Content-Type: application/json; charset=utf-8", true, 500);
        echo json_encode(["ok"=>false, "error"=>"Erro ao listar fretes", "detail"=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// ================= POST =================
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'editar') {
        $stmt = $pdo->prepare("UPDATE Frete SET bairro=:bairro, valor=:valor, transportadora=:transp WHERE idFrete=:id");
        $ok = $stmt->execute([
            ':bairro'=>$_POST['bairro'],
            ':valor'=>$_POST['valor'],
            ':transp'=>$_POST['transportadora'],
            ':id'=>intval($_POST['id'])
        ]);
        echo json_encode(['ok'=>$ok]);
        exit;
    }

    if ($acao === 'excluir') {
        $stmt = $pdo->prepare("DELETE FROM Frete WHERE idFrete=:id");
        $ok = $stmt->execute([':id'=>intval($_POST['id'])]);
        echo json_encode(['ok'=>$ok]);
        exit;
    }

    // ================= Cadastro =================
    $bairro = $_POST['bairro'] ?? '';
    $valor = $_POST['valor'] ?? '';
    $transportadora = $_POST['transportadora'] ?? '';

    if(trim($bairro)==='' || trim($valor)==='' || trim($transportadora)==='') {
        redirecWith("../paginas_lojista/fretepagamentolojista.html", ["erro"=>"Preencha todos os campos"]);
    }

    $stmt = $pdo->prepare("INSERT INTO Frete (bairro, valor, transportadora) VALUES (:bairro, :valor, :transp)");
    $ok = $stmt->execute([':bairro'=>$bairro, ':valor'=>$valor, ':transp'=>$transportadora]);

    if($ok) {
        redirecWith("../paginas_lojista/fretepagamentolojista.html", ["cadastro"=>"ok"]);
    } else {
        redirecWith("../paginas_lojista/fretepagamentolojista.html", ["erro"=>"Erro ao cadastrar"]);
    }
}
