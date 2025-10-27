<?php
// Conectando ao banco de dados
require_once __DIR__ . "/conexao.php";

// Função para redirecionamento com parâmetros
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
    // Verifica se o método de envio é POST
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        redirecWith("../PAGINAS_CLIENTE/login.html", ["erro" => "Método inválido"]);
    }

    // Capturando dados do formulário
    $cpf = $_POST["cpf"];
    $senha = $_POST["senha"];

    // Validando campos
    if (empty($cpf) || empty($senha)) {
        redirecWith("../PAGINAS_CLIENTE/login.html", ["erro" => "Preencha todos os campos"]);
    }

    // Consulta ao banco para encontrar o cliente pelo CPF
    $stmt = $pdo->prepare("SELECT * FROM Cliente WHERE cpf = :cpf LIMIT 1");
    $stmt->execute([':cpf' => $cpf]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) {
        // CPF não encontrado
        redirecWith("../PAGINAS_CLIENTE/login.html", ["erro" => "CPF ou senha incorretos"]);
    }

    // Verifica a senha
    if ($senha !== $cliente['senha']) {
        // Caso futuramente use password_hash:
        // if (!password_verify($senha, $cliente['senha'])) { ...
        redirecWith("../PAGINAS_CLIENTE/login.html", ["erro" => "CPF ou senha incorretos"]);
    }

    // Login bem-sucedido, iniciar sessão
    session_start();
    $_SESSION['idCliente'] = $cliente['idCliente'];
    $_SESSION['nome'] = $cliente['nome'];
    $_SESSION['cpf'] = $cliente['cpf'];

    // Redirecionar para a página inicial do cliente
   redirecWith("../PAGINAS_CLIENTE/telainicial.html", ["login" => "ok"]);

} catch (PDOException $e) {
    redirecWith("../PAGINAS_CLIENTE/login.html", ["erro" => "Erro no banco de dados: " . $e->getMessage()]);
}


