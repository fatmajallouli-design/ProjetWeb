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
        'redirect' => '../html/login.php',
        'message' => 'Veuillez vous connecter.'
    ], $isAjax);
    header('Location: ../html/login.php');
    exit();
}

if (($_SESSION['user']['role'] ?? 'client') !== 'client') {
    respondPanier([
        'success' => false,
        'redirect' => '../html/index.php',
        'message' => 'Action reservee au client.'
    ], $isAjax);
    header('Location: ../html/index.php');
    exit();
}

$idProduit = isset($_POST['id_produit']) ? (int) $_POST['id_produit'] : 0;
$redirectTo = trim($_POST['redirect_to'] ?? '../html/panier.php');

if ($redirectTo === '' || preg_match('/^https?:/i', $redirectTo)) {
    $redirectTo = '../html/panier.php';
}

if ($idProduit <= 0) {
    respondPanier([
        'success' => false,
        'message' => 'Produit invalide.'
    ], $isAjax);
    header('Location: ' . $redirectTo);
    exit();
}

require_once('connexionBD.php');
$bdd = ConnexionBD::getInstance();
$username = $_SESSION['user']['username'];

$checkStmt = $bdd->prepare('SELECT id_panier, quantite FROM panier WHERE username = :username AND id_produit = :id_produit');
$checkStmt->execute([
    'username' => $username,
    'id_produit' => $idProduit
]);
$existingItem = $checkStmt->fetch(PDO::FETCH_ASSOC);

if ($existingItem) {
    $updateStmt = $bdd->prepare('UPDATE panier SET quantite = quantite + 1 WHERE id_panier = :id_panier');
    $updateStmt->execute(['id_panier' => $existingItem['id_panier']]);
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
