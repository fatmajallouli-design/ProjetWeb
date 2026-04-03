<?php
session_start();
if (empty($_SESSION['user']['username'])) {
    header('Location: /login.php');
    exit();
}

require_once(__DIR__ . '/connexionBD.php');
$bdd = ConnexionBD::getInstance();
$username = $_SESSION['user']['username'];
$role = $_SESSION['user']['role'] ?? 'client';

if ($role === 'client') {
    $stmt = $bdd->prepare('SELECT * FROM client WHERE username = :username');
} else {
    $stmt = $bdd->prepare('SELECT * FROM vendeur WHERE username = :username');
}

$stmt->execute(['username' => $username]);
$userInfo = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IMPORTY : Mon Compte</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <main class="account-page">
        <section class="client-card profile-card account-card">
            <div class="profile-top">
                <div class="avatar-circle"><?php echo strtoupper(substr($username, 0, 1)); ?></div>
                <div>
                    <h2>Mon compte</h2>
                    <p>Bienvenue <?php echo htmlspecialchars($username); ?></p>
                </div>
            </div>

            <div class="profile-details account-details">
                <p><strong>Nom d'utilisateur :</strong> <?php echo htmlspecialchars($userInfo['username'] ?? $username); ?></p>
                <p><strong>Email :</strong> <?php echo htmlspecialchars($userInfo['email'] ?? 'Non renseigne'); ?></p>
                <p><strong>Adresse :</strong> <?php echo htmlspecialchars($userInfo['adresse'] ?? 'Non renseignee'); ?></p>
                <p><strong>Telephone :</strong> <?php echo htmlspecialchars($userInfo['num_tel'] ?? 'Non renseigne'); ?></p>
            </div>

            <div class="account-actions">
                <a href="/client-interface.php" class="secondary-btn">Retour a l'interface</a>
            </div>
        </section>
    </main>
</body>
</html>


