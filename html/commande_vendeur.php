<?php
session_start();

if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'vendeur') {
    die("Acces refuse");
}

require_once(__DIR__ . '/../php/connexionBD.php');
$bdd = ConnexionBD::getInstance();
ConnexionBD::ensureWorkflowTables();

$vendeur = $_SESSION['user']['username'];
$filter = $_GET['type'] ?? 'all';

$sourceCondition = '';
if ($filter === 'panier') {
    $sourceCondition = " AND COALESCE(c.source, 'demande') = 'panier'";
} elseif ($filter === 'demande') {
    $sourceCondition = " AND COALESCE(c.source, 'demande') = 'demande'";
}

$req = $bdd->prepare("
    SELECT c.*, d.nom_produit, d.description, d.id_photo, d.lien_produit
    FROM commandes c
    LEFT JOIN demande d ON d.id_demande = c.id_demande
    WHERE c.vendeur = :vendeur $sourceCondition
    ORDER BY c.created_at DESC, c.id DESC
");
$req->execute(["vendeur" => $vendeur]);
$commandes = $req->fetchAll(PDO::FETCH_ASSOC);

$countsStmt = $bdd->prepare("
    SELECT
        COUNT(*) AS total_all,
        SUM(CASE WHEN COALESCE(source, 'demande') = 'panier' THEN 1 ELSE 0 END) AS total_panier,
        SUM(CASE WHEN COALESCE(source, 'demande') = 'demande' THEN 1 ELSE 0 END) AS total_demande
    FROM commandes
    WHERE vendeur = :vendeur
");
$countsStmt->execute(['vendeur' => $vendeur]);
$counts = $countsStmt->fetch(PDO::FETCH_ASSOC) ?: [];

$commandeIds = array_map(static fn($cmd) => (int)$cmd['id'], $commandes);
$itemsByCommande = [];

if (!empty($commandeIds)) {
    $placeholders = implode(',', array_fill(0, count($commandeIds), '?'));
    $itemReq = $bdd->prepare("
        SELECT id_commande, nom_produit, quantite, prix_unitaire, sous_total, image_path
        FROM commande_item
        WHERE id_commande IN ($placeholders)
        ORDER BY id ASC
    ");
    $itemReq->execute($commandeIds);

    foreach ($itemReq->fetchAll(PDO::FETCH_ASSOC) as $item) {
        $itemsByCommande[(int)$item['id_commande']][] = $item;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mes commandes</title>
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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

    <main class="vendor-shell">
        <section class="vendor-hero-card">
            <div>
                <p class="vendor-kicker">Gestion vendeur</p>
                <h2>Mes commandes</h2>
                <p class="vendor-hero-text">Suivez chaque commande, filtrez selon son origine et retrouvez rapidement les articles lies au panier ou aux demandes clients.</p>
            </div>
            <div class="vendor-pill"><?= count($commandes) ?> commande(s)</div>
        </section>

        <section class="vendor-filter-row">
            <a href="?type=all" class="vendor-filter-chip <?= $filter === 'all' ? 'active' : '' ?>">
                Toutes les commandes
                <span><?= (int)($counts['total_all'] ?? 0) ?></span>
            </a>
            <a href="?type=panier" class="vendor-filter-chip <?= $filter === 'panier' ? 'active' : '' ?>">
                Commandes depuis le panier
                <span><?= (int)($counts['total_panier'] ?? 0) ?></span>
            </a>
            <a href="?type=demande" class="vendor-filter-chip <?= $filter === 'demande' ? 'active' : '' ?>">
                Commandes apres demande
                <span><?= (int)($counts['total_demande'] ?? 0) ?></span>
            </a>
        </section>

        <?php if (!empty($commandes)): ?>
            <section class="vendor-list">
                <?php foreach ($commandes as $cmd): ?>
                    <?php
                        $commandeItems = $itemsByCommande[(int)$cmd['id']] ?? [];
                        $isPanierOrder = ($cmd['source'] ?? 'demande') === 'panier';
                        $mainItem = $commandeItems[0] ?? null;
                        $imageSrc = !empty($cmd['id_photo'])
                            ? htmlspecialchars($cmd['id_photo'])
                            : htmlspecialchars($mainItem['image_path'] ?? '/files_profil/logo.png');
                        $title = $isPanierOrder ? 'Commande panier' : ($cmd['nom_produit'] ?? 'Commande');
                    ?>
                    <article class="vendor-record-card">
                        <div class="vendor-record-grid">
                            <div class="vendor-record-image">
                                <img src="<?= $imageSrc ?>" alt="<?= htmlspecialchars($title) ?>">
                            </div>

                            <div class="vendor-record-content">
                                <div class="vendor-record-head">
                                    <div>
                                        <h3><?= htmlspecialchars($title) ?></h3>
                                        <div class="vendor-meta-row">
                                            <span class="vendor-chip"><i class="fa-regular fa-user"></i> <?= htmlspecialchars($cmd['client']) ?></span>
                                            <span class="vendor-chip"><i class="fa-solid fa-layer-group"></i> <?= $isPanierOrder ? 'Commande panier' : 'Depuis une demande' ?></span>
                                            <span class="vendor-chip strong"><i class="fa-regular fa-calendar"></i> <?= htmlspecialchars($cmd['created_at']) ?></span>
                                        </div>
                                    </div>
                                    <span class="vendor-status-chip <?= str_replace(' ', '-', strtolower(trim((string)($cmd['statut'] ?? 'en cours')))) ?>">
                                        <?= htmlspecialchars(ucfirst((string)($cmd['statut'] ?? 'en cours'))) ?>
                                    </span>
                                </div>

                                <?php if (!$isPanierOrder && !empty($cmd['description'])): ?>
                                    <p class="vendor-message-preview"><?= nl2br(htmlspecialchars($cmd['description'])) ?></p>
                                <?php endif; ?>

                                <?php if ($isPanierOrder): ?>
                                    <div class="vendor-items-box">
                                        <div class="vendor-items-head">
                                            <strong>Articles de la commande</strong>
                                            <span><?= number_format((float)($cmd['total'] ?? 0), 2) ?> DT</span>
                                        </div>
                                        <?php foreach ($commandeItems as $item): ?>
                                            <div class="vendor-item-line">
                                                <span><?= htmlspecialchars($item['nom_produit']) ?> x<?= (int)$item['quantite'] ?></span>
                                                <strong><?= number_format((float)$item['sous_total'], 2) ?> DT</strong>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="vendor-actions-row">
                                    <?php if (!$isPanierOrder && !empty($cmd['lien_produit'])): ?>
                                        <a href="<?= htmlspecialchars($cmd['lien_produit']) ?>" target="_blank" class="btn-link vendor-action-link">
                                            Voir produit
                                        </a>
                                    <?php endif; ?>

                                    <form method="POST" action="/php/update_commande.php" class="vendor-status-form">
                                        <input type="hidden" name="id" value="<?= (int)$cmd['id'] ?>">
                                        <select name="statut" onchange="this.form.submit()">
                                            <option value="en cours" <?= (($cmd['statut'] ?? '') === 'en cours') ? 'selected' : '' ?>>En cours</option>
                                            <option value="livre" <?= (($cmd['statut'] ?? '') === 'livre') ? 'selected' : '' ?>>Livre</option>
                                            <option value="termine" <?= (($cmd['statut'] ?? '') === 'termine') ? 'selected' : '' ?>>Termine</option>
                                            <option value="annule" <?= (($cmd['statut'] ?? '') === 'annule') ? 'selected' : '' ?>>Annule</option>
                                        </select>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php else: ?>
            <section class="vendor-empty-box">
                <h3>Aucune commande pour le moment</h3>
                <p>Les commandes acceptees ou creees depuis le panier client apparaitront ici.</p>
            </section>
        <?php endif; ?>
    </main>

</body>
</html>
