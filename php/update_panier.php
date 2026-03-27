<?php
session_start();

if (empty($_SESSION['user']['username'])) {
    header('Location: ../html/login.php');
    exit();
}

$username = $_SESSION['user']['username'];
$action = $_POST['action'] ?? '';
$idPanier = isset($_POST['id_panier']) ? (int) $_POST['id_panier'] : 0;
$quantite = isset($_POST['quantite']) ? (int) $_POST['quantite'] : 1;

if ($idPanier <= 0) {
    $_SESSION['panier_error'] = 'Element de panier invalide.';
    header('Location: ../html/panier.php');
    exit();
}

require_once('connexionBD.php');
$bdd = ConnexionBD::getInstance();

if ($action === 'delete') {
    $stmt = $bdd->prepare('DELETE FROM panier WHERE id_panier = :id_panier AND username = :username');
    $stmt->execute([
        'id_panier' => $idPanier,
        'username' => $username
    ]);

    $_SESSION['panier_success'] = 'Produit supprime du panier.';
    header('Location: ../html/panier.php');
    exit();
}

if ($action === 'update') {
    if ($quantite < 1) {
        $_SESSION['panier_error'] = 'La quantite doit etre au moins 1.';
        header('Location: ../html/panier.php');
        exit();
    }

    $stmt = $bdd->prepare('UPDATE panier SET quantite = :quantite WHERE id_panier = :id_panier AND username = :username');
    $stmt->execute([
        'quantite' => $quantite,
        'id_panier' => $idPanier,
        'username' => $username
    ]);

    $_SESSION['panier_success'] = 'Quantite mise a jour.';
    header('Location: ../html/panier.php');
    exit();
}

$_SESSION['panier_error'] = 'Action panier invalide.';
header('Location: ../html/panier.php');
exit();
