<?php
session_start();
if (empty($_SESSION['user']['username']) || (($_SESSION['user']['role'] ?? '') !== 'client')) {
    header('Location: /login.php');
    exit();
}

$nom_produit = trim($_POST['nom_produit'] ?? '');
$prix = floatval($_POST['prix'] ?? 0);
$categorie = trim($_POST['categorie'] ?? 'tous');
$description = trim($_POST['description'] ?? '');
$lien_produit = trim($_POST['lien_produit'] ?? '');
$newFilePath = null;
$errorMessage = '';

if ($nom_produit === '' || $prix <= 0 || $lien_produit === '' || $description === '') {
    $_SESSION['demande_error'] = 'Veuillez remplir tous les champs requis correctement.';
    header('Location: /demande.php');
    exit();
}

if (isset($_FILES['image'])) {
    if ($_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $targetDir = realpath(__DIR__ . '/../files_produits');
        if ($targetDir === false || !is_dir($targetDir)) {
            $targetDir = __DIR__ . '/../files_produits';
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }
        }

        $fileName = uniqid() . '_' . basename($_FILES['image']['name']);
        $absoluteTarget = rtrim($targetDir, '/\\') . DIRECTORY_SEPARATOR . $fileName;
        $relativeTarget = '/files_produits/' . $fileName;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $absoluteTarget)) {
            $newFilePath = $relativeTarget;
        } else {
            $errorMessage = 'Impossible d\'enregistrer l\'image du produit. Veuillez réessayer.';
        }
    } elseif ($_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $errorMessage = 'Erreur lors du téléchargement de l\'image (code ' . (int)$_FILES['image']['error'] . ').';
    }
}

require_once(__DIR__ . '/connexionBD.php');
$bdd = ConnexionBD::getInstance();
ConnexionBD::ensureWorkflowTables();

$username = $_SESSION['user']['username'];
$etat = 'en attente';

$insert = $bdd->prepare('INSERT INTO demande (nom_produit, prix, lien_produit, description, categorie, id_photo, username, etat) VALUES (:nom_produit, :prix, :lien_produit, :description, :categorie, :id_photo, :username, :etat)');
$insert->execute([
    'nom_produit' => $nom_produit,
    'prix' => $prix,
    'lien_produit' => $lien_produit,
    'description' => $description,
    'categorie' => $categorie,
    'id_photo' => $newFilePath,
    'username' => $username,
    'etat' => $etat,
]);

$newDemandeId = (int)$bdd->lastInsertId();

// Notify every seller that a new client demande was posted.
try {
    $vendeursStmt = $bdd->query('SELECT username FROM vendeur');
    $vendeurUsernames = $vendeursStmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($vendeurUsernames as $vUser) {
        ConnexionBD::pushNotification([
            'recipient_username' => $vUser,
            'recipient_role'     => 'vendeur',
            'type'               => 'new_demande',
            'title'              => 'Nouvelle demande de ' . $username,
            'body'               => $username . ' demande "' . $nom_produit . '" (budget ' . number_format((float)$prix, 2) . ' DT).',
            'link'               => '/php/page_vendeur.php',
            'actor_username'     => $username,
            'related_id'         => $newDemandeId,
        ]);
    }
} catch (PDOException $e) {
    // notifications best-effort
}

if ($errorMessage !== '') {
    $_SESSION['demande_error'] = 'Demande créée mais : ' . $errorMessage;
} else {
    $_SESSION['demande_success'] = 'Demande créée avec succès.';
}

header('Location: /mes_demandes.php');
exit();


