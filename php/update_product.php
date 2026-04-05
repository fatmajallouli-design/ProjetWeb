<?php
session_start();
if (empty($_SESSION['user']['username']) || (($_SESSION['user']['role'] ?? '') !== 'vendeur')) {
    header('Location: /login.php');
    exit();
}

require_once(__DIR__ . "/connexionBD.php");
$bdd = ConnexionBD::getInstance();
ConnexionBD::ensureWorkflowTables();

$vendeur = $_SESSION['user']['username'];
$idProduit = isset($_POST['id_produit']) ? (int) $_POST['id_produit'] : 0;
$nom = trim($_POST['nom_produit'] ?? '');
$prix = (float)($_POST['prix'] ?? 0);
$quantite = isset($_POST['quantite']) ? max(0, (int) $_POST['quantite']) : 0;
$categorie = trim($_POST['categorie'] ?? 'tous');
$description = trim($_POST['description'] ?? '');

if ($idProduit <= 0 || $nom === '' || $prix <= 0) {
    $_SESSION['product_error'] = 'Données du produit invalides.';
    header('Location: /php/page_vendeur.php');
    exit();
}

$stmt = $bdd->prepare('SELECT * FROM produit WHERE id_produit = :id AND vendeur_username = :vendeur');
$stmt->execute(['id' => $idProduit, 'vendeur' => $vendeur]);
$produit = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$produit) {
    $_SESSION['product_error'] = 'Produit non trouvé ou accès refusé.';
    header('Location: /php/page_vendeur.php');
    exit();
}

$imagePath = $produit['image_path'];
$imageError = '';
if (isset($_FILES['image'])) {
    if ($_FILES['image']['error'] === UPLOAD_ERR_NO_FILE) {
        // rien � faire, conserve l'image existante
    } elseif ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        $imageError = 'Erreur d\u00e9pot d\u2019image (code ' . (int)$_FILES['image']['error'] . ').';
    } else {
        $targetDir = realpath(__DIR__ . "/../files_produit");
        if ($targetDir === false) {
            $targetDir = __DIR__ . "/../files_produit";
            if (!is_dir($targetDir)) {
                @mkdir($targetDir, 0777, true);
            }
        }

        $fileName = uniqid() . "_" . basename($_FILES['image']['name']);
        $absoluteTarget = rtrim($targetDir, "/\\") . DIRECTORY_SEPARATOR . $fileName;
        $relativeTarget = "/files_produit/" . $fileName;

        if (@move_uploaded_file($_FILES['image']['tmp_name'], $absoluteTarget)) {
            $imagePath = $relativeTarget;
        } else {
            $imageError = 'Impossible de copier l\u2019image sur le serveur.';
        }
    }
}

$update = $bdd->prepare('UPDATE produit SET nom_produit = :nom, prix = :prix, quantite = :quantite, categorie = :categorie, description = :description, image_path = :image WHERE id_produit = :id AND vendeur_username = :vendeur');
$update->execute([
    'nom' => $nom,
    'prix' => $prix,
    'quantite' => $quantite,
    'categorie' => $categorie,
    'description' => $description,
    'image' => $imagePath,
    'id' => $idProduit,
    'vendeur' => $vendeur,
]);

if ($imageError !== '') {
    $_SESSION['product_error'] = "Produit mis � jour, mais : $imageError";
} else {
    $_SESSION['product_success'] = 'Produit mis � jour avec succ�s.';
}
header('Location: /php/page_vendeur.php');
exit();


