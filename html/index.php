<?php
require_once(__DIR__ . '/../php/connexionBD.php');
$bdd = ConnexionBD::getInstance();

$produitStmt = $bdd->query('SELECT * FROM produit ORDER BY id_produit DESC LIMIT 12');
$produits = $produitStmt->fetchAll(PDO::FETCH_ASSOC);

function resolveProductImagePath(?string $path): string {
    $raw = trim((string)$path);
    if ($raw === '') {
        return '/files_profil/logo.png';
    }
    $normalized = str_replace('\\', '/', $raw);
    $normalized = preg_replace('#^\.\./+#', '/', $normalized);

    if (strpos($normalized, 'files_produit/') === 0 || strpos($normalized, 'files_produits/') === 0) {
        $normalized = '/' . ltrim($normalized, '/');
    }

    $fixedPaths = [];
    if (strpos($normalized, '/files_produit/') === 0 || strpos($normalized, '/files_produits/') === 0) {
        $fixedPaths[] = $normalized;
    }
    if (strpos($normalized, '/files_produit/') === 0) {
        $fixedPaths[] = str_replace('/files_produit/', '/files_produits/', $normalized);
    } elseif (strpos($normalized, '/files_produits/') === 0) {
        $fixedPaths[] = str_replace('/files_produits/', '/files_produit/', $normalized);
    }

    $root = realpath(__DIR__ . '/..');
    foreach ($fixedPaths as $candidate) {
        if ($candidate === '') continue;
        $local = $root . $candidate;
        if (is_file($local)) {
            return $candidate;
        }
    }

    $basename = pathinfo($normalized, PATHINFO_BASENAME);
    if ($basename !== '') {
        $dirsToTry = [
            $root . '/files_produit',
            $root . '/files_produits',
            $root . '/files_demande',
        ];
        foreach ($dirsToTry as $dir) {
            if (!is_dir($dir)) continue;
            foreach (glob($dir . '/*' . $basename . '*') as $match) {
                if (is_file($match)) {
                    $publicDir = str_replace($root, '', dirname($match));
                    return rtrim($publicDir, '/') . '/' . basename($match);
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
    <title>IMPORTY : Accueil</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="cart-toast" id="cartToast">Produit ajoute dans le panier.</div>

    <header class="top-header">
        <button id="menuBtn" class="menu-btn">
            <i class="fa-solid fa-align-justify"></i>
        </button>

        <a class="logo" aria-label="Importy - Accueil">
            <img class="logo-img" src="/files_profil/logo.png" alt="Importy">
        </a>

        <div class="search">
            <i class="fa fa-search"></i>
            <input type="text" id="homeProductSearch" placeholder="Rechercher un produit, une marque...">
        </div>

        <div class="icons">
            <a href="#" class="icon-item" id="sidebarPanierTrigger">
                <i class="fa-solid fa-bag-shopping" style="color:#B197FC;"></i>
                <span>Votre Panier</span>
            </a>

            <a href="#" class="icon-item" id="demandeTrigger">
                <i class="fa-solid fa-plus" style="color:#74C0FC;"></i>
                <span>Demande</span>
            </a>

            <a href="#" class="icon-item" id="notificationTrigger">
                <i class="fa-solid fa-bell" style="color:#74C0FC;"></i>
                <span>Notification</span>
            </a>

            <a href="login.php" class="icon-item">
                <i class="fa-regular fa-user" style="color:#74C0FC;"></i>
                <span>Se connecter</span>
            </a>
        </div>
    </header>

    <div class="overlay" id="overlay"></div>

    <div class="side-menu" id="sideMenu">
        <div class="side-header">
            <a class="brand" aria-label="Importy - Accueil">
                <img class="brand-img" src="/files_profil/logo.png" alt="Importy">
            </a>
            <i class="fa-solid fa-xmark" id="closeMenu"></i>
        </div>

        <div class="section">
            <h4>Compte</h4>
            <a href="signup.php"><i class="fa-solid fa-user"></i> Sign up</a>
            <a href="login.php"><i class="fa-regular fa-user"></i> Login</a>
        </div>
    </div>

    <main class="client-page simple-client-page">
        <section class="client-content full-width-content">
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
                                <p>Budget : <?php echo htmlspecialchars($prod['prix'] ?? '0'); ?> DT</p>
                                <p>Stock : <?php echo ((int)($prod['quantite'] ?? 0) > 0) ? ((int)$prod['quantite'] . ' disponible(s)') : 'Rupture de stock'; ?></p>
                                <p><?php echo htmlspecialchars($prod['description'] ?? 'Aucune description.'); ?></p>
                                <div class="product-actions">
                                    <a class="small-btn" href="details.php?id=<?php echo urlencode($prod['id_produit'] ?? ''); ?>&return_to=<?php echo urlencode('index.php'); ?>">Voir produit</a>
                                    <button class="primary-btn product-cart-btn open-index-sidebar" type="button">Ajouter au panier</button>
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

    <a href="#" class="floating-cart-btn" id="floatingSidebarPanierTrigger" aria-label="Voir le panier">
        <i class="fa-solid fa-bag-shopping"></i>
    </a>

    <script>
        const menuBtn = document.getElementById('menuBtn');
        const sideMenu = document.getElementById('sideMenu');
        const closeMenu = document.getElementById('closeMenu');
        const overlay = document.getElementById('overlay');
        const sidebarPanierTrigger = document.getElementById('sidebarPanierTrigger');
        const floatingSidebarPanierTrigger = document.getElementById('floatingSidebarPanierTrigger');
        const demandeTrigger = document.getElementById('demandeTrigger');
        const notificationTrigger = document.getElementById('notificationTrigger');
        const sidebarCartButtons = document.querySelectorAll('.open-index-sidebar');
        const homeSearchInput = document.getElementById('homeProductSearch');
        const homeProductCards = document.querySelectorAll('.searchable-product');
        const cartToast = document.getElementById('cartToast');

        function openMenu() {
            sideMenu.classList.add('active');
            overlay.style.display = 'block';
        }

        function closeAll() {
            sideMenu.classList.remove('active');
            overlay.style.display = 'none';
        }

        if (menuBtn && closeMenu && overlay) {
            menuBtn.addEventListener('click', openMenu);
            closeMenu.addEventListener('click', closeAll);
            overlay.addEventListener('click', closeAll);
        }

        if (sidebarPanierTrigger) {
            sidebarPanierTrigger.addEventListener('click', function (event) {
                event.preventDefault();
                openMenu();
                showCartToast('Connectez-vous pour continuer.');
            });
        }

        if (floatingSidebarPanierTrigger) {
            floatingSidebarPanierTrigger.addEventListener('click', function (event) {
                event.preventDefault();
                openMenu();
                showCartToast('Connectez-vous pour continuer.');
            });
        }

        if (demandeTrigger) {
            demandeTrigger.addEventListener('click', function (event) {
                event.preventDefault();
                showCartToast('Connectez-vous pour continuer.');
            });
        }

        if (notificationTrigger) {
            notificationTrigger.addEventListener('click', function (event) {
                event.preventDefault();
                showCartToast('Connectez-vous pour continuer.');
            });
        }

        sidebarCartButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                openMenu();
            });
        });

        if (homeSearchInput) {
            homeSearchInput.addEventListener('input', function () {
                const query = this.value.toLowerCase().trim();

                homeProductCards.forEach(function (card) {
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

        sidebarCartButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                showCartToast('Connectez-vous pour continuer.');
            });
        });
    </script>
</body>
</html>
