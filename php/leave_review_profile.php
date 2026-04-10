<?php
session_start();
if (empty($_SESSION['user']['username']) || (($_SESSION['user']['role'] ?? '') !== 'client')) {
    header('Location: /login.php');
    exit();
}

require_once(__DIR__ . "/connexionBD.php");
$bdd = ConnexionBD::getInstance();
ConnexionBD::ensureWorkflowTables();

$client = $_SESSION['user']['username'];
$vendeur = trim($_POST['vendeur_username'] ?? '');
$idDeal = (int)($_POST['id_deal'] ?? 0);
$rating = (int)($_POST['rating'] ?? 0);
$commentaire = trim($_POST['commentaire'] ?? '');

if ($vendeur === '' || $idDeal <= 0 || $rating < 1 || $rating > 5) {
    header('Location: /html/vendor_profile_client.php?vendeur=' . urlencode($vendeur));
    exit();
}

$exists = $bdd->prepare("SELECT username FROM vendeur WHERE username = :u");
$exists->execute(['u' => $vendeur]);
if (!$exists->fetch(PDO::FETCH_ASSOC)) {
    header('Location: /login.php');
    exit();
}

$dealCheck = $bdd->prepare("
    SELECT id_deal
    FROM deal_request
    WHERE id_deal = :id
      AND client_username = :client
      AND vendeur_username = :vendeur
      AND status = 'accepte'
");
$dealCheck->execute([
    'id' => $idDeal,
    'client' => $client,
    'vendeur' => $vendeur
]);

if (!$dealCheck->fetch(PDO::FETCH_ASSOC)) {
    header('Location: /html/vendor_profile_client.php?vendeur=' . urlencode($vendeur));
    exit();
}

$dup = $bdd->prepare("
    SELECT id_review
    FROM review
    WHERE id_deal = :id
    AND client_username = :client
    LIMIT 1
");
$dup->execute([
    'id' => $idDeal,
    'client' => $client
]);

if ($dup->fetch(PDO::FETCH_ASSOC)) {
    header('Location: /html/vendor_profile_client.php?vendeur=' . urlencode($vendeur));
    exit();
}

$ins = $bdd->prepare("
    INSERT INTO review (id_deal, client_username, vendeur_username, rating, commentaire)
    VALUES (:id, :c, :v, :r, :m)
");
$ins->execute([
    'id' => $idDeal,
    'c' => $client,
    'v' => $vendeur,
    'r' => $rating,
    'm' => $commentaire
]);

header('Location: /html/vendor_profile_client.php?vendeur=' . urlencode($vendeur));
exit();
?>