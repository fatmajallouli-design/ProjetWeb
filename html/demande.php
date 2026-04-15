<?php
// Démarrer la session et vérifier que l'utilisateur est connecté et est un client
session_start();
if (empty($_SESSION['user']['username']) || (($_SESSION['user']['role'] ?? '') !== 'client')) {
    header('Location: /login.php');
    exit();
}

// Initialiser les variables pour les compteurs de notifications et messages
// Ces variables sont affichées dans le header s'il y a des notifications non lues

?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>demander produit</title>
<link rel="icon" href="/files_profil/logo.png" type="image/png">
<link rel="stylesheet" href="../css/demande.css">
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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
<header class="top-header simple-client-header">
    
        <a href="../html/client-interface.php" class="logo" aria-label="Importy - Accueil">
            <img class="logo-img" src="/files_profil/logo.png" alt="Importy">
        </a>

        <div class="header-center">
        <h1 class="title">Demander un produit</h1>
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

            <a href="../html/mes_demandes.php" class="icon-item">
                <i class="fa-solid fa-list-check" style="color:#74C0FC;"></i>
                <span>Mes demandes</span>
            </a>
            <a href="../html/client-interface.php" class="icon-item">
                <i class="fa-solid fa-home" style="color:#74C0FC;"></i>
                <span>acceuil</span>
            </a>
        </div>
    </header>
   
<div class="card">
  


<?php if (!empty($_SESSION['demande_error'])): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['demande_error']) ?></div>
  <?php unset($_SESSION['demande_error']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['demande_success'])): ?>
  <div class="alert alert-success"><?= htmlspecialchars($_SESSION['demande_success']) ?></div>
  <?php unset($_SESSION['demande_success']); ?>
<?php endif; ?>


  <form class="form" action="/php/demande.php" method="POST" enctype="multipart/form-data">
      <!-- Champ d'image caché (déclenché au clic sur image-box) -->
      <input type="file" name="image" id="imageInput" hidden accept="image/*">

      <div class="container">

        <!-- Zone d'upload d'image cliquable -->
        <div class="image-box" id="imageBox" title="Cliquez pour ajouter une image">
          <img id="imagePreview" style="display:none;" alt="Aperçu">
          <span id="imagePlaceholder">Photo</span>
        </div>

        <!-- Champs alignés à droite de l'image -->
        <div class="form-fields">
          <input type="text" name="nom_produit" placeholder="Nom du produit">
          <input type="number" name="prix" placeholder="Prix">
          <select name="categorie" required>
            <option>tous</option>
            <option>femme</option>
            <option>homme</option>
            <option>maison</option>
            <option>beaute</option>
          </select>
          <input type="url" name="lien_produit" placeholder="Lien produit (https://...)">
        </div>

      </div>

      <textarea name="description" placeholder="Description"></textarea>

      <div class="form-actions">
        <button type="submit">Publier</button>
      </div>

  </form>



</div>
<script src="../javascript/demande.js"></script>
</body>
</html>
