<?php
session_start();

if (empty($_SESSION['user']['username'])) {
    header('Location: ../html/login.php');
    exit();
}

$username = $_SESSION['user']['username'];
$role = $_SESSION['user']['role'] ?? 'client';

if ($role !== 'client') {
    $_SESSION['account_error'] = 'La modification de compte est disponible uniquement pour le client.';
    header('Location: ../html/mon compte.php');
    exit();
}

$email = trim($_POST['email'] ?? '');
$adresse = trim($_POST['adresse'] ?? '');
$numTel = trim($_POST['num_tel'] ?? '');

if ($email === '' || $adresse === '' || $numTel === '') {
    $_SESSION['account_error'] = 'Veuillez remplir email, adresse et telephone.';
    header('Location: ../html/mon compte.php');
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['account_error'] = 'Veuillez entrer un email valide.';
    header('Location: ../html/mon compte.php');
    exit();
}

require_once('connexionBD.php');
$bdd = ConnexionBD::getInstance();

$photoSql = '';
$params = [
    'email' => $email,
    'adresse' => $adresse,
    'num_tel' => $numTel,
    'username' => $username
];

if (isset($_FILES['image']) && $_FILES['image']['error'] !== 4) {
    if ($_FILES['image']['error'] !== 0) {
        $_SESSION['account_error'] = 'Erreur lors du telechargement de la photo.';
        header('Location: ../html/mon compte.php');
        exit();
    }

    $newFilePath = '../files_profil/' . uniqid() . '_' . basename($_FILES['image']['name']);

    if (!move_uploaded_file($_FILES['image']['tmp_name'], $newFilePath)) {
        $_SESSION['account_error'] = 'Impossible d enregistrer la nouvelle photo.';
        header('Location: ../html/mon compte.php');
        exit();
    }

    $photoSql = ', idphoto = :idphoto';
    $params['idphoto'] = $newFilePath;
}

$stmt = $bdd->prepare("
    UPDATE client
    SET email = :email, adresse = :adresse, num_tel = :num_tel{$photoSql}
    WHERE username = :username
");

$stmt->execute($params);

$_SESSION['account_success'] = 'Vos donnees ont ete mises a jour.';
header('Location: ../html/mon compte.php');
exit();
