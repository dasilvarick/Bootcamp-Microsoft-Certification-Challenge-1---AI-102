<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

$dbFile = __DIR__ . '/database.sqlite';

try {
    $pdo = new PDO("sqlite:" . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Criar tabela se não existir
    $pdo->exec("CREATE TABLE IF NOT EXISTS manifestations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        protocol TEXT UNIQUE,
        name TEXT,
        email TEXT,
        message TEXT,
        status TEXT DEFAULT 'Aberto',
        createdAt DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro na conexão com o banco de dados.']);
    exit;
}

// Roteamento Simples
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

function generateProtocol() {
    return 'ARAM-' . date('Y') . '-' . rand(1000, 9999);
}

function sendEmail($protocol, $name, $email, $message) {
    $to = 'ouvidoria@aramacan.com.br';
    $subject = "Nova Manifestação de Ouvidoria: Protocolo $protocol";
    $headers = "From: ouvidoria@aramacan.com.br\r\n";
    $headers .= "Reply-To: $email\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    $safeName = htmlspecialchars($name);
    $safeEmail = htmlspecialchars($email);

    $body = "
        <h2>Nova Manifestação Recebida</h2>
        <p><strong>Protocolo:</strong> $protocol</p>
        <p><strong>Nome:</strong> $safeName</p>
        <p><strong>E-mail:</strong> $safeEmail</p>
        <p><strong>Mensagem:</strong></p>
        <p>" . nl2br(htmlspecialchars($message)) . "</p>
        <hr>
        <p>Esta é uma mensagem automática do Sistema de Ouvidoria.</p>
    ";

    // No PHP a função mail depende de configurações do servidor (ex: sendmail).
    // Como solicitado, a lógica se mantém via mail().
    @mail($to, $subject, $body, $headers);
}

// POST: Criar Manifestação
if ($method === 'POST' && $action === 'create') {
    $data = json_decode(file_get_contents('php://input'), true);

    $name = $data['name'] ?? '';
    $email = $data['email'] ?? '';
    $message = $data['message'] ?? '';

    if (empty($name) || empty($email) || empty($message)) {
        http_response_code(400);
        echo json_encode(['error' => 'Todos os campos são obrigatórios.']);
        exit;
    }

    $protocol = generateProtocol();

    try {
        $stmt = $pdo->prepare("INSERT INTO manifestations (protocol, name, email, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$protocol, $name, $email, $message]);

        // Dispara e-mail
        sendEmail($protocol, $name, $email, $message);

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Manifestação enviada com sucesso.',
            'protocol' => $protocol
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Erro ao salvar no banco de dados.']);
    }
    exit;
}

// GET: Consultar Manifestação
if ($method === 'GET' && $action === 'status') {
    $protocol = $_GET['protocol'] ?? '';

    if (empty($protocol)) {
        http_response_code(400);
        echo json_encode(['error' => 'Protocolo não informado.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT protocol, name, status, createdAt FROM manifestations WHERE protocol = ?");
        $stmt->execute([$protocol]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            echo json_encode($row);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Protocolo não encontrado.']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Erro ao consultar banco de dados.']);
    }
    exit;
}

// Caso a rota não seja encontrada
http_response_code(404);
echo json_encode(['error' => 'Endpoint não encontrado.']);
