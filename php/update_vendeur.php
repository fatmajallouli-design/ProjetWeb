<?php
session_start();

if (empty($_SESSION['user']['username'])) {
    header('Location: ../html/login.php');
    exit();
}

$username = $_SESSION['user']['username'];
$role = $_SESSION['user']['role'] ?? 'client';

if ($role !== 'vendeur') {
    $_SESSION['account_error'] = 'La modification de compte est disponible uniquement pour le vendeur.';
    header('Location: ../html/mon%20compte.php');
    exit();
}

$email = trim($_POST['email'] ?? '');
$adresse = trim($_POST['adresse'] ?? '');
$numTel = trim($_POST['num_tel'] ?? '');

if ($email === '' || $adresse === '' || $numTel === '') {
    $_SESSION['account_error'] = 'Veuillez remplir tous les champs.';
    header('Location: ../html/mon%20compte.php');
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['account_error'] = 'Veuillez entrer un email valide.';
    header('Location: ../html/mon%20compte.php');
    exit();
}

require_once(__DIR__ . "/connexionBD.php");
$bdd = ConnexionBD::getInstance();

$photoSql = '';

$params = [
    'email' => $email,
    'adresse' => $adresse,
    'num_tel' => $numTel,
    'username' => $username
];

if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {

    if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['account_error'] = 'Erreur lors du téléchargement de la photo.';
        header('Location: ../html/mon%20compte.php');
        exit();
    }

    $targetDir = realpath(__DIR__ . '/../files_profil');

    if ($targetDir === false) {
        $targetDir = __DIR__ . '/../files_profil';

        if (!is_dir($targetDir)) {
            if (!mkdir($targetDir, 0777, true)) {
                $_SESSION['account_error'] = 'Impossible de créer le dossier de photos.';
                header('Location: ../html/mon%20compte.php');
                exit();
            }
        }
    }

    $fileName = uniqid() . '_' . basename($_FILES['image']['name']);
    $absolutePath = rtrim($targetDir, '/\\') . DIRECTORY_SEPARATOR . $fileName;
    $publicPath = '/files_profil/' . $fileName;

    if (!move_uploaded_file($_FILES['image']['tmp_name'], $absolutePath)) {
        $_SESSION['account_error'] = 'Impossible d’enregistrer la nouvelle photo.';
        header('Location: ../html/mon%20compte.php');
        exit();
    }

    $photoSql = ', idphoto = :idphoto';
    $params['idphoto'] = $publicPath;
}

$stmt = $bdd->prepare("
    UPDATE vendeur
    SET email = :email,
        adresse = :adresse,
        num_tel = :num_tel
        $photoSql
    WHERE username = :username
");

$stmt->execute($params);

$_SESSION['account_success'] = 'Vos données ont été mises à jour avec succès.';
header('Location: ../html/mon%20compte.php');
exit();
