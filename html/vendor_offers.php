<?php
session_start();
if (empty($_SESSION['user']['username']) || (($_SESSION['user']['role'] ?? '') !== 'vendeur')) {
    header('Location: ./login.php');
    exit();
}
require_once('../php/connexionBD.php');
$bdd = ConnexionBD::getInstance();
ConnexionBD::ensureWorkflowTables();
$vendeur = $_SESSION['user']['username'];
$req = $bdd->prepare("SELECT dr.*, d.nom_produit FROM deal_request dr JOIN demande d ON d.id_demande = dr.id_demande WHERE dr.vendeur_username = :v ORDER BY dr.created_at DESC, dr.id_deal DESC");
$req->execute(['v' => $vendeur]);
$rows = $req->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <title>Mes offres envoyees</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<header class="top-header simple-client-header">
        <button id="menuBtn" class="menu-btn" type="button" aria-label="Ouvrir le menu">
            <i class="fa-solid fa-align-justify"></i>
        </button>

        <a class="logo" href="../php/page_vendeur.php" aria-label="Importy - Espace vendeur">
            <img class="logo-img" src="../files_profil/logo.png" alt="Importy">
        </a>

        <div class="icons quick-actions">
            <a href="../html/vendor_offers.php" class="icon-item">
                <i class="fa-solid fa-paper-plane" style="color:#B197FC;"></i>
                <span>Mes offres</span>
            </a>
            <a href="../html/messages.php" class="icon-item">
                <i class="fa-solid fa-envelope" style="color:#B197FC;"></i>
                <span>Messages</span>
            </a>
            <a href="../html/mon%20compte.php" class="icon-item">
                <i class="fa-regular fa-user" style="color:#74C0FC;"></i>
                <span>Mon compte</span>
            </a>
            <a href="../php/logout.php" class="icon-item">
                <i class="fa-solid fa-right-from-bracket" style="color:#74C0FC;"></i>
                <span>Logout</span>
            </a>
        </div>
    </header>
      <main style="max-width:900px;margin:24px auto;padding:0 16px;">
    <h2>Mes offres envoyees</h2>
    <?php foreach ($rows as $r): ?>
      <article style="background:#fff;border:1px solid #ececec;border-radius:12px;padding:12px;margin-bottom:10px;">
        <h3><?= htmlspecialchars($r['nom_produit']) ?></h3>
        <p>Client: <?= htmlspecialchars($r['client_username']) ?> | Prix: <?= htmlspecialchars($r['prix_propose']) ?> TND | Date: <?= htmlspecialchars($r['created_at']) ?></p>
        <p><?= nl2br(htmlspecialchars($r['message'])) ?></p>
        <a href="./messages.php?deal=<?= (int)$r['id_deal'] ?>">Ouvrir chat</a>
      </article>
    <?php endforeach; ?>
    <?php if (empty($rows)): ?><p>Aucune offre envoyee.</p><?php endif; ?>
  </main>
</body>
</html>
