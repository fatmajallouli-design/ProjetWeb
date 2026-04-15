<?php
session_start();
require_once(__DIR__ . "/connexionBD.php");

$bdd = ConnexionBD::getInstance();

$id_deal = $_POST['id_deal'] ?? null;

if (!$id_deal) {
    die("ID manquant");
}

$req = $bdd->prepare("SELECT * FROM deal_request WHERE id_deal = :id");
$req->execute(["id" => $id_deal]);
$deal = $req->fetch();

if (!$deal) {
    die("Deal introuvable");
}

$insert = $bdd->prepare("
    INSERT INTO commandes (id_demande, vendeur, client, statut)
    VALUES (:id_demande, :vendeur, :client, 'en cours')
");

$insert->execute([
    "id_demande" => $deal['id_demande'],
    "vendeur" => $deal['vendeur_username'],
    "client" => $deal['client_username']
]);

$update = $bdd->prepare("UPDATE deal_request SET status = 'accepte' WHERE id_deal = :id");
$update->execute(["id" => $id_deal]);

header("Location: ../html/mes_demandes.php");
exit;
