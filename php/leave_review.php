<?php
session_start();
if (empty($_SESSION['user']['username']) || (($_SESSION['user']['role'] ?? '') !== 'client')) {
    header('Location: ../html/login.php');
    exit();
}
require_once('connexionBD.php');
$bdd = ConnexionBD::getInstance();
ConnexionBD::ensureWorkflowTables();

$idDeal = (int)($_POST['id_deal'] ?? 0);
$rating = (int)($_POST['rating'] ?? 0);
$commentaire = trim($_POST['commentaire'] ?? '');
$client = $_SESSION['user']['username'];

if ($idDeal <= 0 || $rating < 1 || $rating > 5) {
    header('Location: ../html/messages.php?deal=' . $idDeal);
    exit();
}

$q = $bdd->prepare("SELECT client_username, vendeur_username FROM deal_request WHERE id_deal = :id");
$q->execute(['id' => $idDeal]);
$row = $q->fetch(PDO::FETCH_ASSOC);
if (!$row || $row['client_username'] !== $client) {
    header('Location: ../html/messages.php');
    exit();
}

$ins = $bdd->prepare("INSERT INTO review (id_deal, client_username, vendeur_username, rating, commentaire) VALUES (:id, :c, :v, :r, :m)");
$ins->execute([
    'id' => $idDeal,
    'c' => $client,
    'v' => $row['vendeur_username'],
    'r' => $rating,
    'm' => $commentaire
]);
header('Location: ../html/messages.php?deal=' . $idDeal);
exit();
?>
