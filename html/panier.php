<?php
session_start();

if (empty($_SESSION['user']['username'])) {
    header('Location: /login.php');
    exit();
}

require_once(__DIR__ . '/../php/connexionBD.php');
$bdd = ConnexionBD::getInstance();
$username = $_SESSION['user']['username'];
$success = $_SESSION['panier_success'] ?? '';
$error = $_SESSION['panier_error'] ?? '';

unset($_SESSION['panier_success'], $_SESSION['panier_error']);

$stmt = $bdd->prepare('
    SELECT p.id_panier, p.quantite, p.date_ajout, pr.id_produit, pr.nom_produit, pr.prix, pr.description, pr.categorie, pr.image_path
    FROM panier p
    INNER JOIN produit pr ON pr.id_produit = p.id_produit
    WHERE p.username = :username
    ORDER BY p.date_ajout DESC
');
$stmt->execute(['username' => $username]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = 0;
foreach ($items as $item) {
    $total += ((float) $item['prix']) * ((int) $item['quantite']);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon panier</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/mes_demandes.css">

</head>
<body>
<header class="top-header">

<div class="header-left">
    <a href="client-interface.php" class="logo">
        <img src="/files_profil/logo.png" alt="Importy" class="logo-img">
    </a>
</div>

<div class="header-center">
    <h1 class="title">Mon panier</h1>
</div>

<div class="header-right">
    <a href="client-interface.php" class="header-btn retour-btn">â† Retour Ã  lâ€™interface client</a>
</header>
    <main class="panier-page">
        <section class="content-card panier-card">
            <div class="section-head">
                <div>
                    <h2>Mon panier</h2>
                    <p><?= count($items) ?> produit(s)</p>
                </div>
                
            </div>

            <?php if (!empty($success)): ?>
                <div class="account-message success-message"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="account-message error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if (!empty($items)): ?>
                <div class="panier-list">
                    <?php foreach ($items as $item): ?>
                        <article class="panier-item">
                            <div class="panier-image">
                                <?php if (!empty($item['image_path'])): ?>
                                    <img src="<?= htmlspecialchars($item['image_path']) ?>" alt="<?= htmlspecialchars($item['nom_produit']) ?>">
                                <?php endif; ?>
                            </div>
                            <div class="panier-content">
                                <span class="product-badge"><?= htmlspecialchars($item['categorie'] ?? 'Sans categorie') ?></span>
                                <h3><?= htmlspecialchars($item['nom_produit']) ?></h3>
                                <p><?= htmlspecialchars($item['description']) ?></p>
                                <strong><?= htmlspecialchars($item['prix']) ?> DT</strong>
                                <p><strong>Sous-total :</strong> <?= number_format(((float) $item['prix']) * ((int) $item['quantite']), 2, '.', '') ?> DT</p>

                                <div class="panier-controls">
                                    <form action="/php/update_panier.php" method="post" class="panier-form">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="id_panier" value="<?= (int) $item['id_panier'] ?>">
                                        <label class="panier-qty-label">
                                            <span>Quantite</span>
                                            <input type="number" name="quantite" min="1" value="<?= (int) $item['quantite'] ?>">
                                        </label>
                                        <button type="submit" class="secondary-btn">Modifier</button>
                                    </form>

                                    <form action="/php/update_panier.php" method="post" class="panier-form">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id_panier" value="<?= (int) $item['id_panier'] ?>">
                                        <button type="submit" class="small-btn panier-delete-btn">Supprimer</button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <div class="panier-summary">
                    <strong>Total : <?= number_format($total, 2, '.', '') ?> DT</strong>
                    <div class="panier-actions">
                        <form action="/php/valider_panier.php" method="post" style="display:inline-block; margin-right:12px;">
                            <button type="submit" class="primary-btn">Valider le panier</button>
                        </form>
                        <a href="/client-interface.php" class="secondary-btn">Retour</a>
                    </div>
                </div>
            <?php else: ?>
                <p class="empty-products">Votre panier est vide.</p>
                <a href="/client-interface.php" class="secondary-btn">Voir les produits</a>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>


