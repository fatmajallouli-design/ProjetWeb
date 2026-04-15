<?php
session_start();
if (empty($_SESSION['user']['username'])) {
    header('Location: /login.php');
    exit();
}
require_once(__DIR__ . "/connexionBD.php");
$bdd = ConnexionBD::getInstance();
ConnexionBD::ensureWorkflowTables();

$idDeal = (int)($_POST['id_deal'] ?? 0);
$contenu = trim($_POST['contenu'] ?? '');
$sender = $_SESSION['user']['username'];
if ($idDeal <= 0 || $contenu === '') {
    header('Location: /messages.php?deal=' . $idDeal);
    exit();
}

$q = $bdd->prepare("SELECT client_username, vendeur_username FROM deal_request WHERE id_deal = :id");
$q->execute(['id' => $idDeal]);
$row = $q->fetch(PDO::FETCH_ASSOC);
if (!$row) {
    header('Location: /messages.php');
    exit();
}
if ($sender !== $row['client_username'] && $sender !== $row['vendeur_username']) {
    header('Location: /login.php');
    exit();
}
$receiver = ($sender === $row['client_username']) ? $row['vendeur_username'] : $row['client_username'];

$ins = $bdd->prepare("INSERT INTO message (id_deal, sender_username, receiver_username, contenu) VALUES (:id, :s, :r, :c)");
$ins->execute(['id' => $idDeal, 's' => $sender, 'r' => $receiver, 'c' => $contenu]);

if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    $lastId = $bdd->lastInsertId();
    $msgData = [
        'id_message' => $lastId,
        'id_deal' => $idDeal,
        'sender_username' => $sender,
        'receiver_username' => $receiver,
        'contenu' => $contenu,
        'created_at' => date('Y-m-d H:i:s')
    ];
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message_data' => $msgData,
        'message' => 'Message envoye avec succes.'
    ]);
    exit();
}

header('Location: /messages.php?deal=' . $idDeal);
exit();
?>


