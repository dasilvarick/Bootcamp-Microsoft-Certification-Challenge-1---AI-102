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
        tipo_pessoa TEXT,
        publicacao TEXT,
        setor TEXT,
        name TEXT,
        matricula TEXT,
        endereco TEXT,
        bairro TEXT,
        cidade TEXT,
        cep TEXT,
        email TEXT,
        telefone TEXT,
        celular TEXT,
        email_secundario TEXT,
        sigilo TEXT,
        assunto TEXT,
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

function sendEmail($protocol, $data) {
    // Sanitize email to prevent header injection (remove newlines)
    $clean_email = str_replace(array("\r", "\n"), '', $data['email']);

    $to = 'ouvidoria@aramacan.com.br';
    $subject = "Nova Manifestação de Ouvidoria: Protocolo $protocol - " . htmlspecialchars($data['assunto']);
    $headers = "From: ouvidoria@aramacan.com.br\r\n";
    $headers .= "Reply-To: " . filter_var($clean_email, FILTER_SANITIZE_EMAIL) . "\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    $body = "
        <h2>Nova Manifestação Recebida</h2>
        <p><strong>Protocolo:</strong> $protocol</p>
        <p><strong>Você é:</strong> " . htmlspecialchars($data['tipo_pessoa']) . "</p>
        <p><strong>Permite publicação (FAQ):</strong> " . htmlspecialchars($data['publicacao']) . "</p>
        <p><strong>Setor:</strong> " . htmlspecialchars($data['setor']) . "</p>
        <p><strong>Nome:</strong> " . htmlspecialchars($data['name']) . "</p>
        <p><strong>Matrícula:</strong> " . htmlspecialchars($data['matricula']) . "</p>
        <p><strong>Endereço:</strong> " . htmlspecialchars($data['endereco']) . ", <strong>Bairro:</strong> " . htmlspecialchars($data['bairro']) . ", <strong>Cidade:</strong> " . htmlspecialchars($data['cidade']) . " - <strong>CEP:</strong> " . htmlspecialchars($data['cep']) . "</p>
        <p><strong>E-mail Principal:</strong> " . htmlspecialchars($data['email']) . "</p>
        <p><strong>E-mail Secundário:</strong> " . htmlspecialchars($data['email_secundario']) . "</p>
        <p><strong>Telefone:</strong> " . htmlspecialchars($data['telefone']) . " | <strong>Celular:</strong> " . htmlspecialchars($data['celular']) . "</p>
        <p><strong>Deseja sigilo:</strong> " . htmlspecialchars($data['sigilo']) . "</p>
        <p><strong>Assunto:</strong> " . htmlspecialchars($data['assunto']) . "</p>
        <p><strong>Mensagem:</strong></p>
        <p>" . nl2br(htmlspecialchars($data['message'])) . "</p>
        <hr>
        <p>Esta é uma mensagem automática do Sistema de Ouvidoria.</p>
    ";

    @mail($to, $subject, $body, $headers);
}

// POST: Criar Manifestação
if ($method === 'POST' && $action === 'create') {
    $data = json_decode(file_get_contents('php://input'), true);

    $tipo_pessoa = $data['tipo_pessoa'] ?? '';
    $publicacao = $data['publicacao'] ?? '';
    $setor = $data['setor'] ?? '';
    $name = $data['name'] ?? '';
    $matricula = $data['matricula'] ?? '';
    $endereco = $data['endereco'] ?? '';
    $bairro = $data['bairro'] ?? '';
    $cidade = $data['cidade'] ?? '';
    $cep = $data['cep'] ?? '';
    $email = $data['email'] ?? '';
    $telefone = $data['telefone'] ?? '';
    $celular = $data['celular'] ?? '';
    $email_secundario = $data['email_secundario'] ?? '';
    $sigilo = $data['sigilo'] ?? '';
    $assunto = $data['assunto'] ?? '';
    $message = $data['message'] ?? '';

    if (empty($name) || empty($email) || empty($message) || empty($assunto) || empty($setor)) {
        http_response_code(400);
        echo json_encode(['error' => 'Campos obrigatórios estão faltando.']);
        exit;
    }

    $protocol = generateProtocol();

    try {
        $stmt = $pdo->prepare("
            INSERT INTO manifestations (
                protocol, tipo_pessoa, publicacao, setor, name, matricula, endereco, bairro, cidade, cep, email, telefone, celular, email_secundario, sigilo, assunto, message
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $protocol, $tipo_pessoa, $publicacao, $setor, $name, $matricula,
            $endereco, $bairro, $cidade, $cep, $email, $telefone, $celular,
            $email_secundario, $sigilo, $assunto, $message
        ]);

        // Dispara e-mail
        sendEmail($protocol, $data);

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
