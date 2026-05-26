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
$nom = trim($_POST['nom_produit'] ?? '');
$prix = (float)($_POST['prix'] ?? 0);
$quantite = isset($_POST['quantite']) ? max(0, (int) $_POST['quantite']) : 0;
$categorie = trim($_POST['categorie'] ?? 'tous');
$description = trim($_POST['description'] ?? '');
$imagePath = null;
$imageError = '';

if ($nom === '' || $prix <= 0) {
    header('Location: /php/page_vendeur.php');
    exit();
}

if (isset($_FILES['image'])) {
    if ($_FILES['image']['error'] === UPLOAD_ERR_NO_FILE) {
        // Pas d'image t�l�charg�e, produit ajout� sans image.
        $imagePath = null;
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

$ins = $bdd->prepare("INSERT INTO produit (vendeur_username, nom_produit, prix, quantite, categorie, description, image_path) VALUES (:v, :n, :p, :q, :c, :d, :i)");
$ins->execute([
    'v' => $vendeur,
    'n' => $nom,
    'p' => $prix,
    'q' => $quantite,
    'c' => $categorie,
    'd' => $description,
    'i' => $imagePath
]);

$newProductId = (int)$bdd->lastInsertId();

// Notify every client who has previously messaged this vendor (via any deal).
try {
    $clientsStmt = $bdd->prepare("
        SELECT DISTINCT client_username
        FROM (
            SELECT m.sender_username AS client_username
            FROM message m
            JOIN deal_request d ON d.id_deal = m.id_deal
            WHERE d.vendeur_username = :v AND m.sender_username <> :v
            UNION
            SELECT m.receiver_username AS client_username
            FROM message m
            JOIN deal_request d ON d.id_deal = m.id_deal
            WHERE d.vendeur_username = :v AND m.receiver_username <> :v
        ) AS c
    ");
    $clientsStmt->execute(['v' => $vendeur]);
    $clientUsernames = $clientsStmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($clientUsernames as $clientUsername) {
        ConnexionBD::pushNotification([
            'recipient_username' => $clientUsername,
            'recipient_role'     => 'client',
            'type'               => 'new_product',
            'title'              => 'Nouveau produit de ' . $vendeur,
            'body'               => $vendeur . ' vient d\'ajouter "' . $nom . '" (' . number_format((float)$prix, 2) . ' DT).',
            'link'               => '/php/produit_details.php?id=' . $newProductId,
            'actor_username'     => $vendeur,
            'related_id'         => $newProductId,
        ]);
    }
} catch (PDOException $e) {
    // notifications best-effort
}

if ($imageError !== '') {
    $_SESSION['product_error'] = "Produit ajout�, mais : $imageError";
} else {
    $_SESSION['product_success'] = 'Produit ajout� avec succ�s.';
}

header('Location: /php/page_vendeur.php');
exit();
?>


