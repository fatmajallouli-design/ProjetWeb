<?php
session_start();

if (empty($_SESSION['user']['username']) || (($_SESSION['user']['role'] ?? '') !== 'vendeur')) {
    header('Location: /login.php');
    exit();
}

require_once(__DIR__ . '/../php/connexionBD.php');
$bdd = ConnexionBD::getInstance();
ConnexionBD::ensureWorkflowTables();

$vendeur = $_SESSION['user']['username'];
$successMessage = $_SESSION['product_success'] ?? '';
$errorMessage = $_SESSION['product_error'] ?? '';
unset($_SESSION['product_success'], $_SESSION['product_error']);

$stmt = $bdd->prepare("SELECT * FROM produit WHERE vendeur_username = :username ORDER BY created_at DESC, id_produit DESC");
$stmt->execute(['username' => $vendeur]);
$produits = $stmt->fetchAll(PDO::FETCH_ASSOC);

function resolveSellerProductImage(?string $path): string
{
    $raw = trim((string)$path);
    if ($raw === '') {
        return '/files_profil/logo.png';
    }

    $normalized = str_replace('\\', '/', $raw);
    $normalized = preg_replace('#^\.\./+#', '/', $normalized);

    $candidates = [];
    if (strpos($normalized, '/files_produit/') === 0 || strpos($normalized, '/files_produits/') === 0) {
        $candidates[] = $normalized;
        $candidates[] = str_replace('/files_produit/', '/files_produits/', $normalized);
        $candidates[] = str_replace('/files_produits/', '/files_produit/', $normalized);
    } else {
        $candidates[] = '/files_produit/' . ltrim($normalized, '/');
        $candidates[] = '/files_produits/' . ltrim($normalized, '/');
    }

    $root = realpath(__DIR__ . '/..');
    foreach ($candidates as $candidate) {
        $absPath = realpath($root . $candidate);
        if ($absPath !== false && is_file($absPath)) {
            return $candidate;
        }
    }

    return '/files_profil/logo.png';
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes produits</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/page_vendeur.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>
    <header class="top-header simple-client-header">
        <a class="logo" href="/php/page_vendeur.php" aria-label="Importy - Espace vendeur">
            <img class="logo-img" src="/files_profil/logo.png" alt="Importy">
        </a>

        <div class="header-center">
            <h1 class="title">Mes produits</h1>
        </div>

        <div class="header-right">
            <a href="/php/page_vendeur.php" class="header-btn retour-btn">Retour espace vendeur</a>
        </div>
    </header>

    <main class="orders-page">
        <section class="orders-hero">
            <div class="orders-hero-copy">
                <h2>Catalogue du vendeur</h2>
                <p>Retrouvez vos produits dans une vraie galerie plus compacte, plus elegante et plus simple a gerer au quotidien.</p>
            </div>
            <div class="orders-hero-badge"><?= count($produits) ?> produit(s)</div>
        </section>

        <?php if (!empty($successMessage)): ?>
            <div class="account-message success-message"><?= htmlspecialchars($successMessage) ?></div>
        <?php endif; ?>
        <?php if (!empty($errorMessage)): ?>
            <div class="account-message error-message"><?= htmlspecialchars($errorMessage) ?></div>
        <?php endif; ?>

        <?php if (!empty($produits)): ?>
            <section class="vendeur-gallery-grid">
                <?php foreach ($produits as $produit): ?>
                    <article class="vendeur-gallery-card">
                        <img
                            class="vendeur-gallery-image"
                            src="<?= htmlspecialchars(resolveSellerProductImage($produit['image_path'] ?? '')) ?>"
                            alt="<?= htmlspecialchars($produit['nom_produit']) ?>">

                        <div class="vendeur-gallery-body">
                            <div class="vendeur-gallery-top">
                                <span class="vendeur-preview-chip"><?= htmlspecialchars($produit['categorie'] ?? 'Sans categorie') ?></span>
                                <span class="vendeur-stock-badge <?= ((int)($produit['quantite'] ?? 0) > 0) ? 'in-stock' : 'out-stock' ?>">
                                    <?= ((int)($produit['quantite'] ?? 0) > 0) ? ((int)$produit['quantite'] . ' en stock') : 'Rupture' ?>
                                </span>
                            </div>

                            <h3><?= htmlspecialchars($produit['nom_produit']) ?></h3>
                            <p class="vendeur-gallery-price"><?= htmlspecialchars((string)$produit['prix']) ?> DT</p>
                            <p class="vendeur-gallery-desc"><?= htmlspecialchars($produit['description'] ?? '') ?></p>
                            <p class="vendeur-gallery-date">Publie le <?= htmlspecialchars($produit['created_at'] ?? '') ?></p>

                            <div class="product-actions">
                                <a href="/html/edit_product.php?id=<?= (int)$produit['id_produit'] ?>" class="secondary-btn">Modifier</a>
                                <form action="/php/delete_product.php" method="post" onsubmit="return confirm('Voulez-vous vraiment supprimer ce produit ?');">
                                    <input type="hidden" name="id_produit" value="<?= (int)$produit['id_produit'] ?>">
                                    <button type="submit" class="small-btn vendeur-danger-btn">Supprimer</button>
                                </form>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php else: ?>
            <section class="orders-empty">
                <h3>Aucun produit pour le moment</h3>
                <p>Ajoutez votre premier produit depuis l'espace vendeur et il apparaitra ici dans votre galerie.</p>
                <a href="/php/page_vendeur.php" class="add-btn">Publier un produit</a>
            </section>
        <?php endif; ?>
    </main>
</body>

</html>