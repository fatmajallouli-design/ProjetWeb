<?php
session_start();
if (empty($_SESSION['user']['username']) || (($_SESSION['user']['role'] ?? '') !== 'vendeur')) {
    header('Location: /login.php');
    exit();
}
require_once(__DIR__ . '/../php/connexionBD.php');
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
  <link rel="stylesheet" href="../css/commande_vendeur.css">
</head>
<body>
<header class="top-header simple-client-header">
        <button id="menuBtn" class="menu-btn" type="button" aria-label="Ouvrir le menu">
            <i class="fa-solid fa-align-justify"></i>
        </button>

        <a class="logo" href="/php/page_vendeur.php" aria-label="Importy - Espace vendeur">
            <img class="logo-img" src="/files_profil/logo.png" alt="Importy">
        </a>

        <div class="icons quick-actions">
             <a href="/commande_vendeur.php" class="icon-item">
                <i class="fa-solid fa-handshake" style="color:#B197FC;"></i>
                <span>Mes commandes</span>
            </a>
            <a href="/vendor_offers.php" class="icon-item">
                <i class="fa-solid fa-paper-plane" style="color:#B197FC;"></i>
                <span>Mes offres</span>
            </a>
            <a href="/messages.php" class="icon-item">
                <i class="fa-solid fa-envelope" style="color:#B197FC;"></i>
                <span>Messages</span>
            </a>
            <a href="/mon%20compte.php" class="icon-item">
                <i class="fa-regular fa-user" style="color:#74C0FC;"></i>
                <span>Mon compte</span>
            </a>
            <a href="/php/logout.php" class="icon-item">
                <i class="fa-solid fa-right-from-bracket" style="color:#74C0FC;"></i>
                <span>Logout</span>
            </a>
        </div>
    </header>
    <div class="overlay" id="overlay"></div>

    <aside class="side-menu client-side-menu" id="sideMenu" aria-hidden="true">
        <div class="side-header">
            <a class="brand" href="/php/page_vendeur.php" aria-label="Importy - Espace vendeur">
                <img class="brand-img" src="/files_profil/logo.png" alt="Importy">
            </a>
            <button class="menu-close-btn" id="closeMenu" type="button" aria-label="Fermer le menu">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="section">
            <h4>Navigation</h4>
            <a href="/php/page_vendeur.php"><i class="fa-solid fa-store"></i> Espace vendeur</a>
            <a href="/vendor_offers.php"><i class="fa-solid fa-paper-plane"></i> Mes offres</a>
            <a href="/messages.php"><i class="fa-solid fa-envelope"></i> Messages</a>
            <a href="/mon%20compte.php"><i class="fa-regular fa-user"></i> Mon compte</a>
            <a href="/php/logout.php" id="logoutLink"><i class="fa-solid fa-right-from-bracket"></i> Se deconnecter</a>
        </div>
    </aside>

      <main class="vendor-shell">
    <section class="vendor-hero-card">
      <div>
        <p class="vendor-kicker">Suivi commercial</p>
        <h2>Mes offres envoyees</h2>
        <p class="vendor-hero-text">Retrouvez toutes vos propositions, leurs montants et accedez rapidement au chat associe.</p>
      </div>
      <div class="vendor-pill"><?= count($rows) ?> offre(s)</div>
    </section>

    <?php if (!empty($rows)): ?>
      <section class="vendor-list">
        <?php foreach ($rows as $r): ?>
          <article class="vendor-record-card">
            <div class="vendor-record-head">
              <div>
                <h3><?= htmlspecialchars($r['nom_produit']) ?></h3>
                <div class="vendor-meta-row">
                  <span class="vendor-chip"><i class="fa-regular fa-user"></i> <?= htmlspecialchars($r['client_username']) ?></span>
                  <span class="vendor-chip"><i class="fa-regular fa-calendar"></i> <?= htmlspecialchars($r['created_at']) ?></span>
                  <span class="vendor-chip strong"><i class="fa-solid fa-coins"></i> <?= htmlspecialchars($r['prix_propose']) ?> TND</span>
                </div>
              </div>
              <span class="vendor-status-chip <?= strtolower(trim((string)$r['status'])) === 'accepte' ? 'termine' : 'en-cours' ?>">
                <?= htmlspecialchars(ucfirst($r['status'])) ?>
              </span>
            </div>

            <p class="vendor-message-preview"><?= nl2br(htmlspecialchars($r['message'])) ?></p>

            <a class="btn-link vendor-action-link" href="../html/messages.php?deal=<?= (int)$r['id_deal'] ?>">Ouvrir chat</a>
          </article>
        <?php endforeach; ?>
      </section>
    <?php else: ?>
      <section class="vendor-empty-box">
        <h3>Aucune offre envoyee</h3>
        <p>Des que vous repondez a une demande client, vos offres apparaitront ici.</p>
      </section>
    <?php endif; ?>
  </main>
   <script>
        const menuBtn = document.getElementById('menuBtn');
        const sideMenu = document.getElementById('sideMenu');
        const closeMenu = document.getElementById('closeMenu');
        const overlay = document.getElementById('overlay');
        const logoutLink = document.getElementById('logoutLink');

        function openMenu() {
            sideMenu.classList.add('active');
            sideMenu.setAttribute('aria-hidden', 'false');
            overlay.style.display = 'block';
        }

        function closeAll() {
            sideMenu.classList.remove('active');
            sideMenu.setAttribute('aria-hidden', 'true');
            overlay.style.display = 'none';
        }

        if (menuBtn && closeMenu && overlay) {
            menuBtn.addEventListener('click', openMenu);
            closeMenu.addEventListener('click', closeAll);
            overlay.addEventListener('click', closeAll);
        }

        if (logoutLink) {
            logoutLink.addEventListener('click', function (event) {
                if (!window.confirm('Est tu sure que tu veux te deconnecter ?')) {
                    event.preventDefault();
                }
            });
        }
    </script>
</body>
</html>

