<?php
session_start();

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? '';

$_SESSION['login_old'] = [
    'username' => $username,
    'role' => $role
];

if ($username === '' || $password === '' || ($role !== 'client' && $role !== 'vendeur')) {
    $_SESSION['login_error'] = 'Veuillez remplir tous les champs.';
    header('Location: ../html/login.php');
    exit();
}

require_once('connexionBD.php');
$bdd = ConnexionBD::getInstance();

if ($role === 'client') {
    $stmt = $bdd->prepare('SELECT username, password FROM client WHERE username = :username AND password = :password');
} else {
    $stmt = $bdd->prepare('SELECT username, password FROM vendeur WHERE username = :username AND password = :password');
}

$stmt->execute([
    'username' => $username,
    'password' => $password
]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    $_SESSION['login_error'] = 'Nom d\'utilisateur, mot de passe ou role incorrect.';
    header('Location: ../html/login.php');
    exit();
}

unset($_SESSION['login_old']);
$_SESSION['user'] = [
    'username' => $user['username'],
    'role' => $role
];

if ($role === 'client') {
    header('Location: ../html/client-interface.php');
    exit();
}

header('Location: ../php/page_vendeur.php');
exit();
