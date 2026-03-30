<?php
session_start();
if (empty($_SESSION['user']['username']) || (($_SESSION['user']['role'] ?? '') !== 'vendeur')) {
    header('Location: ../html/login.php');
    exit();
}

require_once('../php/connexionBD.php');
$bdd = ConnexionBD::getInstance();
ConnexionBD::ensureWorkflowTables();

$vendeur = $_SESSION['user']['username'];
$idProduit = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($idProduit <= 0) {
    header('Location: ../php/page_vendeur.php');
    exit();
}

$stmt = $bdd->prepare('SELECT * FROM produit WHERE id_produit = :id AND vendeur_username = :vendeur');
$stmt->execute(['id' => $idProduit, 'vendeur' => $vendeur]);
$produit = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$produit) {
    header('Location: ../php/page_vendeur.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier le produit</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/vendeur_style.css">
</head>
<body>
    <main class="account-page">
        <section class="content-card">
            <div class="section-head">
                <h2>Modifier le produit</h2>
                <a href="../php/page_vendeur.php" class="secondary-btn">Retour</a>
            </div>
            <form action="../php/update_product.php" method="post" enctype="multipart/form-data" class="account-form">
                <input type="hidden" name="id_produit" value="<?= (int)$produit['id_produit'] ?>">
                <label>
                    Nom du produit
                    <input type="text" name="nom_produit" value="<?= htmlspecialchars($produit['nom_produit']) ?>" required>
                </label>
                <label>
                    Prix
                    <input type="number" step="0.01" min="0.01" name="prix" value="<?= htmlspecialchars($produit['prix']) ?>" required>
                </label>
                <label>
                    Quantité
                    <input type="number" name="quantite" min="0" value="<?= (int) ($produit['quantite'] ?? 0) ?>" required>
                </label>
                <label>
                    Catégorie
                    <select name="categorie" required>
                        <option value="tous" <?= $produit['categorie'] === 'tous' ? 'selected' : '' ?>>Tous</option>
                        <option value="femme" <?= $produit['categorie'] === 'femme' ? 'selected' : '' ?>>Femme</option>
                        <option value="homme" <?= $produit['categorie'] === 'homme' ? 'selected' : '' ?>>Homme</option>
                        <option value="maison" <?= $produit['categorie'] === 'maison' ? 'selected' : '' ?>>Maison</option>
                        <option value="beaute" <?= $produit['categorie'] === 'beaute' ? 'selected' : '' ?>>Beauté</option>
                    </select>
                </label>
                <label>
                    Description
                    <textarea name="description" rows="4"><?= htmlspecialchars($produit['description'] ?? '') ?></textarea>
                </label>
                <?php if (!empty($produit['image_path'])): ?>
                    <div class="image-preview">
                        <img src="<?= htmlspecialchars($produit['image_path']) ?>" alt="Image actuelle" style="max-width:240px; display:block; margin-bottom:12px; border-radius:12px;">
                    </div>
                <?php endif; ?>
                <label>
                    Remplacer l'image
                    <input type="file" name="image" accept="image/*">
                </label>
                <button type="submit" class="primary-btn">Enregistrer les modifications</button>
            </form>
        </section>
    </main>
</body>
</html>
