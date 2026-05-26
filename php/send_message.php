<?php
session_start();
if (empty($_SESSION['user']['username'])) {
    header('Location: /login.php');
    exit();
}
require_once(__DIR__ . '/connexionBD.php');
$bdd = ConnexionBD::getInstance();
ConnexionBD::ensureWorkflowTables();

$idDeal     = (int)($_POST['id_deal'] ?? 0);
$threadKey  = trim($_POST['thread'] ?? '');
$contenu    = trim($_POST['contenu'] ?? '');
$sender     = $_SESSION['user']['username'];
$senderRole = $_SESSION['user']['role'] ?? 'client';

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

function respond($success, $payload = [], $message = '') {
    global $isAjax, $idDeal, $threadKey;
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(array_merge(['success' => $success, 'message' => $message], $payload));
        exit();
    }
    if ($threadKey !== '') {
        header('Location: /html/messages.php?thread=' . urlencode($threadKey));
    } else {
        header('Location: /html/messages.php?deal=' . $idDeal);
    }
    exit();
}

if ($contenu === '' || ($idDeal <= 0 && $threadKey === '')) {
    respond(false, [], 'Message ou destinataire manquant.');
}

$receiver = null;
$resolvedThread = null;

if ($idDeal > 0) {
    $q = $bdd->prepare('SELECT client_username, vendeur_username FROM deal_request WHERE id_deal = :id');
    $q->execute(['id' => $idDeal]);
    $row = $q->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        respond(false, [], 'Conversation introuvable.');
    }
    if ($sender !== $row['client_username'] && $sender !== $row['vendeur_username']) {
        respond(false, [], 'Acces refuse.');
    }
    $receiver = ($sender === $row['client_username']) ? $row['vendeur_username'] : $row['client_username'];
} else {
    // direct thread "direct:client|vendeur"
    if (!preg_match('/^direct:([^|]+)\|(.+)$/', $threadKey, $m)) {
        respond(false, [], 'Thread invalide.');
    }
    $client  = $m[1];
    $vendeur = $m[2];
    if ($sender !== $client && $sender !== $vendeur) {
        respond(false, [], 'Acces refuse.');
    }
    $receiver = ($sender === $client) ? $vendeur : $client;
    $resolvedThread = $threadKey;
    // verify receiver exists
    $rc = $bdd->prepare('SELECT 1 FROM client WHERE username = :u UNION SELECT 1 FROM vendeur WHERE username = :u');
    $rc->execute(['u' => $receiver]);
    if (!$rc->fetchColumn()) {
        respond(false, [], 'Destinataire introuvable.');
    }
}

$ins = $bdd->prepare('INSERT INTO message (id_deal, thread_key, sender_username, receiver_username, contenu) VALUES (:id, :tk, :s, :r, :c)');
$ins->execute([
    'id' => $idDeal > 0 ? $idDeal : null,
    'tk' => $resolvedThread,
    's'  => $sender,
    'r'  => $receiver,
    'c'  => $contenu,
]);
$lastId = (int)$bdd->lastInsertId();

// Notify the receiver
$recipientRole = $senderRole === 'client' ? 'vendeur' : 'client';
$preview = mb_substr($contenu, 0, 80);
$link = $resolvedThread !== null
    ? '/html/messages.php?thread=' . urlencode($resolvedThread)
    : '/html/messages.php?deal=' . $idDeal;
ConnexionBD::pushNotification([
    'recipient_username' => $receiver,
    'recipient_role'     => $recipientRole,
    'type'               => 'new_message',
    'title'              => 'Nouveau message de ' . $sender,
    'body'               => $preview,
    'link'               => $link,
    'actor_username'     => $sender,
    'related_id'         => $lastId,
]);

$msgData = [
    'id_message'        => $lastId,
    'id_deal'           => $idDeal,
    'thread_key'        => $resolvedThread,
    'sender_username'   => $sender,
    'receiver_username' => $receiver,
    'contenu'           => $contenu,
    'created_at'        => date('Y-m-d H:i:s'),
];

respond(true, ['message_data' => $msgData], 'Message envoye avec succes.');
