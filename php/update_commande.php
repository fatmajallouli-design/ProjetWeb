<?php
require_once(__DIR__ . "/connexionBD.php");
$bdd = ConnexionBD::getInstance();

$id = $_POST['id'] ?? null;
$statut = $_POST['statut'] ?? null;

if ($id && $statut) {

    $req = $bdd->prepare("UPDATE commandes SET statut = :statut WHERE id = :id");
    $req->execute([
        "statut" => $statut,
        "id" => $id
    ]);

    $req2 = $bdd->prepare("SELECT id_demande FROM commandes WHERE id = :id");
    $req2->execute(["id" => $id]);
    $cmd = $req2->fetch(PDO::FETCH_ASSOC);

    if ($cmd && !empty($cmd['id_demande'])) {
        $etatDemande = "en attente";

        if ($statut === "livre" || $statut === "termine") {
            $etatDemande = "recu";
        } elseif ($statut === "annule") {
            $etatDemande = "annule";
        }

        $req3 = $bdd->prepare("UPDATE demande SET etat = :etat WHERE id_demande = :id");
        $req3->execute([
            "etat" => $etatDemande,
            "id" => $cmd['id_demande']
        ]);
    }
}

header("Location: /commande_vendeur.php");
exit;
