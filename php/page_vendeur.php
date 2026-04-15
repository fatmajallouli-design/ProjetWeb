<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: /index.php");
    exit();
}
if (empty($_SESSION['user']['username']) || (($_SESSION['user']['role'] ?? '') !== 'vendeur')) {
    header('Location: /login.php');
    exit();
}

require_once(__DIR__ . "/connexionBD.php");
$bdd = ConnexionBD::getInstance();
ConnexionBD::ensureWorkflowTables();
$vendeur = $_SESSION['user']['username'];

$successMessage = $_SESSION['product_success'] ?? '';
$errorMessage = $_SESSION['product_error'] ?? '';
unset($_SESSION['product_success'], $_SESSION['product_error']);

$userStmt = $bdd->prepare('SELECT idphoto FROM vendeur WHERE username = :username');
$userStmt->execute(['username' => $vendeur]);
$userRow = $userStmt->fetch(PDO::FETCH_ASSOC);
$photoPath = trim($userRow['idphoto'] ?? '');
$photoUrl = '';
$hasPhoto = false;

if ($photoPath !== '') {
    $normalizedPhotoPath = str_replace('\\', '/', $photoPath);
    if (strpos($normalizedPhotoPath, '../') === 0) {
        $resolvedPhotoPath = realpath(__DIR__ . '/' . $normalizedPhotoPath);
    } else {
        $resolvedPhotoPath = realpath(__DIR__ . '/../' . ltrim($normalizedPhotoPath, '/'));
        if ($resolvedPhotoPath === false) {
            $resolvedPhotoPath = realpath(__DIR__ . '/' . ltrim($normalizedPhotoPath, '/'));
        }
    }
    if ($resolvedPhotoPath !== false && is_file($resolvedPhotoPath)) {
        $hasPhoto = true;
        $photoUrl = $normalizedPhotoPath;
    }
}

$demandesStmt = $bdd->query("SELECT * FROM demande WHERE etat <> 'recu' AND COALESCE(source, 'demande') = 'demande' ORDER BY COALESCE(created_at, NOW()) DESC, id_demande DESC");
$demandes = $demandesStmt->fetchAll(PDO::FETCH_ASSOC);

$notifCount = 0;
$messageCount = 0;
try {
    $stmt = $bdd->prepare("SELECT COUNT(*) FROM deal_request WHERE vendeur_username = :u AND (vendeur_seen_at IS NULL OR created_at > vendeur_seen_at)");
    $stmt->execute(['u' => $vendeur]);
    $notifCount = (int) ($stmt->fetchColumn() ?? 0);

    $stmt = $bdd->prepare("SELECT COUNT(*) FROM message WHERE receiver_username = :u AND is_read = 0");
    $stmt->execute(['u' => $vendeur]);
    $messageCount = (int) ($stmt->fetchColumn() ?? 0);
} catch (PDOException $e) {
}

$myProdStmt = $bdd->prepare("SELECT * FROM produit WHERE vendeur_username = :username ORDER BY created_at DESC, id_produit DESC");
$myProdStmt->execute(['username' => $vendeur]);
$myProduits = $myProdStmt->fetchAll(PDO::FETCH_ASSOC);

$ordersCountStmt = $bdd->prepare("SELECT COUNT(*) FROM commandes WHERE vendeur = :vendeur");
$ordersCountStmt->execute(['vendeur' => $vendeur]);
$ordersCount = (int)($ordersCountStmt->fetchColumn() ?? 0);

$offersCountStmt = $bdd->prepare("SELECT COUNT(*) FROM deal_request WHERE vendeur_username = :vendeur");
$offersCountStmt->execute(['vendeur' => $vendeur]);
$offersCount = (int)($offersCountStmt->fetchColumn() ?? 0);

$activeProductsCount = count(array_filter($myProduits, static fn($p) => (int)($p['quantite'] ?? 0) > 0));

function resolveImagePath(?string $path): string
{
    $raw = trim((string)$path);
    if ($raw === '') return '/files_profil/logo.png';
    $normalized = str_replace('\\', '/', $raw);
    $normalized = preg_replace('#^\.\./+#', '/', $normalized);

    $candidates = [];
    if (strpos($normalized, '/files_produit/') === 0 || strpos($normalized, '/files_produits/') === 0) {
        $candidates[] = $normalized;
        if (strpos($normalized, '/files_produit/') === 0) {
            $candidates[] = str_replace('/files_produit/', '/files_produits/', $normalized);
        } else {
            $candidates[] = str_replace('/files_produits/', '/files_produit/', $normalized);
        }
    } else {
        if (strpos($normalized, 'files_produit/') === 0 || strpos($normalized, 'files_produits/') === 0) {
            $candidates[] = '/' . ltrim($normalized, '/');
        }
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

function resolveDemandeImagePath(?string $path): string
{
    $raw = trim((string)$path);
    if ($raw === '') {
        return '/files_profil/logo.png';
    }
    $normalized = str_replace('\\', '/', $raw);
    $normalized = preg_replace('#^\.\./+#', '/', $normalized);

    $candidates = [];
    if (strpos($normalized, '/files_demande/') === 0 || strpos($normalized, '/files_produit/') === 0 || strpos($normalized, '/files_produits/') === 0) {
        $candidates[] = $normalized;
    } else {
        $base = ltrim($normalized, '/');
        $candidates[] = '/files_demande/' . $base;
        $candidates[] = '/files_produits/' . $base;
        $candidates[] = '/files_produit/' . $base;
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
    <title>Importy - Espace vendeur</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/vendeur_style.css">
    <link rel="stylesheet" href="../css/page_vendeur.css">
</head>

<body>
    <header class="top-header simple-client-header">
        <button id="menuBtn" class="menu-btn" type="button" aria-label="Ouvrir le menu">
            <i class="fa-solid fa-align-justify"></i>
        </button>

        <a class="logo" href="/php/page_vendeur.php" aria-label="Importy - Espace vendeur">
            <img class="logo-img" src="../files_profil/logo.png" alt="Importy">
        </a>

        <div class="icons quick-actions">
            <a href="/html/commande_vendeur.php" class="icon-item">
                <i class="fa-solid fa-handshake" style="color:#B197FC;"></i>
                <span>Mes commandes</span>
            </a>
            <a href="/html/vendor_offers.php" class="icon-item">
                <i class="fa-solid fa-paper-plane" style="color:#B197FC;"></i>
                <span>Mes offres</span>
            </a>
            <a href="/html/notifications.php" class="icon-item">
                <i class="fa-solid fa-bell" style="color:#74C0FC;"></i>
                <span>Notifications</span>
                <?php if ($notifCount > 0): ?>
                    <span class="badge"><?= htmlspecialchars($notifCount) ?></span>
                <?php endif; ?>
            </a>
            <a href="/html/messages.php" class="icon-item">
                <i class="fa-solid fa-envelope" style="color:#B197FC;"></i>
                <span>Messages</span>
                <?php if ($messageCount > 0): ?>
                    <span class="badge"><?= htmlspecialchars($messageCount) ?></span>
                <?php endif; ?>
            </a>
            <a href="/html/mes_produits_vendeur.php" class="icon-item">
                <i class="fa-solid fa-box-open" style="color:#B197FC;"></i>
                <span>Mes produits</span>
            </a>
            <a href="/html/mon%20compte.php" class="icon-item">
                <i class="fa-regular fa-user" style="color:#74C0FC;"></i>
                <span>Mon compte</span>
            </a>
            <a href="/php/logout.php" class="icon-item">
                <i class="fa-solid fa-right-from-bracket" style="color:#74C0FC;"></i>
                <span>Logout</span>
            </a>
        </div>
    </header>

    <div class="overlay" id="overlay"></div>

    <aside class="side-menu client-side-menu" id="sideMenu" aria-hidden="true">
        <div class="side-header">
            <a class="brand" href="/php/page_vendeur.php" aria-label="Importy - Espace vendeur">
                <img class="brand-img" src="../files_profil/logo.png" alt="Importy">
            </a>
            <button class="menu-close-btn" id="closeMenu" type="button" aria-label="Fermer le menu">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="section">
            <h4>Navigation</h4>
            <a href="/php/page_vendeur.php"><i class="fa-solid fa-store"></i> Espace vendeur</a>
            <a href="/vendor_offers.php"><i class="fa-solid fa-paper-plane"></i> Mes offres</a>
            <a href="/messages.php"><i class="fa-solid fa-envelope"></i> Messages</a>
            <a href="/html/mon%20compte.php"><i class="fa-regular fa-user"></i> Mon compte</a> <a href="/php/logout.php" id="logoutLink"><i class="fa-solid fa-right-from-bracket"></i> Se deconnecter</a>
        </div>
    </aside>

    <main class="client-page simple-client-page vendeur-main">
        <section class="client-content full-width-content">
            <div class="welcome-banner simple-banner">
                <div class="welcome-user">
                    <?php if ($hasPhoto): ?>
                        <img
                            class="account-avatar-image welcome-avatar-image"
                            src="<?= htmlspecialchars($photoUrl) ?>"
                            alt="Photo de profil de <?= htmlspecialchars($vendeur) ?>">
                    <?php else: ?>
                        <div class="avatar-circle welcome-avatar"><?= strtoupper(substr($vendeur, 0, 1)) ?></div>
                    <?php endif; ?>
                    <div class="welcome-copy">
                        <p class="welcome-label">Bienvenue</p>
                        <h1><?= htmlspecialchars($vendeur) ?></h1>
                        <p>Publiez vos produits, consultez vos annonces et repondez aux demandes clients.</p>
                    </div>
                </div>
            </div>

            <section class="vendeur-stats-grid">
                <article class="vendeur-stat-card">
                    <span class="vendeur-stat-icon"><i class="fa-solid fa-box-open"></i></span>
                    <strong><?= count($myProduits) ?></strong>
                    <span>Produits publies</span>
                </article>
                <article class="vendeur-stat-card">
                    <span class="vendeur-stat-icon"><i class="fa-solid fa-cubes"></i></span>
                    <strong><?= $activeProductsCount ?></strong>
                    <span>Produits en stock</span>
                </article>
                <article class="vendeur-stat-card">
                    <span class="vendeur-stat-icon"><i class="fa-solid fa-paper-plane"></i></span>
                    <strong><?= $offersCount ?></strong>
                    <span>Offres envoyees</span>
                </article>
                <article class="vendeur-stat-card">
                    <span class="vendeur-stat-icon"><i class="fa-solid fa-handshake"></i></span>
                    <strong><?= $ordersCount ?></strong>
                    <span>Commandes recues</span>
                </article>
            </section>

            <section class="content-card vendeur-publish-card">
                <div class="section-head">
                    <div>
                        <h2>Publier un produit</h2>
                        <p class="vendeur-section-subtitle">Creez une fiche produit plus claire et plus attractive pour donner envie d'acheter des le premier regard.</p>
                    </div>
                </div>
                <div class="vendeur-publish-shell">
                    <div class="vendeur-publish-copy">
                        <span class="vendeur-mini-badge">Annonce premium</span>
                        <h3>Une belle fiche produit rassure le client</h3>
                        <p>Un bon nom, un prix lisible, une categorie precise et une photo propre rendent votre catalogue plus serieux et plus facile a explorer.</p>
                        <div class="vendeur-publish-points">
                            <span><i class="fa-solid fa-circle-check"></i> Stock et prix visibles</span>
                            <span><i class="fa-solid fa-circle-check"></i> Categorie bien rangee</span>
                            <span><i class="fa-solid fa-circle-check"></i> Image plus vendeuse</span>
                        </div>
                    </div>

                    <form class="vendeur-form-grid vendeur-product-form" action="/php/add_product.php" method="post" enctype="multipart/form-data">
                        <label>
                            <span>Nom du produit</span>
                            <input type="text" name="nom_produit" placeholder="Ex: Parfum YSL Libre" required>
                        </label>
                        <label>
                            <span>Prix</span>
                            <input type="number" step="0.01" min="1" name="prix" placeholder="Prix en DT" required>
                        </label>
                        <label>
                            <span>Quantite disponible</span>
                            <input type="number" name="quantite" min="0" placeholder="Quantite" value="1" required>
                        </label>
                        <label>
                            <span>Categorie</span>
                            <select name="categorie" required>
                                <option value="tous">Tous</option>
                                <option value="femme">Femme</option>
                                <option value="homme">Homme</option>
                                <option value="maison">Maison</option>
                                <option value="beaute">Beaute</option>
                            </select>
                        </label>
                        <label class="vendeur-form-full">
                            <span>Description</span>
                            <textarea name="description" rows="4" placeholder="Expliquez les points forts du produit, son style, son etat ou sa disponibilite."></textarea>
                        </label>
                        <label class="vendeur-form-full vendeur-upload-field">
                            <span>Image produit</span>
                            <input type="file" name="image" accept="image/*">
                        </label>
                        <button type="submit">Publier ce produit</button>
                    </form>
                </div>
            </section>

            <section class="content-card vendeur-products-preview">
                <div class="section-head">
                    <div>
                        <h2>Mes produits</h2>
                        <p class="vendeur-section-subtitle">Accedez a une page dediee pour gerer vos produits avec un affichage plus elegant et plus compact.</p>
                    </div>
                    <a href="/html/mes_produits_vendeur.php" class="small-btn vendeur-page-link">Ouvrir mes produits</a>
                </div>
                <?php if (!empty($successMessage)): ?>
                    <div class="account-message success-message"><?= htmlspecialchars($successMessage) ?></div>
                <?php endif; ?>
                <?php if (!empty($errorMessage)): ?>
                    <div class="account-message error-message"><?= htmlspecialchars($errorMessage) ?></div>
                <?php endif; ?>
                <div class="vendeur-preview-grid">
                    <?php foreach (array_slice($myProduits, 0, 3) as $p): ?>
                        <?php $prodImage = resolveImagePath($p['image_path'] ?? ''); ?>
                        <article class="vendeur-preview-card">
                            <img class="vendeur-preview-image" src="<?= htmlspecialchars($prodImage) ?>" alt="<?= htmlspecialchars($p['nom_produit']) ?>">
                            <div class="vendeur-preview-body">
                                <span class="vendeur-preview-chip"><?= htmlspecialchars($p['categorie']) ?></span>
                                <h3><?= htmlspecialchars($p['nom_produit']) ?></h3>
                                <p><?= htmlspecialchars((string)$p['prix']) ?> DT</p>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
                <?php if (empty($myProduits)): ?><p>Aucun produit poste pour le moment.</p><?php endif; ?>
            </section>

            <section class="content-card">
                <div class="section-head">
                    <div>
                        <h2>Demandes clients</h2>
                        <p class="vendeur-section-subtitle">Reperez les meilleures opportunites et envoyez une offre avec une presentation plus soignee.</p>
                    </div>
                    <span class="vendeur-demandes-count"><?= count($demandes) ?> demande(s)</span>
                </div>
                <div class="vendeur-demandes-grid">
                    <?php foreach ($demandes as $d): ?>
                        <article class="vendeur-demande-card">
                            <?php if (!empty($d['id_photo'])): ?>
                                <div class="vendeur-demande-image-wrap">
                                    <img class="vendeur-demande-image" src="<?= htmlspecialchars(resolveDemandeImagePath($d['id_photo'])) ?>" alt="Demande">
                                </div>
                            <?php endif; ?>

                            <div class="vendeur-demande-body">
                                <div class="vendeur-demande-head">
                                    <span class="vendeur-preview-chip"><?= htmlspecialchars($d['categorie'] ?? 'tous') ?></span>
                                    <span class="vendeur-budget-badge"><?= htmlspecialchars((string)$d['prix']) ?> TND</span>
                                </div>

                                <h3><?= htmlspecialchars($d['nom_produit']) ?></h3>

                                <div class="vendeur-demande-meta-row">
                                    <span><i class="fa-regular fa-user"></i> <?= htmlspecialchars($d['username']) ?></span>
                                    <span><i class="fa-regular fa-calendar"></i> <?= htmlspecialchars($d['created_at'] ?? '') ?></span>
                                </div>

                                <p class="vendeur-demande-text"><?= htmlspecialchars($d['description']) ?></p>

                                <form class="vendeur-demande-form" action="/php/send_offer.php" method="post">
                                <input type="hidden" name="id_demande" value="<?= (int)$d['id_demande'] ?>">
                                    <label>
                                        <span>Votre prix propose</span>
                                        <input type="number" name="prix_propose" min="1" step="0.01" placeholder="Entrez votre prix" required>
                                    </label>
                                    <label class="vendeur-form-full">
                                        <span>Message au client</span>
                                        <textarea name="message" rows="3" placeholder="Expliquez votre offre, delai ou conditions..." required></textarea>
                                    </label>
                                    <button type="submit">Envoyer cette offre</button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        </section>
    </main>

    <script>
        const menuBtn = document.getElementById('menuBtn');
        const sideMenu = document.getElementById('sideMenu');
        const closeMenu = document.getElementById('closeMenu');
        const overlay = document.getElementById('overlay');
        const logoutLink = document.getElementById('logoutLink');

        function openMenu() {
            sideMenu.classList.add('active');
            sideMenu.setAttribute('aria-hidden', 'false');
            overlay.style.display = 'block';
        }

        function closeAll() {
            sideMenu.classList.remove('active');
            sideMenu.setAttribute('aria-hidden', 'true');
            overlay.style.display = 'none';
        }

        if (menuBtn && closeMenu && overlay) {
            menuBtn.addEventListener('click', openMenu);
            closeMenu.addEventListener('click', closeAll);
            overlay.addEventListener('click', closeAll);
        }

        if (logoutLink) {
            logoutLink.addEventListener('click', function(event) {
                if (!window.confirm('Est tu sure que tu veux te deconnecter ?')) {
                    event.preventDefault();
                }
            });
        }
    </script>
</body>

</html>
