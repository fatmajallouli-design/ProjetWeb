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
$nom = trim($_POST['nom_produit'] ?? '');
$prix = (float)($_POST['prix'] ?? 0);
$categorie = trim($_POST['categorie'] ?? 'tous');
$description = trim($_POST['description'] ?? '');
$imagePath = null;

if ($nom === '' || $prix <= 0) {
    header('Location: ../php/page_vendeur.php');
    exit();
}

if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
    $targetDir = realpath(__DIR__ . "/../files_produit");
    if ($targetDir === false) {
        $targetDir = __DIR__ . "/../files_produit";
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0777, true);
        }
    }

    $fileName = uniqid() . "_" . basename($_FILES['image']['name']);
    $absoluteTarget = rtrim($targetDir, "/\\") . DIRECTORY_SEPARATOR . $fileName;
    $relativeTarget = "../files_produit/" . $fileName;

    if (@move_uploaded_file($_FILES['image']['tmp_name'], $absoluteTarget)) {
        $imagePath = $relativeTarget;
    }
}

$ins = $bdd->prepare("INSERT INTO produit (vendeur_username, nom_produit, prix, categorie, description, image_path) VALUES (:v, :n, :p, :c, :d, :i)");
$ins->execute([
    'v' => $vendeur,
    'n' => $nom,
    'p' => $prix,
    'c' => $categorie,
    'd' => $description,
    'i' => $imagePath
]);

header('Location: ../php/page_vendeur.php');
exit();
?>
