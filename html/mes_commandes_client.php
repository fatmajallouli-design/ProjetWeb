<?php
session_start();

if (empty($_SESSION['user']['username'])) {
    header('Location: /login.php');
    exit();
}

require_once(__DIR__ . '/../php/connexionBD.php');
$bdd = ConnexionBD::getInstance();
ConnexionBD::ensureWorkflowTables();

$username = $_SESSION['user']['username'];

$ordersStmt = $bdd->prepare("
    SELECT id, vendeur, client, statut, total, created_at
    FROM commandes
    WHERE client = :client AND source = 'panier'
    ORDER BY created_at DESC, id DESC
");
$ordersStmt->execute(['client' => $username]);
$commandes = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);

$commandeIds = array_map(static fn($commande) => (int)$commande['id'], $commandes);
$itemsByCommande = [];

if (!empty($commandeIds)) {
    $placeholders = implode(',', array_fill(0, count($commandeIds), '?'));
    $itemsStmt = $bdd->prepare("
        SELECT id_commande, nom_produit, prix_unitaire, quantite, sous_total, image_path
        FROM commande_item
        WHERE id_commande IN ($placeholders)
        ORDER BY id ASC
    ");
    $itemsStmt->execute($commandeIds);

    foreach ($itemsStmt->fetchAll(PDO::FETCH_ASSOC) as $item) {
        $idCommande = (int)$item['id_commande'];
        if (!isset($itemsByCommande[$idCommande])) {
            $itemsByCommande[$idCommande] = [];
        }
        $itemsByCommande[$idCommande][] = $item;
    }
}

function orderStatusClass(string $statut): string
{
    $normalized = strtolower(trim($statut));
    return match ($normalized) {
        'livre' => 'livre',
        'termine' => 'termine',
        'annule' => 'annule',
        default => 'en-cours',
    };
}

function orderStatusLabel(string $statut): string
{
    $normalized = strtolower(trim($statut));
    return match ($normalized) {
        'livre' => 'Livree',
        'termine' => 'Terminee',
        'annule' => 'Annulee',
        default => 'En cours',
    };
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes commandes</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/mes_demandes.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<header class="top-header">
    <div class="header-left">
        <a href="client-interface.php" class="logo">
            <img src="/files_profil/logo.png" alt="Importy" class="logo-img">
        </a>
    </div>

    <div class="header-center">
        <h1 class="title">Mes commandes</h1>
    </div>

    <div class="header-right">
        <a href="../html/panier.php" class="header-btn retour-btn">Retour au panier</a>
        <a href="../html/client-interface.php" class="header-btn retour-btn">Interface client</a>
    </div>
</header>

<main class="orders-page">
    <section class="orders-hero">
        <div class="orders-hero-copy">
            <h2>Historique de vos commandes</h2>
            <p>Retrouvez ici toutes les commandes validées depuis votre panier, avec le vendeur, les articles achetés et le statut actuel.</p>
        </div>
        <div class="orders-hero-badge">
            <?= count($commandes) ?> commande(s)
        </div>
    </section>

    <?php if (!empty($commandes)): ?>
        <section class="orders-stack">
            <?php foreach ($commandes as $commande): ?>
                <?php $commandeItems = $itemsByCommande[(int)$commande['id']] ?? []; ?>
                <article class="client-order-card">
                    <div class="client-order-head">
                        <div>
                            <h3>Commande #<?= (int)$commande['id'] ?></h3>
                            <div class="client-order-meta">
                                <span class="client-order-chip">
                                    <i class="fa-solid fa-store"></i>
                                    Vendeur : <?= htmlspecialchars($commande['vendeur'] ?? '-') ?>
                                </span>
                                <span class="client-order-chip">
                                    <i class="fa-regular fa-calendar"></i>
                                    <?= htmlspecialchars(date('d/m/Y H:i', strtotime($commande['created_at'] ?? 'now'))) ?>
                                </span>
                                <span class="client-order-status <?= orderStatusClass((string)($commande['statut'] ?? '')) ?>">
                                    <i class="fa-solid fa-circle"></i>
                                    <?= htmlspecialchars(orderStatusLabel((string)($commande['statut'] ?? 'en cours'))) ?>
                                </span>
                            </div>
                        </div>

                        <div class="client-order-total">
                            <span class="label">Total commande</span>
                            <span class="value"><?= number_format((float)($commande['total'] ?? 0), 2) ?> DT</span>
                        </div>
                    </div>

                    <div class="client-order-items">
                        <?php foreach ($commandeItems as $item): ?>
                            <div class="client-order-item">
                                <div class="client-order-item-image">
                                    <img src="<?= htmlspecialchars($item['image_path'] ?: '/files_profil/logo.png') ?>" alt="<?= htmlspecialchars($item['nom_produit']) ?>">
                                </div>
                                <h4><?= htmlspecialchars($item['nom_produit']) ?></h4>
                                <p><strong>Prix unitaire :</strong> <?= number_format((float)$item['prix_unitaire'], 2) ?> DT</p>
                                <p><strong>Quantite :</strong> <?= (int)$item['quantite'] ?></p>
                                <p><strong>Sous-total :</strong> <?= number_format((float)$item['sous_total'], 2) ?> DT</p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
    <?php else: ?>
        <section class="orders-empty">
            <h3>Aucune commande pour le moment</h3>
            <p>Des que vous validez un panier, vos articles commandes apparaitront ici avec leur statut.</p>
            <a href="../html/client-interface.php" class="add-btn">Explorer les produits</a>
        </section>
    <?php endif; ?>
</main>

<div class="about-site">
    <h4><strong>A propos de nous</strong></h4>
    <p>
        Importy vous aide a retrouver facilement vos achats et a suivre vos commandes, tout en gardant une experience simple, claire et rassurante.
    </p>
</div>

<div class="services">
    <div class="service">
        <div class="icon"><i class="fa-solid fa-store"></i></div>
        <div>
            <h4>pour les vendeurs</h4>
            <p>Proposez vos produits et gerez votre activite en toute simplicite.</p>
        </div>
    </div>

    <div class="service">
        <div class="icon"><i class="fa-solid fa-truck"></i></div>
        <div>
            <h4>Livraison standard offerte</h4>
            <p>just verifier votre adresse dans le compte</p>
        </div>
    </div>

    <div class="service">
        <div class="icon"><i class="fa-regular fa-credit-card"></i></div>
        <div>
            <h4>Paiements a la livraison</h4>
            <p>vous payez le livreur lorsque vous recevez votre commande</p>
        </div>
    </div>

    <div class="service">
        <div class="icon"><i class="fa-solid fa-undo"></i></div>
        <div>
            <h4>Retours</h4>
            <p>sous 14 jours</p>
        </div>
    </div>
</div>
</body>
</html>
