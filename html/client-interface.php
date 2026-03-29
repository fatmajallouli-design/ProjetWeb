<?php
session_start();
if (empty($_SESSION['user']['username'])) {
    header('Location: ../html/login.php');
    exit();
}

require_once('../php/connexionBD.php');
$bdd = ConnexionBD::getInstance();
$username = $_SESSION['user']['username'];
$role = $_SESSION['user']['role'] ?? 'client';

if ($role === 'client') {
    $userStmt = $bdd->prepare('SELECT idphoto FROM client WHERE username = :username');
} else {
    $userStmt = $bdd->prepare('SELECT idphoto FROM vendeur WHERE username = :username');
}

$userStmt->execute(['username' => $username]);
$userInfo = $userStmt->fetch(PDO::FETCH_ASSOC);
$photoPath = trim($userInfo['idphoto'] ?? '');
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

// This project dump may not contain the `produit` table.
// If it's missing, we still want the client interface to load.
$produits = [];
try {
    $produitStmt = $bdd->query('SELECT * FROM produit ORDER BY id_produit DESC LIMIT 12');
    $produits = $produitStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $produits = [];
}

function resolveProductImagePath(?string $path): string {
    $raw = trim((string)$path);
    if ($raw === '') return '';
    $candidates = [
        $raw,
        str_replace('../files_demande/', '../files_produits/', $raw),
    ];
    foreach ($candidates as $candidate) {
        $resolved = realpath(__DIR__ . '/' . $candidate);
        if ($resolved !== false && is_file($resolved)) {
            return $candidate;
        }
    }
    return '';
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

    <header class="top-header simple-client-header">
        <button id="menuBtn" class="menu-btn" type="button" aria-label="Ouvrir le menu">
            <i class="fa-solid fa-align-justify"></i>
        </button>

        <a class="logo" aria-label="Importy - Accueil">
            <img class="logo-img" src="../files_profil/logo.png" alt="Importy">
        </a>

        <div class="search">
            <i class="fa fa-search"></i>
            <input type="text" id="productSearch" placeholder="Rechercher un produit dans la base de donnees...">
        </div>

        <div class="icons quick-actions">
            <a href="../html/mon%20compte.php" class="icon-item">
                <i class="fa-regular fa-user" style="color:#B197FC;"></i>
                <span>Mon compte</span>
            </a>

            <a href="../html/panier.php" class="icon-item">
                <i class="fa-solid fa-bag-shopping" style="color:#B197FC;"></i>
                <span>Panier</span>
            </a>

            <a href="demande.html" class="icon-item">
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
            </a>

            <a href="../html/messages.php" class="icon-item">
                <i class="fa-solid fa-envelope" style="color:#B197FC;"></i>
                <span>Messages</span>
            </a>
        </div>
    </header>

    <div class="overlay" id="overlay"></div>

    <aside class="side-menu client-side-menu" id="sideMenu" aria-hidden="true">
        <div class="side-header">
            <a class="brand" aria-label="Importy - Accueil">
                <img class="brand-img" src="../files_profil/logo.png" alt="Importy">
            </a>
            <button class="menu-close-btn" id="closeMenu" type="button" aria-label="Fermer le menu">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="section">
            <h4>Navigation</h4>
            <a href="../html/mon%20compte.php"><i class="fa-regular fa-user"></i> Mon compte</a>
            <a href="../html/panier.php"><i class="fa-solid fa-bag-shopping"></i> Panier</a>
            <a href="demande.html"><i class="fa-solid fa-plus"></i> Demande</a>
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
                    <?php if ($hasPhoto): ?>
                        <img
                            class="account-avatar-image welcome-avatar-image"
                            src="<?php echo htmlspecialchars($photoUrl); ?>"
                            alt="Photo de profil de <?php echo htmlspecialchars($username); ?>"
                        >
                    <?php endif; ?>
                    <div class="welcome-copy">
                        <p class="welcome-label">Bienvenue</p>
                        <h1><?php echo htmlspecialchars($username); ?></h1>
                        <p>Utilisez la barre de recherche ou les boutons ci-dessus pour naviguer rapidement.</p>
                    </div>
                </div>
            </div>

            <section class="content-card" id="produits">
                <div class="section-head">
                    <h2>Produits</h2>
                    <p><?php echo count($produits); ?> resultat(s)</p>
                </div>

                <div class="products-grid" id="productsGrid">
                    <?php if (!empty($produits)): ?>
                        <?php foreach ($produits as $prod): ?>
                            <article class="product-card searchable-product">
                                <span class="product-badge"><?php echo htmlspecialchars($prod['categorie'] ?? 'Sans categorie'); ?></span>
                                <div class="product-image">
                                    <?php $productImage = resolveProductImagePath($prod['image_path'] ?? ''); ?>
                                    <?php if ($productImage !== ''): ?>
                                        <img src="<?php echo htmlspecialchars($productImage); ?>" alt="<?php echo htmlspecialchars($prod['nom_produit'] ?? 'Produit'); ?>">
                                    <?php else: ?>
                                        <i class="fa-solid fa-box"></i>
                                    <?php endif; ?>
                                </div>
                                <h3><?php echo htmlspecialchars($prod['nom_produit'] ?? 'Produit'); ?></h3>
                                <p>
                                    Vendeur :
                                    <a href="./vendor_profile.php?vendeur=<?php echo urlencode($prod['vendeur_username'] ?? ''); ?>">
                                        <?php echo htmlspecialchars($prod['vendeur_username'] ?? 'Inconnu'); ?>
                                    </a>
                                </p>
                                <p>Budget : <?php echo htmlspecialchars($prod['prix'] ?? '0'); ?> DT</p>
                                <p>Date : <?php echo htmlspecialchars($prod['created_at'] ?? ''); ?></p>
                                <p><?php echo htmlspecialchars($prod['description'] ?? 'Aucune description.'); ?></p>
                                <div class="product-actions">
                                    <a class="small-btn" href="../php/produit_details.php?id=<?php echo urlencode($prod['id_produit'] ?? ''); ?>&return_to=<?php echo urlencode('../html/client-interface.php'); ?>">Voir produit</a>
                                    <form action="../php/request_from_product.php" method="post" class="inline-form">
                                        <input type="hidden" name="id_produit" value="<?php echo (int) ($prod['id_produit'] ?? 0); ?>">
                                        <button class="small-btn" type="submit">Demander ce produit</button>
                                    </form>
                                    <form action="../php/add_to_panier.php" method="post" class="inline-form">
                                        <input type="hidden" name="id_produit" value="<?php echo (int) ($prod['id_produit'] ?? 0); ?>">
                                        <input type="hidden" name="redirect_to" value="../html/client-interface.php">
                                        <button class="primary-btn product-cart-btn" type="submit">Ajouter au panier</button>
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
        const addToCartForms = document.querySelectorAll('.inline-form');

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
                const confirmed = window.confirm('Est tu sure que tu veux te deconnecter ?');

                if (!confirmed) {
                    event.preventDefault();
                }
            });
        }

        if (searchInput) {
            searchInput.addEventListener('input', function () {
                const query = this.value.toLowerCase().trim();

                productCards.forEach(function (card) {
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
            showCartToast.timeoutId = window.setTimeout(function () {
                cartToast.classList.remove('visible');
            }, 2200);
        }

        addToCartForms.forEach(function (form) {
            form.addEventListener('submit', async function (event) {
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
</body>
</html>
