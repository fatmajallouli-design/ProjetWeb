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
  <style>
    body { background:#f6f7fb; }
    .notif-wrap { max-width:980px; margin:24px auto; padding:0 16px; }
    .notif-card {
      background:#fff; border:1px solid #e8e8ef; border-radius:14px; padding:14px; margin-bottom:12px;
      box-shadow:0 8px 22px rgba(0,0,0,0.04);
    }
    .notif-meta { color:#666; margin:6px 0; }
    .vendor-link { font-weight:700; color:#5b46e5; text-decoration:none; }
    .notif-actions a {
      display:inline-block; margin-right:10px; margin-top:8px; text-decoration:none;
      padding:8px 12px; border-radius:10px; background:#f2f0ff; color:#4b39d9;
    }
  </style>
</head>
<body>
  <header class="top-header"><a class="logo"><img class="logo-img" src="../files_profil/logo.png" alt="Importy"></a></header>
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
          <a href="./messages.php?deal=<?= (int)$r['id_deal'] ?>">Ouvrir chat</a>
          <a href="./vendor_profile.php?vendeur=<?= urlencode($r['vendeur_username']) ?>">Voir profil vendeur</a>
        </div>
      </article>
    <?php endforeach; ?>
    <?php if (empty($rows)): ?><p>Aucune notification.</p><?php endif; ?>
  </main>
</body>
</html>
