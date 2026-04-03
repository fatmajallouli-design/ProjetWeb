<?php
session_start();
require_once(__DIR__ . '/connexionBD.php');

$bdd = ConnexionBD::getInstance();

$id = $_GET['id'] ?? null;

if (!$id) {
    die("ID manquant");
}


$req = $bdd->prepare("
SELECT dr.*, v.idphoto AS photo_profil
FROM deal_request dr
JOIN vendeur v ON v.username = dr.vendeur_username
WHERE dr.id_demande = :id
ORDER BY dr.prix_propose ASC
");
$req->execute(["id" => $id]);
$offres = $req->fetchAll();

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Offres</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="../css/offres_style.css">
</head>

<body>

<header class="top-header">

    <div class="header-left">
        <a href="/client-interface.php" class="logo">
            <img src="../files_profil/logo.png" alt="Importy" class="logo-img">
        </a>
    </div>

    <div class="header-center">
        <h1 class="title">Les offres de ce produit</h1>
    </div>

    <div class="header-right" >
      <a href="/mes_demandes.php" class="btn-retour-pro">
      <span class="arrow">â†</span>Retour a mes demandes
      </a>       
    </div>

</header>
<div class="container">



<?php foreach ($offres as $offre): ?>
  <div class="offer-card">

    <!-- VENDEUR -->
    <div class="offer-header">
      <img src="../files_profil/<?= htmlspecialchars($offre['photo_profil']) ?>" class="avatar">

      <a href="/vendor_profile.php?vendeur=<?= urlencode($offre['vendeur_username']) ?>" class="vendeur-name">
        <?= htmlspecialchars($offre['vendeur_username']) ?>
      </a>
    </div>

    <!-- PRIX -->
    <div class="price">
      <?= htmlspecialchars($offre['prix_propose']) ?> TND
    </div>

    <!-- MESSAGE -->
    <p class="message">
      <?= htmlspecialchars($offre['message']) ?>
    </p>

    <!-- ACTIONS -->
    <div class="actions">

      <!-- ðŸ”¥ ACCEPTER -->
      <form method="POST" action="/php/accepter_offre.php">
        <input type="hidden" name="id_deal" value="<?= $offre['id_deal'] ?>">
        <button class="btn-accept">Accepter</button>
      </form>

      <!-- CHAT -->
      <a href="/messages.php?deal=<?= $offre['id_deal'] ?>" class="btn-chat">
        Chat
      </a>

    </div>

  </div>
<?php endforeach; ?>

</div>

</body>
</html>
