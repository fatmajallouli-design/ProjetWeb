<?php
session_start();

if (empty($_SESSION['user']['username'])) {
    header('Location: /login.php');
    exit();
}

require_once(__DIR__ . '/../php/connexionBD.php');
$bdd = ConnexionBD::getInstance();
$username = $_SESSION['user']['username'];
$success = $_SESSION['panier_success'] ?? '';
$error = $_SESSION['panier_error'] ?? '';

unset($_SESSION['panier_success'], $_SESSION['panier_error']);

$stmt = $bdd->prepare('
    SELECT p.id_panier, p.quantite, p.date_ajout, pr.id_produit, pr.nom_produit, pr.prix, pr.description, pr.categorie, pr.image_path
    FROM panier p
    INNER JOIN produit pr ON pr.id_produit = p.id_produit
    WHERE p.username = :username
    ORDER BY p.date_ajout DESC
');
$stmt->execute(['username' => $username]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = 0;
foreach ($items as $item) {
    $total += ((float) $item['prix']) * ((int) $item['quantite']);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon panier</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/mes_demandes.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">


</head>
<body>
<header class="top-header">

<div class="header-left">
    <a href="client-interface.php" class="logo">
        <img src="/files_profil/logo.png" alt="Importy" class="logo-img">
    </a>
</div>

<div class="header-center">
    <h1 class="title">Mon panier</h1>
</div>

<div class="header-right">
    <a href="../html/client-interface.php" class="header-btn retour-btn">Retour à l'interface client</a>
</header>
   <main class="panier-page">

  <div class="panier-layout">

    <!-- LEFT : PRODUITS -->
    <div class="panier-left">

      <section class="content-card panier-card">

        <div class="section-head">
          <div>
            <h2>Mon panier</h2>
            <p><?= count($items) ?> produit(s)</p>
          </div>
        </div>

        <?php if (!empty($items)): ?>

          <div class="panier-list">

            <?php foreach ($items as $item): ?>

              <article class="panier-item">

                <div class="panier-image">
                  <img src="<?= htmlspecialchars($item['image_path']) ?>" alt="">
                </div>

                <div class="panier-content">

                  <span class="product-badge"><?= htmlspecialchars($item['categorie']) ?></span>

                  <h3><?= htmlspecialchars($item['nom_produit']) ?></h3>

                  <p><?= htmlspecialchars($item['description']) ?></p>

                  <strong class="price"><?= htmlspecialchars($item['prix']) ?> DT</strong>

                  <p>
                    <strong>Sous-total :</strong>
                    <?= number_format($item['prix'] * $item['quantite'], 2) ?> DT
                  </p>

                  <div class="panier-controls">

                    <form action="/php/update_panier.php" method="post">
                      <input type="hidden" name="action" value="update">
                      <input type="hidden" name="id_panier" value="<?= $item['id_panier'] ?>">

                      <input type="number" name="quantite" value="<?= $item['quantite'] ?>" min="1">

                      <button class="secondary-btn">Modifier</button>
                    </form>

                    <form action="/php/update_panier.php" method="post">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id_panier" value="<?= $item['id_panier'] ?>">

                      <button class="delete-btn">Supprimer</button>
                    </form>

                  </div>

                </div>

              </article>

            <?php endforeach; ?>

          </div>

        <?php else: ?>

          <p>Votre panier est vide.</p>

        <?php endif; ?>

      </section>

    </div>

    <div class="panier-right">

      <div class="resume-card">

        <h3>Récapitulatif</h3>

        <div class="resume-line">
          <span>Sous-total</span>
          <span><?= number_format($total, 2) ?> DT</span>
        </div>

        <div class="resume-line">
          <span>Livraison</span>
          <span>Calculé à l'étape paiement</span>
        </div>

        <div class="resume-line total">
          <span>Total</span>
          <span><?= number_format($total, 2) ?> DT</span>
        </div>

        <form action="/php/valider_panier.php" method="post">
  <button type="submit" class="btn-checkout">
    Valider mon panier
  </button>
</form>

      </div>

    </div>

  </div>

</main>
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


