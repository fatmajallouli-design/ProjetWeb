<?php
session_start();

require_once(__DIR__ . '/../php/connexionBD.php');
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

function normalizeProductImagePath(?string $path): string {
    $raw = trim((string)$path);
    if ($raw === '') return '';
    $raw = str_replace('\\', '/', $raw);
    if (strpos($raw, '/files_produit/') === 0 || strpos($raw, '/files_produits/') === 0 || strpos($raw, '/files_demande/') === 0) {
        return $raw;
    }
    if (strpos($raw, '../files_produit/') === 0 || strpos($raw, '../files_produits/') === 0 || strpos($raw, '../files_demande/') === 0) {
        return '/' . ltrim(preg_replace('#^\.{2}/#', '', $raw), '/');
    }
    if (strpos($raw, 'files_demande/') === 0) {
        return '/' . ltrim($raw, '/');
    }
    return $raw;
}

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
<title>DÃ©tails</title>
<link rel="stylesheet" href="../css/details.css">
</head>

<body>

<div class="page">
  <a href="mes_demandes.php" class="back-link">â† Retour Ã  mes demandes</a>

  <div class="box">
      <?php $imgSrc = normalizeProductImagePath($demande['id_photo'] ?? ''); ?>
      <?php if ($imgSrc !== ''): ?>
        <img src="<?= htmlspecialchars($imgSrc) ?>" alt="Produit">
      <?php endif; ?>

      <form method="POST" action="/php/update_etat.php">
    <input type="hidden" name="id" value="<?= $demande['id_demande'] ?>">

    <select name="etat" onchange="this.form.submit()" class="detail-badge <?= $etatClass ?>">
    <option value="en attente" <?= $etat == 'en attente' ? 'selected' : '' ?>>En attente</option>
    <option value="recu" <?= $etat == 'recu' ? 'selected' : '' ?>>ReÃ§u</option>
    <option value="annule" <?= $etat == 'annule' ? 'selected' : '' ?>>AnnulÃ©</option>
    </select>
    </form>

      <h2><?= htmlspecialchars($demande['nom_produit']) ?></h2>

      <div class="detail-grid">
        <p><strong>Prix :</strong> <?= htmlspecialchars($demande['prix']) ?> TND</p>
        <p><strong>CatÃ©gorie :</strong> <?= htmlspecialchars($demande['categorie']) ?></p>
        <p><strong>Date :</strong> <?= htmlspecialchars($demande['created_at'] ?? 'Non disponible') ?></p>
        <p><strong>Utilisateur :</strong> <?= htmlspecialchars($demande['username']) ?></p>
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
    <a href="/php/offres.php?id=<?= $demande['id_demande'] ?>" class="btn-offres">
        Voir les offres
      </a>
  <a href="/php/supprimer_demande.php?id=<?= $demande['id_demande'] ?>"
     class="delete-btn"
     onclick="return confirm('Voulez-vous vraiment supprimer cette demande ?')">
    Supprimer le produit
  </a>
</div>
  </div>
</div>

</body>
</html>
