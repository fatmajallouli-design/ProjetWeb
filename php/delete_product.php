<?php
session_start();
if (empty($_SESSION['user']['username']) || (($_SESSION['user']['role'] ?? '') !== 'vendeur')) {
    header('Location: ../html/login.php');
    exit();
}

require_once('connexionBD.php');
$bdd = ConnexionBD::getInstance();
ConnexionBD::ensureWorkflowTables();

$vendeur = $_SESSION['user']['username'];
$idProduit = isset($_POST['id_produit']) ? (int) $_POST['id_produit'] : 0;

if ($idProduit <= 0) {
    $_SESSION['product_error'] = 'Produit invalide.';
    header('Location: ../php/page_vendeur.php');
    exit();
}

$check = $bdd->prepare('SELECT id_produit FROM produit WHERE id_produit = :id AND vendeur_username = :vendeur');
$check->execute(['id' => $idProduit, 'vendeur' => $vendeur]);
$product = $check->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    $_SESSION['product_error'] = 'Produit non trouvé ou accès refusé.';
    header('Location: ../php/page_vendeur.php');
    exit();
}

$del = $bdd->prepare('DELETE FROM produit WHERE id_produit = :id');
$del->execute(['id' => $idProduit]);

$_SESSION['product_success'] = 'Produit supprimé avec succès.';
header('Location: ../php/page_vendeur.php');
exit();
