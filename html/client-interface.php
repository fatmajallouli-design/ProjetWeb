<?php
session_start();
if (empty($_SESSION['user']['username'])) {
    header('Location: /login.php');
    exit();
}

require_once(__DIR__ . '/../php/connexionBD.php');
$bdd = ConnexionBD::getInstance();
ConnexionBD::ensureWorkflowTables();

$username = $_SESSION['user']['username'];
$role = $_SESSION['user']['role'] ?? 'client';

$notifCount = 0;
$messageCount = 0;

try {
    $stmt = $bdd->prepare("SELECT COUNT(*) AS c FROM deal_request WHERE client_username = :u AND (client_seen_at IS NULL OR created_at > client_seen_at)");
    $stmt->execute(['u' => $username]);
    $notifCount = (int)($stmt->fetchColumn() ?? 0);

    $stmt = $bdd->prepare("SELECT COUNT(*) AS c FROM message WHERE receiver_username = :u AND is_read = 0");
    $stmt->execute(['u' => $username]);
    $messageCount = (int)($stmt->fetchColumn() ?? 0);
} catch (PDOException $e) {
}

/* récupération de la photo du profil */
if ($role === 'client') {
    $userStmt = $bdd->prepare('SELECT idphoto FROM client WHERE username = :username');
} else {
    $userStmt = $bdd->prepare('SELECT idphoto FROM vendeur WHERE username = :username');
}

$userStmt->execute(['username' => $username]);
$userInfo = $userStmt->fetch(PDO::FETCH_ASSOC);

$photoPath = trim($userInfo['idphoto'] ?? '');
$photoUrl = '/files_profil/logo.png';
$hasPhoto = false;

if ($photoPath !== '') {
    $normalizedPhotoPath = str_replace('\\', '/', $photoPath);
    $webPhotoPath = str_replace('../', '/', $normalizedPhotoPath);

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
        $photoUrl = $webPhotoPath;
    }
}

$produits = [];
try {
    $produitStmt = $bdd->query('SELECT * FROM produit ORDER BY id_produit DESC LIMIT 12');
    $produits = $produitStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $produits = [];
}

function resolveProductImagePath(?string $path): string
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

    $basename = pathinfo($normalized, PATHINFO_BASENAME);
    if ($basename !== '') {
        foreach (['/files_produit', '/files_produits', '/files_demande'] as $dir) {
            $absDir = $root . $dir;
            if (!is_dir($absDir)) {
                continue;
            }
            foreach (glob($absDir . '/*' . $basename . '*') as $match) {
                if (is_file($match)) {
                    return $dir . '/' . basename($match);
                }
            }
        }
    }

    return '/files_profil/logo.png';
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IMPORTY : Interface Client</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>

<body>
    <div class="cart-toast" id="cartToast">Produit ajoute dans le panier.</div>

    <div class="top-banner">
        <div class="banner-track">
            <span>livraison sur toute la <strong>Tunisie</strong>• </span>
            <span>Chez nous, poster vos demandes et cherchez la meilleure offre pour vous</span>
            <span>livraison sur toute la <strong>Tunisie</strong> •</span>
            <span>Chez nous, poster vos demandes et cherchez la meilleure offre pour vous</span>
            <span>livraison sur toute la <strong>Tunisie</strong> •</span>
            <span>Chez nous, poster vos demandes et cherchez la meilleure offre pour vous</span>
        </div>
    </div>

    <header class="top-header simple-client-header">
        <button id="menuBtn" class="menu-btn" type="button" aria-label="Ouvrir le menu">
            <i class="fa-solid fa-align-justify"></i>
        </button>

        <a class="logo" aria-label="Importy - Accueil">
            <img class="logo-img" src="/files_profil/logo.png" alt="Importy">
        </a>

        <div class="search">
            <i class="fa fa-search"></i>
            <input type="text" id="productSearch" placeholder="Rechercher un produit dans la base de donnees...">
        </div>

        <div class="icons quick-actions">
            <a href="../html/mon compte.php" class="icon-item">
                <i class="fa-regular fa-user" style="color:#B197FC;"></i>
                <span>Mon compte</span>
            </a>

            <a href="../html/panier.php" class="icon-item">
                <i class="fa-solid fa-bag-shopping" style="color:#B197FC;"></i>
                <span>Panier</span>
            </a>

            <a href="../html/demande.php" class="icon-item">
                <i class="fa-solid fa-plus" style="color:#74C0FC;"></i>
                <span>Demande</span>
            </a>

            <a href="../html/mes_demandes.php" class="icon-item">
                <i class="fa-solid fa-list-check" style="color:#74C0FC;"></i>
                <span>Mes demandes</span>
            </a>

            <a href="../html/notifications.php" class="icon-item">
                <i class="fa-solid fa-bell" style="color:#74C0FC;"></i>
                <span>Notification</span>
                <?php if ($notifCount > 0): ?>
                    <span class="badge"><?= htmlspecialchars((string)$notifCount) ?></span>
                <?php endif; ?>
            </a>

            <a href="../html/messages.php" class="icon-item">
                <i class="fa-solid fa-envelope" style="color:#B197FC;"></i>
                <span>Messages</span>
                <?php if ($messageCount > 0): ?>
                    <span class="badge"><?= htmlspecialchars((string)$messageCount) ?></span>
                <?php endif; ?>
            </a>
        </div>
    </header>

    <div class="hero">
        <img src="../files_profil/img.png" alt="promo" class="hero-img">
    </div>

    <div class="overlay" id="overlay"></div>

    <aside class="side-menu client-side-menu" id="sideMenu" aria-hidden="true">
        <div class="side-header">
            <a class="brand" aria-label="Importy - Accueil">
                <img class="brand-img" src="/files_profil/logo.png" alt="Importy">
            </a>
            <button class="menu-close-btn" id="closeMenu" type="button" aria-label="Fermer le menu">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="section">
            <h4>Navigation</h4>
            <a href="../html/mon compte.php"><i class="fa-regular fa-user"></i> Mon compte</a>
            <a href="../html/panier.php"><i class="fa-solid fa-bag-shopping"></i> Panier</a>
            <a href="../html/demande.php"><i class="fa-solid fa-plus"></i> Demande</a>
            <a href="../html/mes_demandes.php"><i class="fa-solid fa-list-check"></i> Mes demandes</a>
            <a href="../html/notifications.php"><i class="fa-solid fa-bell"></i> Notification</a>
            <a href="../html/messages.php"><i class="fa-solid fa-envelope"></i> Message</a>
            <a href="../php/logout.php" id="logoutLink"><i class="fa-solid fa-right-from-bracket"></i> Se deconnecter</a>
        </div>
    </aside>

    <main class="client-page simple-client-page">
        <section class="client-content full-width-content">
            <div class="welcome-banner simple-banner">
                <div class="welcome-user">
                    <img
                        class="account-avatar-image welcome-avatar-image"
                        src="<?= htmlspecialchars($photoUrl) ?>"
                        alt="Photo de profil de <?= htmlspecialchars($username) ?>">

                    <div class="welcome-copy">
                        <p class="welcome-label">Bienvenue</p>
                        <h1><?= htmlspecialchars($username) ?></h1>
                        <p>Utilisez la barre de recherche ou les boutons ci-dessus pour naviguer rapidement.</p>
                    </div>
                </div>
            </div>

            <section class="content-card" id="produits">
                <div class="section-head">
                    <h2>Produits</h2>
                    <p><?= count($produits) ?> resultat(s)</p>
                </div>

                <div class="products-grid" id="productsGrid">
                    <?php if (!empty($produits)): ?>
                        <?php foreach ($produits as $prod): ?>
                            <article class="product-card searchable-product">
                                <span class="product-badge"><?= htmlspecialchars($prod['categorie'] ?? 'Sans categorie') ?></span>
                                <div class="product-image">
                                    <?php $productImage = resolveProductImagePath($prod['image_path'] ?? ''); ?>
                                    <?php if ($productImage !== ''): ?>
                                        <img src="<?= htmlspecialchars($productImage) ?>" alt="<?= htmlspecialchars($prod['nom_produit'] ?? 'Produit') ?>">
                                    <?php else: ?>
                                        <i class="fa-solid fa-box"></i>
                                    <?php endif; ?>
                                </div>

                                <h3><?= htmlspecialchars($prod['nom_produit'] ?? 'Produit') ?></h3>
                                <p><strong>Prix :</strong> <?= htmlspecialchars($prod['prix'] ?? '0') ?> DT</p>

                                <div class="product-actions">
                                    <a class="small-btn" href="/php/produit_details.php?id=<?= urlencode($prod['id_produit'] ?? '') ?>&return_to=<?= urlencode('/client-interface.php') ?>">Voir produit</a>

                                    <form action="/php/add_to_panier.php" method="post" class="add-to-cart-form">
                                        <input type="hidden" name="id_produit" value="<?= (int)($prod['id_produit'] ?? 0) ?>">
                                        <input type="hidden" name="redirect_to" value="/client-interface.php">
                                        <button class="primary-btn product-cart-btn" type="submit" <?= ((int)($prod['quantite'] ?? 0) <= 0) ? 'disabled' : '' ?>>
                                            <?= ((int)($prod['quantite'] ?? 0) <= 0) ? 'Indisponible' : 'Ajouter au panier' ?>
                                        </button>
                                    </form>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="empty-products">Aucun produit trouve dans la base de donnees.</p>
                    <?php endif; ?>
                </div>
            </section>
        </section>
    </main>

    <a href="../html/panier.php" class="floating-cart-btn" aria-label="Voir le panier">
        <i class="fa-solid fa-bag-shopping"></i>
    </a>

    <script>
        const menuBtn = document.getElementById('menuBtn');
        const sideMenu = document.getElementById('sideMenu');
        const closeMenu = document.getElementById('closeMenu');
        const overlay = document.getElementById('overlay');
        const logoutLink = document.getElementById('logoutLink');
        const searchInput = document.getElementById('productSearch');
        const productCards = document.querySelectorAll('.searchable-product');
        const cartToast = document.getElementById('cartToast');
        const addToCartForms = document.querySelectorAll('.add-to-cart-form');

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
                const confirmed = window.confirm('Est tu sure que tu veux te deconnecter ?');
                if (!confirmed) {
                    event.preventDefault();
                }
            });
        }

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const query = this.value.toLowerCase().trim();

                productCards.forEach(function(card) {
                    const text = card.textContent.toLowerCase();
                    card.style.display = text.includes(query) ? '' : 'none';
                });
            });
        }

        function showCartToast(message) {
            if (!cartToast) return;
            cartToast.textContent = message;
            cartToast.classList.add('visible');
            window.clearTimeout(showCartToast.timeoutId);
            showCartToast.timeoutId = window.setTimeout(function() {
                cartToast.classList.remove('visible');
            }, 2200);
        }

        addToCartForms.forEach(function(form) {
            form.addEventListener('submit', async function(event) {
                event.preventDefault();

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        body: new FormData(form)
                    });

                    const data = await response.json();

                    if (data.redirect && !data.success) {
                        window.location.href = data.redirect;
                        return;
                    }

                    showCartToast(data.message || 'Produit ajoute dans le panier.');
                } catch (error) {
                    showCartToast('Erreur lors de l ajout au panier.');
                }
            });
        });
    </script>

    <div class="about-site">
        <h4><strong>A propos de nous</strong></h4>
        <p>
            Importy est un site de vente en ligne qui permet de découvrir et d’acheter
            facilement différents produits dans plusieurs catégories comme la beauté, la mode,
            l’électroménager ou encore les produits technologiques.
            Le but est de proposer une plateforme simple et agréable à utiliser,
            où l’utilisateur peut rechercher des articles. Ce qui distingue Importy,
            c’est qu'avec cette plateforme, les utilisateurs peuvent également poster des demandes spécifiques pour des produits qu’ils recherchent,
            permettant ainsi aux vendeurs de proposer des offres personnalisées.

            Importy vise à offrir une expérience d’achat fluide et sécurisée, avec un large choix de produits
            pour répondre aux attentes de tous les clients.
        </p>
    </div>

    <div class="services">
        <div class="service">
            <div class="icon"><i class="fa-solid fa-store"></i></div>
            <div>
                <h4>pour les vendeurs</h4>
                <p>Proposez vos produits et gérez votre activité en toute simplicité.</p>
            </div>
        </div>

        <div class="service">
            <div class="icon"><i class="fa-solid fa-truck"></i></div>
            <div>
                <h4>Livraison standard offerte</h4>
                <p>just verifier votre adresse dans le compte</p>
            </div>
        </div>

        <div class="service">
            <div class="icon"><i class="fa-regular fa-credit-card"></i></div>
            <div>
                <h4>Paiements a la livraison</h4>
                <p>vous payez le livreur lorsque vous recevez votre commande</p>
            </div>
        </div>

        <div class="service">
            <div class="icon"><i class="fa-solid fa-undo"></i></div>
            <div>
                <h4>Retours</h4>
                <p>sous 14 jours</p>
            </div>
        </div>
    </div>
</body>

</html>