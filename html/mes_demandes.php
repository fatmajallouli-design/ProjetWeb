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

<h2 class="title">Mes demandes</h2>
<div class="top-actions">
  <span class="count-pill"><?= count($demandes) ?> demande(s)</span>
  <a class="add-btn" href="../html/demande.html">+ Ajouter une demande</a>
</div>

<div class="container">

<?php foreach($demandes as $demande): ?>

<?php
$etat = $demande['etat'];
$class = ($etat == "valide") ? "valide" : "en_attente";
?>

<div class="card" onclick="goToDetail(<?= $demande['id_demande'] ?>)">

    <img src="<?= $demande['id_photo'] ?: '../images/default.png' ?>">

    <h3><?= $demande['nom_produit'] ?></h3>
    <p><?= htmlspecialchars($demande['created_at'] ?? '') ?></p>

    <p class="etat <?= $class ?>">
        <?= $etat ?>
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