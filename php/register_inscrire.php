<?php
session_start();

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? '';

if (empty($username) || empty($password) || empty($role)) {
    header("Location: ../html/inscrire.html?error=missing_fields");
    exit();
}

require_once("connexionBD.php");
$bdd = ConnexionBD::getInstance();

// Vérifier si le compte existe déjà
$req = $bdd->prepare("SELECT COUNT(*) FROM client WHERE username = :username");
$req->execute(['username' => $username]);
$existsClient = $req->fetchColumn();

$req = $bdd->prepare("SELECT COUNT(*) FROM vendeur WHERE username = :username");
$req->execute(['username' => $username]);
$existsVendeur = $req->fetchColumn();

if ($existsClient || $existsVendeur) {
    header("Location: ../html/inscrire.html?error=user_exists");
    exit();
}

// Crée le compte avec des valeurs par défaut pour les champs obligatoires
$email = '';
$adresse = '';
$num_tel = '';
$idphoto = null;

if ($role === 'client') {
    $req = $bdd->prepare("INSERT INTO client (username, email, adresse, num_tel, idphoto, password) VALUES (:username, :email, :adresse, :num_tel, :idphoto, :password)");
    $req->execute([
        'username' => $username,
        'email' => $email,
        'adresse' => $adresse,
        'num_tel' => $num_tel,
        'idphoto' => $idphoto,
        'password' => $password
    ]);

    $_SESSION['user'] = ['username' => $username, 'role' => $role];
    header("Location: ../html/client-interface.php");
    exit();
} elseif ($role === 'vendeur') {
    $req = $bdd->prepare("INSERT INTO vendeur (username, email, adresse, num_tel, idphoto, password) VALUES (:username, :email, :adresse, :num_tel, :idphoto, :password)");
    $req->execute([
        'username' => $username,
        'email' => $email,
        'adresse' => $adresse,
        'num_tel' => $num_tel,
        'idphoto' => $idphoto,
        'password' => $password
    ]);

    $_SESSION['user'] = ['username' => $username, 'role' => $role];
    header("Location: ../php/page_vendeur.php");
    exit();
} else {
    header("Location: ../html/inscrire.html?error=invalid_role");
    exit();
}



?>