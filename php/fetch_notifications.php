<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['user']['username'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}
require_once(__DIR__ . '/connexionBD.php');
$bdd = ConnexionBD::getInstance();
ConnexionBD::ensureWorkflowTables();

$user  = $_SESSION['user']['username'];
$limit = max(1, min(50, (int)($_GET['limit'] ?? 20)));
$unreadOnly = !empty($_GET['unread_only']);

$sql = 'SELECT id_notif, type, title, body, link, actor_username, related_id, is_read, created_at
        FROM notification
        WHERE recipient_username = :u';
if ($unreadOnly) {
    $sql .= ' AND is_read = 0';
}
$sql .= ' ORDER BY created_at DESC, id_notif DESC LIMIT ' . $limit;

$stmt = $bdd->prepare($sql);
$stmt->execute(['u' => $user]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$cStmt = $bdd->prepare('SELECT COUNT(*) FROM notification WHERE recipient_username = :u AND is_read = 0');
$cStmt->execute(['u' => $user]);
$unread = (int)$cStmt->fetchColumn();

$mStmt = $bdd->prepare('SELECT COUNT(*) FROM message WHERE receiver_username = :u AND is_read = 0');
$mStmt->execute(['u' => $user]);
$unreadMessages = (int)$mStmt->fetchColumn();

echo json_encode([
    'success' => true,
    'notifications' => $rows,
    'unread_count' => $unread,
    'unread_messages' => $unreadMessages,
]);
