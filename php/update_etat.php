<?php
require_once(__DIR__ . "/connexionBD.php");

$bdd = ConnexionBD::getInstance();

$id = $_POST['id'] ?? null;
$etat = $_POST['etat'] ?? null;

if ($id && $etat) {
    $req = $bdd->prepare("UPDATE demande SET etat = :etat WHERE id_demande = :id");
    $req->execute([
        "etat" => $etat,
        "id" => $id
    ]);
}


header("Location: /details.php?id=" . $id);
exit;
