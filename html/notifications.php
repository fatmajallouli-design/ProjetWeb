<?php
session_start();
if (empty($_SESSION['user']['username'])) {
  header('Location: /login.php');
  exit();
}
$role = $_SESSION['user']['role'] ?? 'client';
require_once(__DIR__ . '/../php/connexionBD.php');
$bdd = ConnexionBD::getInstance();
ConnexionBD::ensureWorkflowTables();
$client = $_SESSION['user']['username'];
$homeUrl = ($role === 'vendeur') ? '/php/page_vendeur.php' : '/client-interface.php';
if ($role === 'vendeur') {
  $req = $bdd->prepare("SELECT dr.*, d.nom_produit FROM deal_request dr JOIN demande d ON d.id_demande = dr.id_demande WHERE dr.vendeur_username = :c ORDER BY dr.created_at DESC, dr.id_deal DESC");
} else {
  $req = $bdd->prepare("SELECT dr.*, d.nom_produit FROM deal_request dr JOIN demande d ON d.id_demande = dr.id_demande WHERE dr.client_username = :c ORDER BY dr.created_at DESC, dr.id_deal DESC");
}
$req->execute(['c' => $client]);
$rows = $req->fetchAll(PDO::FETCH_ASSOC);

if ($role === 'vendeur') {
  $bdd->prepare("UPDATE deal_request SET vendeur_seen_at = NOW() WHERE vendeur_username = :u")->execute(['u' => $client]);
} else {
  $bdd->prepare("UPDATE deal_request SET client_seen_at = NOW() WHERE client_username = :u")->execute(['u' => $client]);
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Notifications</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/notifications.css">
</head>

<body>
  <header class="top-header">
    <div class="header-left">
      <a href="<?= $homeUrl ?>" class="logo">
        <img src="/files_profil/logo.png" alt="Importy" class="logo-img">
      </a>
    </div>
    <div class="header-center">
      <h1 class="title">Mes notifications</h1>
    </div>
    <div class="header-right">
      <?php if ($role == "client") {
        $chemin = "../html/client-interface.php";
      } else {
        $chemin = "/php/page_vendeur.php";
      }
      ?>
      <a href=<?= $chemin ?> class="header-btn retour-btn">← Retour à l'accueil</a>

    </div>
  </header>
  <main class="notif-wrap">
    <h2><?= $role === 'vendeur' ? 'Offres envoy&eacute;es' : 'Demandes des vendeurs' ?></h2>
    <?php foreach ($rows as $r): ?>
      <article class="notif-card">
        <h3><?= htmlspecialchars($r['nom_produit']) ?></h3>
        <p class="notif-meta">
          <?php if ($role === 'client'): ?>
            Vendeur:
            <a class="vendor-link" href="../html      /vendor_profile.php?vendeur=<?= urlencode($r['vendeur_username']) ?>">
              <?= htmlspecialchars($r['vendeur_username']) ?>
            </a>
          <?php else: ?>
            Client: <?= htmlspecialchars($r['client_username']) ?>
          <?php endif; ?>
          | Prix: <?= htmlspecialchars($r['prix_propose']) ?> TND
          | Date: <?= htmlspecialchars($r['created_at']) ?>
        </p>
        <p><?= nl2br(htmlspecialchars($r['message'])) ?></p>
        <div class="notif-actions">
          <?php if ($role === 'client'): ?>
            <?php if (trim(strtolower($r['status'])) !== 'accepte'): ?>
              <form method="POST" action="/php/accepter_offre.php" style="display:inline;">
                <input type="hidden" name="id_deal" value="<?= (int)$r['id_deal'] ?>">
                <button type="submit" class="btn-accept">Accepter offre</button>
              </form>
            <?php else: ?>
              <span class="status-label">Offre accept&eacute;e</span>
            <?php endif; ?>
            <a href="../html/vendor_profile.php?vendeur=<?= urlencode($r['vendeur_username']) ?>">Voir profil vendeur</a>
          <?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
    <?php if (empty($rows)): ?><p>Aucune notification.</p><?php endif; ?>
  </main>
</body>

</html>