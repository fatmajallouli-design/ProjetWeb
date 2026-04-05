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

$demandesStmt = $bdd->query("SELECT * FROM demande WHERE etat <> 'recu' ORDER BY COALESCE(created_at, NOW()) DESC, id_demande DESC");
$demandes = $demandesStmt->fetchAll(PDO::FETCH_ASSOC);

$notifCount = 0;
$messageCount = 0;
try {
    // unread notifications: deals where vendeur hasn't seen yet, or new ones since last seen
    $stmt = $bdd->prepare("SELECT COUNT(*) FROM deal_request WHERE vendeur_username = :u AND (vendeur_seen_at IS NULL OR created_at > vendeur_seen_at)");
    $stmt->execute(['u' => $vendeur]);
    $notifCount = (int) ($stmt->fetchColumn() ?? 0);

    // unread messages
    $stmt = $bdd->prepare("SELECT COUNT(*) FROM message WHERE receiver_username = :u AND is_read = 0");
    $stmt->execute(['u' => $vendeur]);
    $messageCount = (int) ($stmt->fetchColumn() ?? 0);
} catch (PDOException $e) {
    // keep 0
}

$myProdStmt = $bdd->prepare("SELECT * FROM produit WHERE vendeur_username = :username ORDER BY created_at DESC, id_produit DESC");
$myProdStmt->execute(['username' => $vendeur]);
$myProduits = $myProdStmt->fetchAll(PDO::FETCH_ASSOC);

function resolveImagePath(?string $path): string {
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

function resolveDemandeImagePath(?string $path): string {
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
            <a href="/commande_vendeur.php" class="icon-item">
                <i class="fa-solid fa-handshake" style="color:#B197FC;"></i>
                <span>Mes commandes</span>
            </a>
            <a href="/vendor_offers.php" class="icon-item">
                <i class="fa-solid fa-paper-plane" style="color:#B197FC;"></i>
                <span>Mes offres</span>
            </a>
            <a href="/notifications.php" class="icon-item">
                <i class="fa-solid fa-bell" style="color:#74C0FC;"></i>
                <span>Notifications</span>
                <?php if ($notifCount > 0): ?>
                    <span class="badge"><?= htmlspecialchars($notifCount) ?></span>
                <?php endif; ?>
            </a>    
            <a href="/messages.php" class="icon-item">
                <i class="fa-solid fa-envelope" style="color:#B197FC;"></i>
                <span>Messages</span>
                <?php if ($messageCount > 0): ?>
                    <span class="badge"><?= htmlspecialchars($messageCount) ?></span>
                <?php endif; ?>
            </a>
            <a href="#mes-produits" class="icon-item">
                <i class="fa-solid fa-box-open" style="color:#B197FC;"></i>
                <span>Mes produits</span>
            </a>
            <a href="/mon%20compte.php" class="icon-item">
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
            <a href="/mon%20compte.php"><i class="fa-regular fa-user"></i> Mon compte</a>
            <a href="/php/logout.php" id="logoutLink"><i class="fa-solid fa-right-from-bracket"></i> Se deconnecter</a>
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
                            alt="Photo de profil de <?= htmlspecialchars($vendeur) ?>"
                        >
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

            <section class="content-card">
                <div class="section-head">
                    <h2>Publier un produit</h2>
                </div>
                <form action="/php/add_product.php" method="post" enctype="multipart/form-data">
                    <input type="text" name="nom_produit" placeholder="Nom du produit" required>
                    <input type="number" step="0.01" min="1" name="prix" placeholder="Prix" required>
                    <input type="number" name="quantite" min="0" placeholder="Quantite" value="1" required>
                    <select name="categorie" required>
                        <option value="tous">Tous</option><option value="femme">Femme</option><option value="homme">Homme</option><option value="maison">Maison</option><option value="beaute">Beaute</option>
                    </select>
                    <textarea name="description" rows="3" placeholder="Description"></textarea>
                    <input type="file" name="image" accept="image/*">
                    <button type="submit">Poster produit</button>
                </form>
            </section>

            <section class="content-card" id="mes-produits">
                <div class="section-head">
                    <h2>Mes produits postes</h2>
                    <p><?= count($myProduits) ?> produit(s)</p>
                </div>
                <?php if (!empty($successMessage)): ?>
                    <div class="account-message success-message"><?= htmlspecialchars($successMessage) ?></div>
                <?php endif; ?>
                <?php if (!empty($errorMessage)): ?>
                    <div class="account-message error-message"><?= htmlspecialchars($errorMessage) ?></div>
                <?php endif; ?>
                <div class="cards">
                    <?php foreach ($myProduits as $p): ?>
                        <article class="inner-card">
                            <?php $prodImage = resolveImagePath($p['image_path'] ?? ''); ?>
                            <?php if ($prodImage !== ''): ?><img class="prod-img" src="<?= htmlspecialchars($prodImage) ?>" alt="Produit"><?php endif; ?>
                            <h3><?= htmlspecialchars($p['nom_produit']) ?></h3>
                            <p class="meta"><?= htmlspecialchars($p['categorie']) ?> | <?= htmlspecialchars($p['prix']) ?> TND | <?= htmlspecialchars($p['created_at']) ?></p>
                            <p><strong>Stock :</strong> <?= ((int)$p['quantite'] > 0) ? ((int)$p['quantite'] . ' disponible(s)') : 'Rupture de stock' ?></p>
                            <p><?= htmlspecialchars($p['description'] ?? '') ?></p>
                            <div class="product-actions">
                                <a href="/edit_product.php?id=<?= (int)$p['id_produit'] ?>" class="secondary-btn">Modifier</a>
                                <form action="/php/delete_product.php" method="post" onsubmit="return confirm('Voulez-vous vraiment supprimer ce produit ?');">
                                    <input type="hidden" name="id_produit" value="<?= (int)$p['id_produit'] ?>">
                                    <button type="submit" class="small-btn" style="background:#ffe4e6;color:#b91c1c;">Supprimer</button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
                <?php if (empty($myProduits)): ?><p>Aucun produit poste pour le moment.</p><?php endif; ?>
            </section>

            <section class="content-card">
                <div class="section-head">
                    <h2>Demandes clients</h2>
                    <p>Vous pouvez proposer un deal</p>
                </div>
                <div class="cards">
                    <?php foreach ($demandes as $d): ?>
                        <article class="inner-card">
                            <?php if (!empty($d['id_photo'])): ?>
                                <img class="dem-img" src="<?= htmlspecialchars(resolveDemandeImagePath($d['id_photo'])) ?>" alt="Demande">
                            <?php endif; ?>
                            <h3><?= htmlspecialchars($d['nom_produit']) ?></h3>
                            <p class="meta">Client: <?= htmlspecialchars($d['username']) ?> | Budget: <?= htmlspecialchars($d['prix']) ?> TND | Date: <?= htmlspecialchars($d['created_at'] ?? '') ?></p>
                            <p><?= htmlspecialchars($d['description']) ?></p>
                            <form action="/php/send_offer.php" method="post">
                                <input type="hidden" name="id_demande" value="<?= (int)$d['id_demande'] ?>">
                                <input type="number" name="prix_propose" min="1" step="0.01" placeholder="Votre prix propose" required>
                                <textarea name="message" rows="2" placeholder="Votre message..." required></textarea>
                                <button type="submit">Envoyer une offre</button>
                            </form>
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
            logoutLink.addEventListener('click', function (event) {
                if (!window.confirm('Est tu sure que tu veux te deconnecter ?')) {
                    event.preventDefault();
                }
            });
        }
    </script>
</body>
</html>



