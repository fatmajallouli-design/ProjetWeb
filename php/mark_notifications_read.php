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

$user = $_SESSION['user']['username'];
$id   = (int)($_POST['id_notif'] ?? $_GET['id_notif'] ?? 0);

if ($id > 0) {
    $stmt = $bdd->prepare('UPDATE notification SET is_read = 1 WHERE id_notif = :i AND recipient_username = :u');
    $stmt->execute(['i' => $id, 'u' => $user]);
} else {
    $stmt = $bdd->prepare('UPDATE notification SET is_read = 1 WHERE recipient_username = :u AND is_read = 0');
    $stmt->execute(['u' => $user]);
}

$cStmt = $bdd->prepare('SELECT COUNT(*) FROM notification WHERE recipient_username = :u AND is_read = 0');
$cStmt->execute(['u' => $user]);
$unread = (int)$cStmt->fetchColumn();

echo json_encode(['success' => true, 'unread_count' => $unread]);
