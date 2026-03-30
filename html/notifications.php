<?php
session_start();
if (empty($_SESSION['user']['username']) || (($_SESSION['user']['role'] ?? '') !== 'client')) {
    header('Location: ./login.php');
    exit();
}
require_once('../php/connexionBD.php');
$bdd = ConnexionBD::getInstance();
ConnexionBD::ensureWorkflowTables();
$client = $_SESSION['user']['username'];
$req = $bdd->prepare("SELECT dr.*, d.nom_produit FROM deal_request dr JOIN demande d ON d.id_demande = dr.id_demande WHERE dr.client_username = :c ORDER BY dr.created_at DESC, dr.id_deal DESC");
$req->execute(['c' => $client]);
$rows = $req->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Notifications client</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/notifications.css">
</head>
<body>
  <header class="top-header">
    <a href="../html/client-interface.php" class="logo">
        <img class="logo-img" src="../files_profil/logo.png" alt="Importy">
    </a>
</header>
  <main class="notif-wrap">
    <h2>Demandes des vendeurs</h2>
    <?php foreach ($rows as $r): ?>
      <article class="notif-card">
        <h3><?= htmlspecialchars($r['nom_produit']) ?></h3>
        <p class="notif-meta">
          Vendeur:
          <a class="vendor-link" href="./vendor_profile.php?vendeur=<?= urlencode($r['vendeur_username']) ?>">
            <?= htmlspecialchars($r['vendeur_username']) ?>
          </a>
          | Prix: <?= htmlspecialchars($r['prix_propose']) ?> TND
          | Date: <?= htmlspecialchars($r['created_at']) ?>
        </p>
        <p><?= nl2br(htmlspecialchars($r['message'])) ?></p>
        <div class="notif-actions">
          <?php if (trim(strtolower($r['status'])) !== 'accepte'): ?>
            <form method="POST" action="../php/accepter_offre.php" style="display:inline;">
              <input type="hidden" name="id_deal" value="<?= (int)$r['id_deal'] ?>">
              <button type="submit" class="btn-accept">Accepter offre</button>
            </form>
          <?php else: ?>
            <span class="status-label">Offre acceptée</span>
          <?php endif; ?>
          <a href="./messages.php?deal=<?= (int)$r['id_deal'] ?>">Ouvrir chat</a>
          <a href="./vendor_profile.php?vendeur=<?= urlencode($r['vendeur_username']) ?>">Voir profil vendeur</a>
        </div>
      </article>
    <?php endforeach; ?>
    <?php if (empty($rows)): ?><p>Aucune notification.</p><?php endif; ?>
  </main>
</body>
</html>
