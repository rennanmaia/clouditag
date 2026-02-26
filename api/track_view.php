<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['profile_id']) || !isset($input['type'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

$profile_id = (int)$input['profile_id'];
$type = sanitize($input['type']);
$link = isset($input['link']) ? sanitize($input['link']) : null;

// Verificar se o perfil existe
$db = getDB();
$stmt = $db->prepare("SELECT COUNT(*) FROM profiles WHERE id = ?");
$stmt->execute([$profile_id]);

if ($stmt->fetchColumn() == 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Profile not found']);
    exit;
}

// Criar tabela de analytics se não existir
$db->exec("
    CREATE TABLE IF NOT EXISTS profile_analytics (
        id INT AUTO_INCREMENT PRIMARY KEY,
        profile_id INT NOT NULL,
        type ENUM('view', 'click') NOT NULL,
        link_name VARCHAR(255) DEFAULT NULL,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE CASCADE,
        INDEX idx_profile_created (profile_id, created_at),
        INDEX idx_type_created (type, created_at)
    )
");

// Obter informações do visitante
$ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

// Limitar registros por IP para evitar spam (1 view por IP por dia)
if ($type === 'view') {
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM profile_analytics 
        WHERE profile_id = ? AND type = 'view' AND ip_address = ? AND DATE(created_at) = CURDATE()
    ");
    $stmt->execute([$profile_id, $ip_address]);
    
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => true, 'message' => 'Already tracked today']);
        exit;
    }
}

// Inserir registro de analytics
try {
    $stmt = $db->prepare("
        INSERT INTO profile_analytics (profile_id, type, link_name, ip_address, user_agent) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([$profile_id, $type, $link, $ip_address, $user_agent]);
    
    echo json_encode(['success' => true, 'message' => 'Tracked successfully']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?>