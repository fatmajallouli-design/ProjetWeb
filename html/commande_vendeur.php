<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'vendeur') {
    die("AccÃ¨s refusÃ©");
}

require_once(__DIR__ . '/../php/connexionBD.php');
$bdd = ConnexionBD::getInstance();

$vendeur = $_SESSION['user']['username'];

// rÃ©cupÃ©rer commandes du vendeur
$req = $bdd->prepare("
SELECT 
  c.*, 
  d.nom_produit, 
  d.description, 
  d.id_photo, 
  d.lien_produit
FROM commandes c
JOIN demande d ON d.id_demande = c.id_demande
WHERE c.vendeur = :vendeur
ORDER BY c.created_at DESC
");

$req->execute(["vendeur" => $vendeur]);
$commandes = $req->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Mes commandes</title>
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="../css/commande_vendeur.css">
</head>

<body>

<header class="top-header simple-client-header">
        <button id="menuBtn" class="menu-btn" type="button" aria-label="Ouvrir le menu">
            <i class="fa-solid fa-align-justify"></i>
        </button>

        <a class="logo" href="/php/page_vendeur.php" aria-label="Importy - Espace vendeur">
            <img class="logo-img" src="/files_profil/logo.png" alt="Importy">
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
            <a href="/messages.php" class="icon-item">
                <i class="fa-solid fa-envelope" style="color:#B197FC;"></i>
                <span>Messages</span>
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

<div class="container">

<h2>Mes commandes</h2>

<?php foreach ($commandes as $cmd): ?>
  <div class="card">

  <!-- IMAGE -->
  <div class="card-img">
    <img src="../files_produit/<?= htmlspecialchars($cmd['id_photo']) ?>"
         alt="<?= htmlspecialchars($cmd['nom_produit']) ?>">
  </div>

  <!-- CONTENU -->
  <div class="card-content">
    <h3><?= htmlspecialchars($cmd['nom_produit']) ?></h3>

    <p><strong>Client :</strong> <?= htmlspecialchars($cmd['client']) ?></p>
    <p><strong>Description :</strong> <?= htmlspecialchars($cmd['description']) ?></p>

    <!-- ðŸ”¥ LIEN PRODUIT -->
    <?php if (!empty($cmd['lien_produit'])): ?>
      <a href="<?= htmlspecialchars($cmd['lien_produit']) ?>" target="_blank" class="btn-link">
        Voir produit
      </a>
    <?php endif; ?>

    <p><strong>Statut :</strong> <?= htmlspecialchars($cmd['statut']) ?></p>

    <form method="POST" action="/php/update_commande.php">
      <input type="hidden" name="id" value="<?= $cmd['id'] ?>">

      <select name="statut" onchange="this.form.submit()">
        <option value="en cours">En cours</option>
        <option value="livre">LivrÃ©</option>
        <option value="termine">TerminÃ©</option>
        <option value="annule">AnnulÃ©</option>
      </select>
    </form>
  </div>

</div>
<?php endforeach; ?>

<?php if (empty($commandes)): ?>
  <p>Aucune commande pour le moment.</p>
<?php endif; ?>

</div>

</body>
</html>
