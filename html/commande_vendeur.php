<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'vendeur') {
    die("Accès refusé");
}

require_once(__DIR__ . '/../php/connexionBD.php');

$bdd = ConnexionBD::getInstance();
$vendeur = $_SESSION['user']['username'];

$type = $_GET['type'] ?? 'all';

$sql = "SELECT * FROM commandes WHERE vendeur = :vendeur";

if ($type === 'panier') {
    $sql .= " AND source = 'panier'";
}

if ($type === 'demande') {
    $sql .= " AND source = 'demande'";
}

$sql .= " ORDER BY created_at DESC";

$req = $bdd->prepare($sql);
$req->execute([
    'vendeur' => $vendeur
]);

$commandes = $req->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mes commandes</title>

    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/commande_vendeur.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

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

        <a href="/html/commande_vendeur.php" class="icon-item">
            <i class="fa-solid fa-handshake" style="color:#B197FC;"></i>
            <span>Mes commandes</span>
        </a>

        <a href="/html/vendor_offers.php" class="icon-item">
            <i class="fa-solid fa-paper-plane" style="color:#B197FC;"></i>
            <span>Mes offres</span>
        </a>

        <a href="/html/messages.php" class="icon-item">
            <i class="fa-solid fa-envelope" style="color:#B197FC;"></i>
            <span>Messages</span>
        </a>

        <a href="/html/mon%20compte.php" class="icon-item">
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
            <a href="../html/vendor_offers.php"><i class="fa-solid fa-paper-plane"></i> Mes offres</a>
            <a href="../html/commande_vendeur.php"><i class="fa-solid fa-handshake"></i> Mes commandes </a>
            <a href="../html/messages.php"><i class="fa-solid fa-envelope"></i> Messages</a>
            <a href="../html/mon compte.php"><i class="fa-regular fa-user"></i> Mon compte</a> <a href="/php/logout.php" id="logoutLink"><i class="fa-solid fa-right-from-bracket"></i> Se deconnecter</a>
    </div>

</aside>

<main class="vendor-shell">

    <section class="vendor-hero-card">
        <div>
            <div class="vendor-kicker">Gestion vendeur</div>
            <h2>Mes commandes</h2>
            <p class="vendor-hero-text">
                Suivez vos commandes, filtrez selon leur origine et modifiez leur statut.
            </p>
        </div>

        <div class="vendor-pill">
            <?= count($commandes) ?> commande(s)
        </div>
    </section>

    <div class="vendor-filter-row">
        <a class="vendor-filter-chip <?= $type === 'all' ? 'active' : '' ?>" href="?type=all">
            Toutes
        </a>

        <a class="vendor-filter-chip <?= $type === 'panier' ? 'active' : '' ?>" href="?type=panier">
            Depuis panier
        </a>

        <a class="vendor-filter-chip <?= $type === 'demande' ? 'active' : '' ?>" href="?type=demande">
            Après demande
        </a>
    </div>

    <?php if (empty($commandes)): ?>

        <div class="vendor-empty-box">
            <h3>Aucune commande trouvée</h3>
            <p>Vous n’avez pas encore de commande pour ce filtre.</p>
        </div>

    <?php else: ?>

        <div class="vendor-list">

            <?php foreach ($commandes as $commande): ?>

                <div class="vendor-record-card">

                    <div class="vendor-record-content">

                        <div class="vendor-record-head">
                            <div>
                                <h3>
                                    <?php if ($commande['source'] === 'panier'): ?>
                                        Commande panier
                                    <?php else: ?>
                                        Commande après demande
                                    <?php endif; ?>
                                </h3>

                                <div class="vendor-meta-row">
                                    <span class="vendor-chip">
                                        <i class="fa-regular fa-user"></i>
                                        <?= htmlspecialchars($commande['client']) ?>
                                    </span>

                                    <span class="vendor-chip">
                                        <i class="fa-regular fa-calendar"></i>
                                        <?= htmlspecialchars($commande['created_at']) ?>
                                    </span>

                                    <span class="vendor-chip strong">
                                        <?= htmlspecialchars($commande['source']) ?>
                                    </span>
                                </div>
                            </div>

                            <span class="vendor-status-chip <?= htmlspecialchars($commande['statut']) ?>">
                                <?= htmlspecialchars($commande['statut']) ?>
                            </span>
                        </div>

                        <p>
                            <strong>Total :</strong>
                            <?= htmlspecialchars($commande['total']) ?> DT
                        </p>

                        <form method="POST" action="/php/update_commande.php" class="vendor-status-form">
                            <input type="hidden" name="id" value="<?= $commande['id'] ?>">

                            <label>Changer le statut :</label>

                            <select name="statut">
                    
        
                                <option value="termine">Terminé</option>
                                <option value="annule">Annulé</option>
                            </select>

                            <button type="submit" class="btn-link">Modifier</button>
                        </form>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

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