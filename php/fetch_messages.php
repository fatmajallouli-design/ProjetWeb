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

$user   = $_SESSION['user']['username'];
$deal   = (int)($_GET['deal'] ?? 0);
$thread = trim($_GET['thread'] ?? '');
$after  = (int)($_GET['after'] ?? 0);

$messages = [];

if ($deal > 0) {
    $d = $bdd->prepare('SELECT client_username, vendeur_username FROM deal_request WHERE id_deal = :id');
    $d->execute(['id' => $deal]);
    $row = $d->fetch(PDO::FETCH_ASSOC);
    if (!$row || ($user !== $row['client_username'] && $user !== $row['vendeur_username'])) {
        echo json_encode(['success' => false, 'message' => 'Forbidden']);
        exit();
    }
    $m = $bdd->prepare('SELECT id_message, id_deal, sender_username, receiver_username, contenu, created_at
        FROM message
        WHERE id_deal = :id AND (thread_key IS NULL OR thread_key = "") AND id_message > :after
        ORDER BY id_message ASC');
    $m->execute(['id' => $deal, 'after' => $after]);
    $messages = $m->fetchAll(PDO::FETCH_ASSOC);

    $bdd->prepare('UPDATE message SET is_read = 1 WHERE id_deal = :id AND receiver_username = :u AND is_read = 0 AND (thread_key IS NULL OR thread_key = "")')
        ->execute(['id' => $deal, 'u' => $user]);
} elseif ($thread !== '') {
    if (!preg_match('/^direct:([^|]+)\|(.+)$/', $thread, $tm)) {
        echo json_encode(['success' => false, 'message' => 'Bad thread']);
        exit();
    }
    if ($user !== $tm[1] && $user !== $tm[2]) {
        echo json_encode(['success' => false, 'message' => 'Forbidden']);
        exit();
    }
    $m = $bdd->prepare('SELECT id_message, id_deal, thread_key, sender_username, receiver_username, contenu, created_at
        FROM message
        WHERE thread_key = :tk AND id_message > :after
        ORDER BY id_message ASC');
    $m->execute(['tk' => $thread, 'after' => $after]);
    $messages = $m->fetchAll(PDO::FETCH_ASSOC);

    $bdd->prepare('UPDATE message SET is_read = 1 WHERE thread_key = :tk AND receiver_username = :u AND is_read = 0')
        ->execute(['tk' => $thread, 'u' => $user]);
} else {
    echo json_encode(['success' => false, 'message' => 'Missing deal or thread']);
    exit();
}

echo json_encode(['success' => true, 'messages' => $messages]);
