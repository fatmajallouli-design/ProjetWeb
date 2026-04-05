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
    <div class="top-banner">
        <div class="banner-track">
        <span>livraison sur toute la <strong>Tunisie</strong>• </span>
        <span>Chez nous,poster votre demandes et chercher la meilleure offre pour vous</span>
        <span>livraison sur toute la <strong>Tunisie</strong> •</span>
        <span>Chez nous,poster votre demandes et chercher la meilleure offre pour vous</span>
        <span>livraison sur toute la <strong>Tunisie</strong> •</span>
        <span>Chez nous,poster votre demandes et chercher la meilleure offre pour vous</span>
        
         </div>
    </div>
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

            

            

            <a href="login.php" class="icon-item">
                <i class="fa-regular fa-user" style="color:#74C0FC;"></i>
                <span>Se connecter</span>
            </a>
        </div>
    </header>
    <div class="hero">
    <img src="../files_profil/image.png" alt="promo" class="hero-img">
    </div>


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
                                <p><strong>Prix : </strong><?php echo htmlspecialchars($prod['prix'] ?? '0'); ?> DT</p>
                               
                                <div class="product-actions">
                                    <a class="small-btn" href="../php/produit_details.php?id=<?php echo urlencode($prod['id_produit'] ?? ''); ?>&return_to=<?php echo urlencode('index.php'); ?>">Voir produit</a>
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

                    
    <div class="about-site">
     <h4><strong>A propos de nous</strong></h4>                               
    <p>
    Importy est un site de vente en ligne qui permet de découvrir et d’acheter
     facilement différents produits dans plusieurs catégories comme la beauté, la mode,
      l’électroménager ou encore les produits technologiques.
       Le but est de proposer une plateforme simple et agréable à utiliser,
        où l’utilisateur peut rechercher des articles.Ce qui distingue Importy,
         c’est qu'avec cette platforme, les utilisateurs peuvent également poster des demandes spécifiques pour des produits qu’ils recherchent,
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
