<?php
session_start();
if (empty($_SESSION['user']['username']) || (($_SESSION['user']['role'] ?? '') !== 'client')) {
    header('Location: /login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>demander produit</title>

<link rel="stylesheet" href="../css/demande.css">
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

</head>


<body>

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
            <i class="fa-regular fa-user"></i>
            <span>Mon compte</span>
        </a>

        <a href="../html/panier.php" class="icon-item">
            <i class="fa-solid fa-bag-shopping"></i>
            <span>Panier</span>
        </a>

        <a href="../html/demande.php" class="icon-item">
            <i class="fa-solid fa-plus"></i>
            <span>Demande</span>
        </a>

        <a href="../html/mes_demandes.php" class="icon-item">
            <i class="fa-solid fa-list-check"></i>
            <span>Mes demandes</span>
        </a>

        <a href="../html/notifications.php" class="icon-item">
            <i class="fa-solid fa-bell"></i>
            <span>Notification</span>
            <?php if ($notifCount > 0): ?>
                <span class="badge"><?= htmlspecialchars($notifCount) ?></span>
            <?php endif; ?>
        </a>

        <a href="../html/messages.php" class="icon-item">
            <i class="fa-solid fa-envelope"></i>
            <span>Messages</span>
            <?php if ($messageCount > 0): ?>
                <span class="badge"><?= htmlspecialchars($messageCount) ?></span>
            <?php endif; ?>
        </a>
    </div>
</header>
<div class="card">
  

  <div class="top-actions">
    <a class="home-button" href="../html/client-interface.php">Accueil</a>
  </div>

<?php if (!empty($_SESSION['demande_error'])): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['demande_error']) ?></div>
  <?php unset($_SESSION['demande_error']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['demande_success'])): ?>
  <div class="alert alert-success"><?= htmlspecialchars($_SESSION['demande_success']) ?></div>
  <?php unset($_SESSION['demande_success']); ?>
<?php endif; ?>

  <div class="title">demander produit</div>

  <div class="container">

  <div class="image-box">
    Photo
  </div>

  <form class="form" action="/php/demande.php" method="POST" enctype="multipart/form-data">

    <input type="file" name="image" id="imageInput" hidden>

    <input type="text" name="nom_produit" placeholder="Nom du produit">
    <input type="number" name="prix" placeholder="Prix">

    <select name="categorie">
      <option>tous</option>
      <option>femme</option>
      <option>homme</option>
      <option>maison</option>
      <option>beaute</option>
    </select>

    <input type="text" name="lien_produit" placeholder="Lien produit">

    <textarea name="description" placeholder="Description"></textarea>

    <button type="submit">Publier</button>

  </form>

    </div>



</div>
<script src="../javascript/demande.js"></script>
</body>
</html>
