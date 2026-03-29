<?php
session_start();

if (!isset($_SESSION['user'])) {
    die("Utilisateur non connecté");
}

$username = $_SESSION['user']['username'];

require_once("../php/connexionBD.php");
$bdd = ConnexionBD::getInstance();

$req = $bdd->prepare("SELECT * FROM demande WHERE username = :username ORDER BY COALESCE(created_at, NOW()) DESC, id_demande DESC");
$req->execute(["username" => $username]);

$demandes = $req->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mes demandes</title>
    <link rel="stylesheet" href="../css/mes_demandes.css">
</head>

<body>

<header class="top-header">

    <div class="header-left">
        <a href="client-interface.php" class="logo">
            <img src="../files_profil/logo.png" alt="Importy" class="logo-img">
        </a>
    </div>

    <div class="header-center">
        <h1 class="title">Mes demandes</h1>
    </div>

    <div class="header-right">
        <a href="client-interface.php" class="header-btn retour-btn">← Retour à l’interface client</a>
        <a class="header-btn retour-btn" href="demande.html">+ Ajouter une demande</a>
        <a href="mon compte.php" class="header-btn small-btn">Mon compte</a>
        <a href="messages.php" class="header-btn small-btn">Messages</a>
        <a href="notifications.php" class="header-btn small-btn">Notifications</a>
    </div>

</header>

<div class="top-actions">
    <span class="count-pill"><?= count($demandes) ?> demande(s)</span>
</div>

<div class="container">

    <?php foreach ($demandes as $demande): ?>
        <?php
        $etat = $demande['etat'];
        $class = ($etat == "valide") ? "valide" : "en_attente";
        ?>

        <div class="card" onclick="goToDetail(<?= (int)$demande['id_demande'] ?>)">
            <img src="<?= htmlspecialchars($demande['id_photo']) ?>" alt="Photo produit">

            <h3><?= htmlspecialchars($demande['nom_produit']) ?></h3>
            <p><?= htmlspecialchars($demande['created_at'] ?? '') ?></p>

            <p class="etat <?= $class ?>">
                <?= htmlspecialchars($etat) ?>
            </p>
        </div>
    <?php endforeach; ?>

    <?php if (empty($demandes)): ?>
        <div class="empty-state">
            <p>Vous n'avez encore aucune demande.</p>
            <a class="add-btn" href="../html/demande.html">Publier ma première demande</a>
        </div>
    <?php endif; ?>

</div>

<script src="../javascript/mes_demandes.js"></script>

</body>
</html>