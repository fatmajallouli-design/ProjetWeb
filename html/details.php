<?php
session_start();

require_once("../php/connexionBD.php");
$bdd = ConnexionBD::getInstance();

$id = $_GET['id'] ?? null;

if (!$id) {
    die("ID manquant");
}


$req = $bdd->prepare("SELECT * FROM demande WHERE id_demande = :id");
$req->execute(["id" => $id]);

$demande = $req->fetch();

if (!$demande) {
    die("Demande introuvable");
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Détails</title>
<link rel="stylesheet" href="../css/details.css">
</head>

<body>

<div class="box">

    <img src="<?= $demande['id_photo'] ?>">

    <h2><?= $demande['nom_produit'] ?></h2>

    <p><strong>Prix :</strong> <?= $demande['prix'] ?> TND</p>

    <p><?= $demande['description'] ?></p>

</div>

</body>
</html>