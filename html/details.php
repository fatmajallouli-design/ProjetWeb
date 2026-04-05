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

$etatClass = match($etat) {
    'recu' => 'valide',
    'annule' => 'annule',
    default => 'en_attente'
};
$cmdReq = $bdd->prepare("SELECT * FROM commandes WHERE id_demande = :id LIMIT 1");
$cmdReq->execute(["id" => $id]);
$commande = $cmdReq->fetch();

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

      <form method="POST" action="../php/update_etat.php">
    <input type="hidden" name="id" value="<?= $demande['id_demande'] ?>">

    <select name="etat" onchange="this.form.submit()" class="detail-badge <?= $etatClass ?>">
    <option value="en attente" <?= $etat == 'en attente' ? 'selected' : '' ?>>En attente</option>
    <option value="recu" <?= $etat == 'recu' ? 'selected' : '' ?>>Reçu</option>
    <option value="annule" <?= $etat == 'annule' ? 'selected' : '' ?>>Annulé</option>
    </select>
    </form>

      <h2><?= htmlspecialchars($demande['nom_produit']) ?></h2>

      <div class="detail-grid">
        <p><strong>Prix :</strong> <?= htmlspecialchars($demande['prix']) ?> TND</p>
        <p><strong>Catégorie :</strong> <?= htmlspecialchars($demande['categorie']) ?></p>
        <p><strong>Date :</strong> <?= htmlspecialchars($demande['created_at'] ?? 'Non disponible') ?></p>
        <?php if ($commande): ?>
        <p>
        <strong>Vendeur :</strong>
          <?= htmlspecialchars($commande['vendeur']) ?>

          <a href="vendor_profile.php?vendeur=<?= urlencode($commande['vendeur']) ?>"
          class="btn-vendeur">
          Voir profil
          </a>
        </p>
<?php endif; ?>
      </div>

      
      <div class="detail-actions">
  <?php if (!empty($demande['lien_produit'])): ?>
    <a href="<?= htmlspecialchars($demande['lien_produit']) ?>" target="_blank" class="action-btn">
      Voir le produit
    </a>
  <?php else: ?>
    <span></span>
  <?php endif; ?>
    <a href="../php/offres.php?id=<?= $demande['id_demande'] ?>" class="btn-offres">
        Voir les offres
      </a>
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