<?php
session_start();
if (empty($_SESSION['user']['username'])) {
    header('Location: /login.php');
    exit();
}
require_once(__DIR__ . '/connexionBD.php');
$bdd = ConnexionBD::getInstance();
ConnexionBD::ensureWorkflowTables();

$me   = $_SESSION['user']['username'];
$role = $_SESSION['user']['role'] ?? 'client';

$peer = trim($_GET['peer'] ?? $_GET['vendeur'] ?? '');
if ($peer === '' || $peer === $me) {
    header('Location: /html/messages.php');
    exit();
}

if ($role === 'client') {
    $check = $bdd->prepare('SELECT 1 FROM vendeur WHERE username = :u');
} else {
    $check = $bdd->prepare('SELECT 1 FROM client WHERE username = :u');
}
$check->execute(['u' => $peer]);
if (!$check->fetchColumn()) {
    header('Location: /html/messages.php');
    exit();
}

$client  = ($role === 'client') ? $me   : $peer;
$vendeur = ($role === 'client') ? $peer : $me;
$thread  = 'direct:' . $client . '|' . $vendeur;

header('Location: /html/messages.php?thread=' . urlencode($thread));
exit();
