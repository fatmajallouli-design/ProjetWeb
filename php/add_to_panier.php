<?php
session_start();

$isAjax = (
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest' ||
    stripos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false
);

function respondPanier($payload, $isAjax)
{
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode($payload);
        exit();
    }
}

if (empty($_SESSION['user']['username'])) {
    respondPanier([
        'success' => false,
        'redirect' => '/login.php',
        'message' => 'Veuillez vous connecter.'
    ], $isAjax);
    header('Location: /login.php');
    exit();
}

if (($_SESSION['user']['role'] ?? 'client') !== 'client') {
    respondPanier([
        'success' => false,
        'redirect' => '/index.php',
        'message' => 'Action reservee au client.'
    ], $isAjax);
    header('Location: /index.php');
    exit();
}

$idProduit = isset($_POST['id_produit']) ? (int) $_POST['id_produit'] : 0;
$redirectTo = trim($_POST['redirect_to'] ?? '/panier.php');

if ($redirectTo === '' || preg_match('/^https?:/i', $redirectTo)) {
    $redirectTo = '/panier.php';
}

if ($idProduit <= 0) {
    respondPanier([
        'success' => false,
        'message' => 'Produit invalide.'
    ], $isAjax);
    header('Location: ' . $redirectTo);
    exit();
}

require_once(__DIR__ . "/connexionBD.php");
$bdd = ConnexionBD::getInstance();
$username = $_SESSION['user']['username'];

$productStmt = $bdd->prepare('SELECT quantite FROM produit WHERE id_produit = :id_produit');
$productStmt->execute(['id_produit' => $idProduit]);
$product = $productStmt->fetch(PDO::FETCH_ASSOC);

if (!$product || (int)$product['quantite'] < 1) {
    respondPanier([
        'success' => false,
        'message' => 'Produit en rupture de stock.',
        'redirect' => $redirectTo
    ], $isAjax);
    header('Location: ' . $redirectTo);
    exit();
}

$checkStmt = $bdd->prepare('SELECT id_panier, quantite FROM panier WHERE username = :username AND id_produit = :id_produit');
$checkStmt->execute([
    'username' => $username,
    'id_produit' => $idProduit
]);
$existingItem = $checkStmt->fetch(PDO::FETCH_ASSOC);

if ($existingItem) {
    $newQuantite = (int)$existingItem['quantite'] + 1;
    if ($newQuantite > (int)$product['quantite']) {
        respondPanier([
            'success' => false,
            'message' => 'Quantité demandée supérieure au stock disponible.',
            'redirect' => $redirectTo
        ], $isAjax);
        header('Location: ' . $redirectTo);
        exit();
    }

    $updateStmt = $bdd->prepare('UPDATE panier SET quantite = :quantite WHERE id_panier = :id_panier');
    $updateStmt->execute(['quantite' => $newQuantite, 'id_panier' => $existingItem['id_panier']]);
} else {
    $insertStmt = $bdd->prepare('INSERT INTO panier (username, id_produit, quantite) VALUES (:username, :id_produit, 1)');
    $insertStmt->execute([
        'username' => $username,
        'id_produit' => $idProduit
    ]);
}

respondPanier([
    'success' => true,
    'message' => 'Produit ajoute dans le panier.',
    'redirect' => $redirectTo
], $isAjax);

header('Location: ' . $redirectTo);
exit();



