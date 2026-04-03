<?php
session_start();
if (empty($_SESSION['user']['username']) || (($_SESSION['user']['role'] ?? '') !== 'vendeur')) {
    header('Location: /login.php');
    exit();
}

require_once(__DIR__ . "/connexionBD.php");
$bdd = ConnexionBD::getInstance();
ConnexionBD::ensureWorkflowTables();

$idDemande = (int)($_POST['id_demande'] ?? 0);
$prix = (float)($_POST['prix_propose'] ?? 0);
$message = trim($_POST['message'] ?? '');
$vendeur = $_SESSION['user']['username'];

if ($idDemande <= 0 || $prix <= 0 || $message === '') {
    header('Location: /php/page_vendeur.php');
    exit();
}

$q = $bdd->prepare("SELECT username FROM demande WHERE id_demande = :id");
$q->execute(['id' => $idDemande]);
$row = $q->fetch(PDO::FETCH_ASSOC);
if (!$row) {
    header('Location: /php/page_vendeur.php');
    exit();
}

$ins = $bdd->prepare("INSERT INTO deal_request (id_demande, client_username, vendeur_username, prix_propose, message, status) VALUES (:id, :c, :v, :p, :m, 'en attente')");
$ins->execute([
    'id' => $idDemande,
    'c' => $row['username'],
    'v' => $vendeur,
    'p' => $prix,
    'm' => $message
]);

header('Location: /vendor_offers.php');
exit();
?>


