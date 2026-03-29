<?php
session_start();


if (empty($_SESSION['user']['username'])) {
    header('Location: ../html/login.php');
    exit();
}


$username = $_SESSION['user']['username'];
$role = $_SESSION['user']['role'] ?? 'client';

if ($role !== 'vendeur') {
    $_SESSION['account_error'] = 'Modification disponible uniquement pour le vendeur.';
    header('Location: ../html/mon compte.php');
    exit();
}


$email = trim($_POST['email'] ?? '');
$adresse = trim($_POST['adresse'] ?? '');
$numTel = trim($_POST['num_tel'] ?? '');


if ($email === '' || $adresse === '' || $numTel === '') {
    $_SESSION['account_error'] = 'Remplir tous les champs.';
    header('Location: ../html/mon compte.php');
    exit();
}


if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['account_error'] = 'Email invalide.';
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
        $_SESSION['account_error'] = 'Erreur upload.';
        header('Location: ../html/mon compte.php');
        exit();
    }


    $newFilePath = '../files_profil/' . uniqid() . '_' . basename($_FILES['image']['name']);

    
    if (!move_uploaded_file($_FILES['image']['tmp_name'], $newFilePath)) {
        $_SESSION['account_error'] = 'Erreur sauvegarde photo.';
        header('Location: ../html/mon compte.php');
        exit();
    }

    $photoSql = ', idphoto = :idphoto';
    $params['idphoto'] = $newFilePath;
}


$stmt = $bdd->prepare("
    UPDATE vendeur
    SET email = :email, adresse = :adresse, num_tel = :num_tel{$photoSql}
    WHERE username = :username
");

$stmt->execute($params);


$_SESSION['account_success'] = 'Donnees mises a jour.';
header('Location: ../html/mon compte.php');
exit();