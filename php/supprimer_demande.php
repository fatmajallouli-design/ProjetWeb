<?php
session_start();

if (!isset($_SESSION['user'])) {
    die("Utilisateur non connecté");
}

require_once(__DIR__ . "/connexionBD.php");
$bdd = ConnexionBD::getInstance();

$id = $_GET['id'] ?? null;
$username = $_SESSION['user']['username'];

if (!$id) {
    die("ID manquant");
}

$req = $bdd->prepare("DELETE FROM demande WHERE id_demande = :id AND username = :username");
$req->execute([
    "id" => $id,
    "username" => $username
]);

header("Location: /mes_demandes.php");
exit();
?>
