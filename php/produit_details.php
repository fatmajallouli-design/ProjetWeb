﻿<?php
session_start();

require_once(__DIR__ . '/connexionBD.php');
$bdd = ConnexionBD::getInstance();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$returnTo = trim($_GET['return_to'] ?? '/index.php');

if ($returnTo === '' || preg_match('/^https?:/i', $returnTo)) {
    $returnTo = '/index.php';
}

if ($id <= 0) {
    die("Produit introuvable");
}

$req = $bdd->prepare("SELECT * FROM produit WHERE id_produit = :id");
$req->execute(["id" => $id]);
$produit = $req->fetch(PDO::FETCH_ASSOC);

if (!$produit) {
    die("Produit introuvable");
}

$req2 = $bdd->prepare("
  SELECT * FROM produit 
  WHERE categorie = :categorie 
  AND id_produit != :id
  LIMIT 4
");

$req2->execute([
  "categorie" => $produit['categorie'],
  "id" => $produit['id_produit']
]);

$produits_similaires = $req2->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Details produit</title>
<link rel="stylesheet" href="../css/produit_details.css">
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

</head>
<body>
 <div class="top-banner">
        <div class="banner-track">
        <span>profitez de remise jusqu'a <strong>25%</strong>• </span>
        <span>profitez de remise jusqu'a <strong>25%</strong>• </span>
        <span>profitez de remise jusqu'a <strong>25%</strong>• </span>
        <span>profitez de remise jusqu'a <strong>25%</strong>• </span>
        <span>profitez de remise jusqu'a <strong>25%</strong>• </span>
        <span>profitez de remise jusqu'a <strong>25%</strong>• </span>
        
         </div>
    </div>
   <header class="top-header simple-client-header">
        <button id="menuBtn" class="menu-btn" type="button" aria-label="Ouvrir le menu">
            <i class="fa-solid fa-align-justify"></i>
        </button>

        <a class="logo"  href="../html/client-interface.php" aria-label="Importy - Accueil">
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

            

            
        </div>
    </header>
    <div id="overlay"></div>
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
</div>
<!-- CONTENEUR GLOBAL -->
<div class="main-container">

  <!-- PRODUIT -->
  <div class="product-page">

    <div class="product-left">
      <img src="<?= htmlspecialchars($produit['image_path']) ?>" alt="">
    </div>

    <div class="product-right">

      <span class="category"><?= htmlspecialchars($produit['categorie']) ?></span>

      <h1><?= htmlspecialchars($produit['nom_produit']) ?></h1>

      <p class="price">
        <strong>prix:</strong> <?= htmlspecialchars($produit['prix']) ?> DT
      </p>

      <form action="../php/add_to_panier.php" method="post">
        <input type="hidden" name="id_produit" value="<?= $produit['id_produit'] ?>">

        <button class="btn-cart">
          Ajouter au panier
        </button>
      </form>

      

      <div class="detail-actions">
        <a class="back-link" href="../html<?= htmlspecialchars($returnTo) ?>">Retour</a>
      </div>

    </div>

  </div>
  <div class="related-products">

  <h2>Complétez votre routine</h2>

  <div class="products-grid">

    <?php foreach ($produits_similaires as $p): ?>

      <div class="product-card">

        <a href="produit_details.php?id=<?= $p['id_produit'] ?>">
          <img src="<?= htmlspecialchars($p['image_path']) ?>" alt="">
        </a>

        <h4><?= htmlspecialchars($p['nom_produit']) ?></h4>

        <p><?= htmlspecialchars($p['prix']) ?> DT</p>

      </div>

    <?php endforeach; ?>

  </div>

</div>

  

</div>


<script>
  const menuBtn = document.getElementById('menuBtn');
const closeMenuBtn = document.getElementById('closeMenu');
const sideMenu = document.getElementById('sideMenu');
const overlay = document.getElementById('overlay');

/* OUVRIR */
menuBtn.addEventListener('click', () => {
  sideMenu.classList.add('active');
  overlay.style.display = 'block';
});

/* FERMER */
closeMenuBtn.addEventListener('click', () => {
  sideMenu.classList.remove('active');
  overlay.style.display = 'none';
});

/* CLIQUER DEHORS */
overlay.addEventListener('click', () => {
  sideMenu.classList.remove('active');
  overlay.style.display = 'none';
});
</script>
<div class="about-site">
     <h4><strong>A propos de nous</strong></h4>                               
    <p>
    Importy est un site de vente en ligne qui permet de découvrir et d’acheter
     facilement différents produits dans plusieurs catégories 
       
    
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