<?php
session_start();
if (empty($_SESSION['user']['username']) || (($_SESSION['user']['role'] ?? '') !== 'client')) {
    header('Location: /login.php');
    exit();
}
require_once(__DIR__ . "/connexionBD.php");
$bdd = ConnexionBD::getInstance();
ConnexionBD::ensureWorkflowTables();

$idProduit = (int)($_POST['id_produit'] ?? 0);
if ($idProduit <= 0) {
    header('Location: /login.php');
    exit();
}

$p = $bdd->prepare("SELECT * FROM produit WHERE id_produit = :id");
$p->execute(['id' => $idProduit]);
$prod = $p->fetch(PDO::FETCH_ASSOC);
if (!$prod) {
    header('Location: /login.php');
    exit();
}

$ins = $bdd->prepare("INSERT INTO demande (nom_produit, prix, lien_produit, description, categorie, id_photo, username, etat) VALUES (:n, :p, :l, :d, :c, :i, :u, 'en attente')");
$ins->execute([
    'n' => $prod['nom_produit'],
    'p' => $prod['prix'],
    'l' => '',
    'd' => 'Demande creee depuis produit vendeur',
    'c' => $prod['categorie'],
    'i' => $prod['image_path'],
    'u' => $_SESSION['user']['username']
]);

header('Location: /mes_demandes.php');
exit();
?>


