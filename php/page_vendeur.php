<?php
session_start();
if (empty($_SESSION['user']['username']) || (($_SESSION['user']['role'] ?? '') !== 'vendeur')) {
    header('Location: ../html/login.php');
    exit();
}

require_once('connexionBD.php');
$bdd = ConnexionBD::getInstance();
ConnexionBD::ensureWorkflowTables();
$vendeur = $_SESSION['user']['username'];

$demandesStmt = $bdd->query("SELECT * FROM demande ORDER BY COALESCE(created_at, NOW()) DESC, id_demande DESC");
$demandes = $demandesStmt->fetchAll(PDO::FETCH_ASSOC);

$myProdStmt = $bdd->prepare("SELECT * FROM produit WHERE vendeur_username = :v ORDER BY created_at DESC, id_produit DESC");
$myProdStmt->execute(['v' => $vendeur]);
$myProduits = $myProdStmt->fetchAll(PDO::FETCH_ASSOC);

function resolveImagePath(?string $path): string {
    $raw = trim((string)$path);
    if ($raw === '') return '';
    $candidates = [
        $raw,
        str_replace('../files_produits/', '../files_produit/', $raw),
        str_replace('../files_demande/', '../files_produit/', $raw),
    ];
    foreach ($candidates as $candidate) {
        $resolved = realpath(__DIR__ . '/' . $candidate);
        if ($resolved !== false && is_file($resolved)) {
            return $candidate;
        }
    }
    return '';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importy - Espace vendeur</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
      body { background:#f7f7fb; }
      .wrap { max-width:1100px; margin:24px auto; padding:0 16px; }
      .grid { display:grid; gap:16px; grid-template-columns:1fr; }
      .card { background:#fff; border:1px solid #ececec; border-radius:14px; padding:14px; }
      .cards { display:grid; gap:14px; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); }
      .meta { color:#666; font-size:13px; margin:6px 0; }
      input, textarea, select { width:100%; padding:10px; border:1px solid #ddd; border-radius:10px; margin-bottom:8px; }
      button { border:none; border-radius:10px; padding:10px 12px; background:#6a5cff; color:#fff; cursor:pointer; }
      .top-links a { margin-right:10px; }
      .prod-img, .dem-img { width:100%; max-height:180px; object-fit:cover; border-radius:10px; margin-bottom:8px; }
    </style>
</head>
<body>
  <header class="top-header">
    <a class="logo"><img class="logo-img" src="../files_profil/logo.png" alt="Importy"></a>
    <div class="icons">
      <a href="../html/vendor_offers.php" class="icon-item"><i class="fa-solid fa-paper-plane"></i><span>Mes offres</span></a>
      <a href="../html/messages.php" class="icon-item"><i class="fa-solid fa-envelope"></i><span>Messages</span></a>
      <a href="../php/logout.php" class="icon-item"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a>
    </div>
  </header>

  <main class="wrap grid">
    <section class="card">
      <h2>Publier un produit</h2>
      <form action="../php/add_product.php" method="post" enctype="multipart/form-data">
        <input type="text" name="nom_produit" placeholder="Nom du produit" required>
        <input type="number" step="0.01" min="1" name="prix" placeholder="Prix" required>
        <select name="categorie" required>
          <option value="tous">Tous</option><option value="femme">Femme</option><option value="homme">Homme</option><option value="maison">Maison</option><option value="beaute">Beaute</option>
        </select>
        <textarea name="description" rows="3" placeholder="Description"></textarea>
        <input type="file" name="image" accept="image/*">
        <button type="submit">Poster produit</button>
      </form>
    </section>

    <section class="card">
      <h2>Mes produits postes</h2>
      <div class="cards">
        <?php foreach ($myProduits as $p): ?>
          <article class="card">
            <?php $prodImage = resolveImagePath($p['image_path'] ?? ''); ?>
            <?php if ($prodImage !== ''): ?><img class="prod-img" src="<?= htmlspecialchars($prodImage) ?>" alt="Produit"><?php endif; ?>
            <h3><?= htmlspecialchars($p['nom_produit']) ?></h3>
            <p class="meta"><?= htmlspecialchars($p['categorie']) ?> | <?= htmlspecialchars($p['prix']) ?> TND | <?= htmlspecialchars($p['created_at']) ?></p>
            <p><?= htmlspecialchars($p['description'] ?? '') ?></p>
          </article>
        <?php endforeach; ?>
        <?php if (empty($myProduits)): ?><p>Aucun produit poste pour le moment.</p><?php endif; ?>
      </div>
    </section>

    <section class="card">
      <h2>Demandes clients (vous pouvez proposer un deal)</h2>
      <div class="cards">
        <?php foreach ($demandes as $d): ?>
          <article class="card">
            <?php if (!empty($d['id_photo'])): ?><img class="dem-img" src="<?= htmlspecialchars($d['id_photo']) ?>" alt="Demande"><?php endif; ?>
            <h3><?= htmlspecialchars($d['nom_produit']) ?></h3>
            <p class="meta">Client: <?= htmlspecialchars($d['username']) ?> | Budget: <?= htmlspecialchars($d['prix']) ?> TND | Date: <?= htmlspecialchars($d['created_at'] ?? '') ?></p>
            <p><?= htmlspecialchars($d['description']) ?></p>
            <form action="../php/send_offer.php" method="post">
              <input type="hidden" name="id_demande" value="<?= (int)$d['id_demande'] ?>">
              <input type="number" name="prix_propose" min="1" step="0.01" placeholder="Votre prix propose" required>
              <textarea name="message" rows="2" placeholder="Votre message..." required></textarea>
              <button type="submit">Envoyer une offre</button>
            </form>
          </article>
        <?php endforeach; ?>
      </div>
    </section>
  </main>
</body>
</html>
