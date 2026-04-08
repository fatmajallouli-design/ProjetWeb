<?php
session_start();
if (empty($_SESSION['user']['username'])) {
  header('Location: /login.php');
  exit();
}

require_once(__DIR__ . '/../php/connexionBD.php');
$bdd = ConnexionBD::getInstance();
ConnexionBD::ensureWorkflowTables();

$viewer = $_SESSION['user']['username'];
$viewerRole = $_SESSION['user']['role'] ?? '';
$homeUrl = ($viewerRole === 'vendeur') ? '/php/page_vendeur.php' : '/html/client-interface.php';

$notifCount = 0;
$messageCount = 0;

try {
  $stmt = $bdd->prepare("
        SELECT COUNT(*) 
        FROM deal_request 
        WHERE client_username = :u
    ");
  $stmt->execute(['u' => $viewer]);
  $notifCount = (int)($stmt->fetchColumn() ?? 0);

  $stmt = $bdd->prepare("
        SELECT COUNT(*) 
        FROM message 
        WHERE receiver_username = :u
    ");
  $stmt->execute(['u' => $viewer]);
  $messageCount = (int)($stmt->fetchColumn() ?? 0);
} catch (PDOException $e) {
}

$vendeur = trim($_GET['vendeur'] ?? '');
if ($vendeur === '') {
  header('Location: ' . $homeUrl);
  exit();
}

$vStmt = $bdd->prepare("
    SELECT username, email, adresse, num_tel, idphoto, created_at
    FROM vendeur
    WHERE username = :u
");
$vStmt->execute(['u' => $vendeur]);
$vendor = $vStmt->fetch(PDO::FETCH_ASSOC);

if (!$vendor) {
  header('Location: ' . $homeUrl);
  exit();
}

$rStmt = $bdd->prepare("
    SELECT client_username, rating, commentaire, created_at
    FROM review
    WHERE vendeur_username = :v
    ORDER BY created_at DESC, id_review DESC
");
$rStmt->execute(['v' => $vendeur]);
$reviews = $rStmt->fetchAll(PDO::FETCH_ASSOC);

$avgStmt = $bdd->prepare("
    SELECT ROUND(AVG(rating), 1) AS avg_rating, COUNT(*) AS total
    FROM review
    WHERE vendeur_username = :v
");
$avgStmt->execute(['v' => $vendeur]);
$stats = $avgStmt->fetch(PDO::FETCH_ASSOC);

$commandesStmt = $bdd->prepare("
    SELECT COUNT(*) AS total_commandes
    FROM commandes
    WHERE vendeur = :vendeur
");
$commandesStmt->execute(['vendeur' => $vendeur]);
$totalCommandes = (int)($commandesStmt->fetchColumn() ?? 0);

$commandesTermineesStmt = $bdd->prepare("
    SELECT COUNT(*) AS total_terminees
    FROM commandes
    WHERE vendeur = :vendeur AND statut = 'termine'
");
$commandesTermineesStmt->execute(['vendeur' => $vendeur]);
$totalCommandesTerminees = (int)($commandesTermineesStmt->fetchColumn() ?? 0);

$dealsAcceptesStmt = $bdd->prepare("
    SELECT COUNT(*) AS total_acceptes
    FROM deal_request
    WHERE vendeur_username = :vendeur AND status = 'accepte'
");
$dealsAcceptesStmt->execute(['vendeur' => $vendeur]);
$totalDealsAcceptes = (int)($dealsAcceptesStmt->fetchColumn() ?? 0);

$produitsStmt = $bdd->prepare("
    SELECT COUNT(*) AS total_produits
    FROM produit
    WHERE vendeur_username = :vendeur
");
$produitsStmt->execute(['vendeur' => $vendeur]);
$totalProduits = (int)($produitsStmt->fetchColumn() ?? 0);


/**$eligibleDeals contient les deals encore notables.yijm il client inotihom maantha manotahoumch i9bl */
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
          AND dr.status = 'accepte'
        ORDER BY dr.created_at DESC, dr.id_deal DESC
    ");
  $dealStmt->execute([
    'client' => $viewer,
    'vendeur' => $vendeur
  ]);
  $eligibleDeals = $dealStmt->fetchAll(PDO::FETCH_ASSOC);
}

$vendeur_photo = $vendor['idphoto'] ?? '';
if (!empty($vendeur_photo)) {
  $vendeur_photo_url = str_replace('../', '/', $vendeur_photo);
} else {
  $vendeur_photo_url = '/files_profil/logo.png';
}

$avgRating = (float)($stats['avg_rating'] ?? 0);
$totalReviews = (int)($stats['total'] ?? 0);

function renderStars(float $rating): string
{
  $full = (int)floor($rating);
  $empty = 5 - $full;
  return str_repeat('★', $full) . str_repeat('☆', $empty);
}

$membreDepuis = !empty($vendor['created_at']) ? date('d/m/Y', strtotime($vendor['created_at'])) : '-';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profil vendeur - <?= htmlspecialchars($vendeur) ?></title>

  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/vendor_profile.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

  <header class="top-header">
    <a href="<?= htmlspecialchars($homeUrl) ?>" class="logo">
      <img class="logo-img" src="/files_profil/logo.png" alt="Importy">
    </a>

    <div class="icons quick-actions">
      <a href="/html/mon%20compte.php" class="icon-item">
        <i class="fa-regular fa-user" style="color:#B197FC;"></i>
        <span>Mon compte</span>
      </a>

      <a href="/html/panier.php" class="icon-item">
        <i class="fa-solid fa-bag-shopping" style="color:#B197FC;"></i>
        <span>Panier</span>
      </a>

      <a href="/html/demande.php" class="icon-item">
        <i class="fa-solid fa-plus" style="color:#74C0FC;"></i>
        <span>Demande</span>
      </a>

      <a href="/html/mes_demandes.php" class="icon-item">
        <i class="fa-solid fa-list-check" style="color:#74C0FC;"></i>
        <span>Mes demandes</span>
      </a>

      <a href="/html/notifications.php" class="icon-item">
        <i class="fa-solid fa-bell" style="color:#74C0FC;"></i>
        <span>Notification</span>
        <?php if ($notifCount > 0): ?>
          <span class="badge"><?= htmlspecialchars((string)$notifCount) ?></span>
        <?php endif; ?>
      </a>

      <a href="/html/messages.php" class="icon-item">
        <i class="fa-solid fa-envelope" style="color:#B197FC;"></i>
        <span>Messages</span>
        <?php if ($messageCount > 0): ?>
          <span class="badge"><?= htmlspecialchars((string)$messageCount) ?></span>
        <?php endif; ?>
      </a>
    </div>
  </header>

  <main class="vendor-profile-page">
    <div class="vendor-wrap">

      <section class="vendor-hero">
        <div class="vendor-main-card">
          <div class="vendor-photo-col">
            <img src="<?= htmlspecialchars($vendeur_photo_url) ?>" class="vendor-avatar" alt="Photo vendeur">
          </div>

          <div class="vendor-info-col">
            <div class="vendor-topline">Profil vendeur</div>
            <h1><?= htmlspecialchars($vendeur) ?></h1>

            <div class="vendor-badges">
              <span class="soft-badge">
                <i class="fa-solid fa-calendar-check"></i>
                Membre depuis <?= htmlspecialchars($membreDepuis) ?>
              </span>

              <?php if ($totalCommandes >= 5): ?>
                <span class="soft-badge success-badge">
                  <i class="fa-solid fa-star"></i>
                  Vendeur actif
                </span>
              <?php else: ?>
                <span class="soft-badge neutral-badge">
                  <i class="fa-solid fa-seedling"></i>
                  Nouveau profil
                </span>
              <?php endif; ?>
            </div>

            <div class="vendor-contact-grid">
              <div class="info-box">
                <span class="info-label">Email</span>
                <span class="info-value"><?= htmlspecialchars($vendor['email'] ?? '') ?></span>
              </div>

              <div class="info-box">
                <span class="info-label">Adresse</span>
                <span class="info-value"><?= htmlspecialchars($vendor['adresse'] ?? '') ?></span>
              </div>

              <div class="info-box">
                <span class="info-label">Téléphone</span>
                <span class="info-value"><?= htmlspecialchars($vendor['num_tel'] ?? '') ?></span>
              </div>
            </div>
          </div>

          <div class="vendor-rating-col">
            <div class="rating-panel">
              <div class="rating-caption">Note moyenne</div>
              <div class="rating-big"><?= number_format($avgRating, 1) ?>/5</div>
              <div class="rating-visual"><?= renderStars($avgRating) ?></div>
              <div class="rating-sub"><?= $totalReviews ?> avis client<?= $totalReviews > 1 ? 's' : '' ?></div>
            </div>
          </div>
        </div>
      </section>

      <section class="stats-grid">
        <article class="stat-card">
          <div class="stat-icon"><i class="fa-solid fa-cart-shopping"></i></div>
          <div class="stat-value"><?= $totalCommandes ?></div>
          <div class="stat-label">Commandes reçues</div>
        </article>

        <article class="stat-card">
          <div class="stat-icon"><i class="fa-solid fa-circle-check"></i></div>
          <div class="stat-value"><?= $totalCommandesTerminees ?></div>
          <div class="stat-label">Commandes terminées</div>
        </article>

        <article class="stat-card">
          <div class="stat-icon"><i class="fa-solid fa-handshake"></i></div>
          <div class="stat-value"><?= $totalDealsAcceptes ?></div>
          <div class="stat-label">Deals acceptés</div>
        </article>

        <article class="stat-card">
          <div class="stat-icon"><i class="fa-solid fa-box-open"></i></div>
          <div class="stat-value"><?= $totalProduits ?></div>
          <div class="stat-label">Produits publiés</div>
        </article>
      </section>

      <section class="content-card reviews-card">
        <div class="section-title-row">
          <div>
            <h2>Avis clients</h2>
            <p>Les retours laissés par les clients de ce vendeur.</p>
          </div>
        </div>

        <?php if (!empty($reviews)): ?>
          <div class="reviews-list">
            <?php foreach ($reviews as $rev): ?>
              <article class="review-item">
                <div class="review-item-head">
                  <div>
                    <div class="review-user"><?= htmlspecialchars($rev['client_username']) ?></div>
                    <div class="review-rating"><?= str_repeat('★', (int)$rev['rating']) . str_repeat('☆', max(0, 5 - (int)$rev['rating'])) ?></div>
                  </div>
                  <div class="review-date"><?= htmlspecialchars(date('d/m/Y H:i', strtotime($rev['created_at']))) ?></div>
                </div>

                <div class="review-text">
                  <?= nl2br(htmlspecialchars($rev['commentaire'] ?? '')) ?>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="empty-box">
            Aucun avis pour le moment.
          </div>
        <?php endif; ?>
      </section>

      <?php if ($viewerRole === 'client'): ?>
        <section class="content-card review-form-card">
          <div class="section-title-row">
            <div>
              <h2>Laisser un nouvel avis</h2>
              <p>Évaluez votre expérience avec ce vendeur.</p>
            </div>
          </div>

          <form class="review-form" action="/php/leave_review_profile.php" method="post">
            <input type="hidden" name="vendeur_username" value="<?= htmlspecialchars($vendeur) ?>">

            <div class="field-block">
              <label for="id_deal">Deal concerné</label>
              <select name="id_deal" id="id_deal" required>
                <option value="">Sélectionner le deal concerné</option>
                <?php foreach ($eligibleDeals as $d): ?>
                  <option value="<?= (int)$d['id_deal'] ?>">
                    Deal #<?= (int)$d['id_deal'] ?> - <?= htmlspecialchars($d['nom_produit']) ?> (<?= htmlspecialchars(date('d/m/Y', strtotime($d['created_at']))) ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="field-block">
              <label>Votre note</label>
              <div class="stars clean-stars">
                <input type="radio" name="rating" id="star5" value="5" required><label for="star5">★</label>
                <input type="radio" name="rating" id="star4" value="4"><label for="star4">★</label>
                <input type="radio" name="rating" id="star3" value="3"><label for="star3">★</label>
                <input type="radio" name="rating" id="star2" value="2"><label for="star2">★</label>
                <input type="radio" name="rating" id="star1" value="1"><label for="star1">★</label>
              </div>
            </div>

            <div class="field-block">
              <label for="commentaire">Votre commentaire</label>
              <textarea name="commentaire" id="commentaire" rows="5" placeholder="Partagez votre avis..."></textarea>
            </div>

            <div class="form-actions">
              <button type="submit" <?= empty($eligibleDeals) ? 'disabled' : '' ?>>Publier l’avis</button>
            </div>
          </form>

          <?php if (empty($eligibleDeals)): ?>
            <div class="empty-box soft-note">
              Vous avez déjà noté tous vos deals acceptés avec ce vendeur, ou aucun deal accepté n’existe encore.
            </div>
          <?php endif; ?>
        </section>
      <?php endif; ?>

    </div>
  </main>

</body>

</html>