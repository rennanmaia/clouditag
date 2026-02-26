<?php
require_once '../includes/functions.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

$slug = isset($_POST['slug']) ? sanitize($_POST['slug']) : '';
$profile_id = isset($_POST['profile_id']) ? (int)$_POST['profile_id'] : 0;

if ($slug === '') {
    echo json_encode(['success' => true, 'available' => false, 'message' => 'Informe um slug.']);
    exit;
}

try {
    $db = getDB();
    if ($profile_id > 0) {
        $stmt = $db->prepare('SELECT COUNT(*) FROM profiles WHERE slug = ? AND id != ?');
        $stmt->execute([$slug, $profile_id]);
    } else {
        $stmt = $db->prepare('SELECT COUNT(*) FROM profiles WHERE slug = ?');
        $stmt->execute([$slug]);
    }
    $exists = $stmt->fetchColumn() > 0;

    if ($exists) {
        echo json_encode([
            'success'   => true,
            'available' => false,
            'message'   => 'Este slug já está em uso. Escolha outro.'
        ]);
    } else {
        echo json_encode([
            'success'   => true,
            'available' => true,
            'message'   => 'Este slug está disponível.'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success'   => false,
        'available' => false,
        'message'   => 'Erro ao verificar slug.'
    ]);
}
