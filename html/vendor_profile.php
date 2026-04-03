<?php
session_start();
if (empty($_SESSION['user']['username'])) {
    header('Location: ./login.php');
    exit();
}
require_once(__DIR__ . '/../php/connexionBD.php');
$bdd = ConnexionBD::getInstance();
ConnexionBD::ensureWorkflowTables();

$viewer = $_SESSION['user']['username'];
$viewerRole = $_SESSION['user']['role'] ?? '';
$vendeur = trim($_GET['vendeur'] ?? '');
if ($vendeur === '') {
    header('Location: ./client-interface.php');
    exit();
}

$vStmt = $bdd->prepare("SELECT username, email, adresse, num_tel, idphoto FROM vendeur WHERE username = :u");
$vStmt->execute(['u' => $vendeur]);
$vendor = $vStmt->fetch(PDO::FETCH_ASSOC);
if (!$vendor) {
    header('Location: ./client-interface.php');
    exit();
}

$rStmt = $bdd->prepare("SELECT client_username, rating, commentaire, created_at FROM review WHERE vendeur_username = :v ORDER BY created_at DESC, id_review DESC");
$rStmt->execute(['v' => $vendeur]);
$reviews = $rStmt->fetchAll(PDO::FETCH_ASSOC);

$avgStmt = $bdd->prepare("SELECT ROUND(AVG(rating),2) AS avg_rating, COUNT(*) AS total FROM review WHERE vendeur_username = :v");
$avgStmt->execute(['v' => $vendeur]);
$stats = $avgStmt->fetch(PDO::FETCH_ASSOC);

$eligibleDeals = [];
if ($viewerRole === 'client') {
    $dealStmt = $bdd->prepare("
        SELECT dr.id_deal, dr.created_at, d.nom_produit
        FROM deal_request dr
        JOIN demande d ON d.id_demande = dr.id_demande
        LEFT JOIN review r ON r.id_deal = dr.id_deal AND r.client_username = :client
        WHERE dr.client_username = :client
          AND dr.vendeur_username = :vendeur
          AND r.id_review IS NULL
        ORDER BY dr.created_at DESC, dr.id_deal DESC
    ");
    $dealStmt->execute([
        'client' => $viewer,
        'vendeur' => $vendeur
    ]);
    $eligibleDeals = $dealStmt->fetchAll(PDO::FETCH_ASSOC);
}
$req = $bdd->prepare("SELECT idphoto FROM vendeur WHERE username = :u");
$req->execute(["u" => $vendeur]);
$data = $req->fetch();

$vendeur_photo = $data['idphoto'] ?? 'default.png';

function normalizeProfilePhoto(?string $path): string {
    $raw = trim((string)$path);
    if ($raw === '') return '/files_profil/logo.png';
    $raw = str_replace('\\', '/', $raw);
    if (strpos($raw, '/files_profil/') === 0 || strpos($raw, '/files_profil/') === 0) {
        return $raw;
    }
    if (strpos($raw, '../files_profil/') === 0) {
        return '/' . ltrim(substr($raw, 3), '/');
    }
    return $raw;
}

$vendeur_photo_url = normalizeProfilePhoto($vendeur_photo);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profil vendeur - <?= htmlspecialchars($vendeur) ?></title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/vendor_profile.css">
</head>
<body>
  <header class="top-header"><a href="/client-interface.php" class="logo"><img class="logo-img" src="/files_profil/logo.png" alt="Importy"></a></header>
  <main class="wrap">
    <section class="card">
      <div class="profile-header">
  <img src="<?= htmlspecialchars($vendeur_photo_url) ?>" class="avatar">

        <h2>
          Profil vendeur: <?= htmlspecialchars($vendeur) ?>
        </h2>
      </div>
      <p class="meta">Email: <?= htmlspecialchars($vendor['email'] ?? '') ?></p>
      <p class="meta">Adresse: <?= htmlspecialchars($vendor['adresse'] ?? '') ?></p>
      <p class="meta">Telephone: <?= htmlspecialchars($vendor['num_tel'] ?? '') ?></p>
      <p><strong>Note moyenne:</strong> <?= htmlspecialchars($stats['avg_rating'] ?? '0') ?>/5 (<?= htmlspecialchars($stats['total'] ?? '0') ?> avis)</p>
    </section>

    <section class="card">
      <h3>Avis clients</h3>
      <?php foreach ($reviews as $rev): ?>
        <div class="review">
          <p>
            <strong><?= htmlspecialchars($rev['client_username']) ?></strong>
            - <span class="review-stars"><?= str_repeat('â˜…', (int)$rev['rating']) . str_repeat('â˜†', max(0, 5 - (int)$rev['rating'])) ?></span>
            - <?= htmlspecialchars($rev['created_at']) ?>
          </p>
          <p><?= nl2br(htmlspecialchars($rev['commentaire'] ?? '')) ?></p>
        </div>
      <?php endforeach; ?>
      <?php if (empty($reviews)): ?><p>Aucun avis pour le moment.</p><?php endif; ?>
    </section>

    <?php if ($viewerRole === 'client'): ?>
    <section class="card">
      <h3>Laisser un nouvel avis</h3>
      <form class="review-form" action="/php/leave_review_profile.php" method="post">
        <input type="hidden" name="vendeur_username" value="<?= htmlspecialchars($vendeur) ?>">
        <select name="id_deal" required>
          <option value="">Selectionner le deal concernÃ©</option>
          <?php foreach ($eligibleDeals as $d): ?>
            <option value="<?= (int)$d['id_deal'] ?>">
              Deal #<?= (int)$d['id_deal'] ?> - <?= htmlspecialchars($d['nom_produit']) ?> (<?= htmlspecialchars($d['created_at']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
        <input type="number" name="rating" min="1" max="5" required placeholder="Note (1-5)">
        <textarea name="commentaire" rows="3" placeholder="Votre avis"></textarea>
        <button type="submit" <?= empty($eligibleDeals) ? 'disabled' : '' ?>>Publier avis</button>
      </form>
      <?php if (empty($eligibleDeals)): ?>
        <p class="meta">Vous avez deja note tous vos deals avec ce vendeur, ou aucun deal n'existe encore.</p>
      <?php endif; ?>
    </section>
    <?php endif; ?>
  </main>
</body>
</html>

