<?php
require_once(__DIR__ . "/connexionBD.php");
$bdd = ConnexionBD::getInstance();

$id = $_POST['id'] ?? null;

if ($id) {

    // récupérer id_demande pour les commandes liées aux demandes
    $req = $bdd->prepare("SELECT id_demande FROM commandes WHERE id = :id");
    $req->execute(["id" => $id]);
    $cmd = $req->fetch();

    if ($cmd) {

        // supprimer commande
        $bdd->prepare("DELETE FROM commandes WHERE id = :id")
            ->execute(["id" => $id]);

        if (!empty($cmd['id_demande'])) {
            // remettre demande en attente
            $bdd->prepare("UPDATE demande SET etat = 'en attente' WHERE id_demande = :id")
                ->execute(["id" => $cmd['id_demande']]);
        }
    }
}

header("Location: /commande_vendeur.php");
exit;
