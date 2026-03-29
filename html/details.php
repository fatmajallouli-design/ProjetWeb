<?php
session_start();

require_once("../php/connexionBD.php");
$bdd = ConnexionBD::getInstance();

$id = $_GET['id'] ?? null;

if (!$id) {
    die("ID manquant");
}

$req = $bdd->prepare("SELECT * FROM demande WHERE id_demande = :id");
$req->execute(["id" => $id]);

$demande = $req->fetch();

if (!$demande) {
    die("Demande introuvable");
}

$etat = strtolower(trim($demande['etat'] ?? 'en attente'));
$etatClass = ($etat === 'valide') ? 'valide' : 'en_attente';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Détails</title>
<link rel="stylesheet" href="../css/details.css">
</head>

<body>

<div class="page">
  <a href="mes_demandes.php" class="back-link">← Retour à mes demandes</a>

  <div class="box">
      <img src="../files_produit/<?= htmlspecialchars($demande['id_photo']) ?>" alt="Produit">

      <span class="detail-badge <?= $etatClass ?>">
        <?= htmlspecialchars($demande['etat']) ?>
      </span>

      <h2><?= htmlspecialchars($demande['nom_produit']) ?></h2>

      <div class="detail-grid">
        <p><strong>Prix :</strong> <?= htmlspecialchars($demande['prix']) ?> TND</p>
        <p><strong>Catégorie :</strong> <?= htmlspecialchars($demande['categorie']) ?></p>
        <p><strong>Date :</strong> <?= htmlspecialchars($demande['created_at'] ?? 'Non disponible') ?></p>
        <p><strong>Utilisateur :</strong> <?= htmlspecialchars($demande['username']) ?></p>
      </div>

      <div class="description-box">
        <h3>Description</h3>
        <p><?= nl2br(htmlspecialchars($demande['description'])) ?></p>
      </div>

      <div class="detail-actions">
  <?php if (!empty($demande['lien_produit'])): ?>
    <a href="<?= htmlspecialchars($demande['lien_produit']) ?>" target="_blank" class="action-btn">
      Voir le produit
    </a>
  <?php else: ?>
    <span></span>
  <?php endif; ?>

  <a href="../php/supprimer_demande.php?id=<?= $demande['id_demande'] ?>"
     class="delete-btn"
     onclick="return confirm('Voulez-vous vraiment supprimer cette demande ?')">
    Supprimer le produit
  </a>
</div>
  </div>
</div>

</body>
</html>