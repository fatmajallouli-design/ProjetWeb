<?php
session_start();
if (empty($_SESSION['user']['username'])) {
    header('Location: ../html/login.php');
    exit();
}
require_once('connexionBD.php');
$bdd = ConnexionBD::getInstance();
ConnexionBD::ensureWorkflowTables();

$idDeal = (int)($_POST['id_deal'] ?? 0);
$contenu = trim($_POST['contenu'] ?? '');
$sender = $_SESSION['user']['username'];
if ($idDeal <= 0 || $contenu === '') {
    header('Location: ../html/messages.php?deal=' . $idDeal);
    exit();
}

$q = $bdd->prepare("SELECT client_username, vendeur_username FROM deal_request WHERE id_deal = :id");
$q->execute(['id' => $idDeal]);
$row = $q->fetch(PDO::FETCH_ASSOC);
if (!$row) {
    header('Location: ../html/messages.php');
    exit();
}
if ($sender !== $row['client_username'] && $sender !== $row['vendeur_username']) {
    header('Location: ../html/login.php');
    exit();
}
$receiver = ($sender === $row['client_username']) ? $row['vendeur_username'] : $row['client_username'];

$ins = $bdd->prepare("INSERT INTO message (id_deal, sender_username, receiver_username, contenu) VALUES (:id, :s, :r, :c)");
$ins->execute(['id' => $idDeal, 's' => $sender, 'r' => $receiver, 'c' => $contenu]);
header('Location: ../html/messages.php?deal=' . $idDeal);
exit();
?>
