<?php
session_start();
if (empty($_SESSION['user']['username'])) {
    header('Location: ../html/login.php');
    exit();
}

require_once('../php/connexionBD.php');
$bdd = ConnexionBD::getInstance();
$username = $_SESSION['user']['username'];
$role = $_SESSION['user']['role'] ?? 'client';
$success = $_SESSION['account_success'] ?? '';
$error = $_SESSION['account_error'] ?? '';

unset($_SESSION['account_success'], $_SESSION['account_error']);

if ($role === 'client') {
    $stmt = $bdd->prepare('SELECT * FROM client WHERE username = :username');
} else {
    $stmt = $bdd->prepare('SELECT * FROM vendeur WHERE username = :username');
}

$stmt->execute(['username' => $username]);
$userInfo = $stmt->fetch(PDO::FETCH_ASSOC);
$photoPath = trim($userInfo['idphoto'] ?? '');
$photoUrl = '';
$hasPhoto = false;

if ($photoPath !== '') {
    $normalizedPhotoPath = str_replace('\\', '/', $photoPath);

    if (strpos($normalizedPhotoPath, '../') === 0) {
        $resolvedPhotoPath = realpath(__DIR__ . '/' . $normalizedPhotoPath);
    } else {
        $resolvedPhotoPath = realpath(__DIR__ . '/../' . ltrim($normalizedPhotoPath, '/'));
        if ($resolvedPhotoPath === false) {
            $resolvedPhotoPath = realpath(__DIR__ . '/' . ltrim($normalizedPhotoPath, '/'));
        }
    }

    if ($resolvedPhotoPath !== false && is_file($resolvedPhotoPath)) {
        $hasPhoto = true;
        $photoUrl = $normalizedPhotoPath;
    }
}
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
                <?php if ($hasPhoto): ?>
                    <img
                        class="account-avatar-image"
                        src="<?php echo htmlspecialchars($photoUrl); ?>"
                        alt="Photo de profil de <?php echo htmlspecialchars($username); ?>"
                    >
                <?php else: ?>
                    <div class="avatar-circle"><?php echo strtoupper(substr($username, 0, 1)); ?></div>
                <?php endif; ?>
                <div>
                    <h2>Mon compte</h2>
                    <p>Bienvenue <?php echo htmlspecialchars($username); ?></p>
                </div>
            </div>

            <?php if (!empty($success)): ?>
                <div class="account-message success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="account-message error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="profile-details account-details">
                <p><strong>Nom d'utilisateur :</strong> <?php echo htmlspecialchars($userInfo['username'] ?? $username); ?></p>
                <p><strong>Email :</strong> <?php echo htmlspecialchars($userInfo['email'] ?? 'Non renseigne'); ?></p>
                <p><strong>Adresse :</strong> <?php echo htmlspecialchars($userInfo['adresse'] ?? 'Non renseignee'); ?></p>
                <p><strong>Telephone :</strong> <?php echo htmlspecialchars($userInfo['num_tel'] ?? 'Non renseigne'); ?></p>
            </div>

<form class="account-form" 
      action="<?php echo ($role === 'vendeur') ? '../php/update_vendeur.php' : '../php/update_client_account.php'; ?>" 
      method="post" enctype="multipart/form-data">                <h3>Modifier mes donnees</h3>
                <input
                    type="email"
                    name="email"
                    placeholder="Email"
                    value="<?php echo htmlspecialchars($userInfo['email'] ?? ''); ?>"
                    required
                >
                <input
                    type="text"
                    name="adresse"
                    placeholder="Adresse"
                    value="<?php echo htmlspecialchars($userInfo['adresse'] ?? ''); ?>"
                    required
                >
                <input
                    type="tel"
                    name="num_tel"
                    placeholder="Telephone"
                    value="<?php echo htmlspecialchars($userInfo['num_tel'] ?? ''); ?>"
                    required
                >
                <label class="account-upload">
                    <span>Modifier la photo</span>
                    <input type="file" name="image" accept="image/*">
                </label>
                <button type="submit" class="primary-btn">Enregistrer les modifications</button>
            </form>

            <div class="account-actions">
                <a href="<?php echo ($role === 'vendeur') ? '../php/page_vendeur.php' : '../html/client-interface.php'; ?>" 
   class="secondary-btn">
   Retour
</a>
                <a href="../php/logout.php" class="small-btn" id="logoutAccountLink">Se deconnecter</a>
            </div>
        </section>
    </main>
    <script>
        const logoutAccountLink = document.getElementById('logoutAccountLink');

        if (logoutAccountLink) {
            logoutAccountLink.addEventListener('click', function (event) {
                const confirmed = window.confirm('Est tu sure que tu veux te deconnecter ?');

                if (!confirmed) {
                    event.preventDefault();
                }
            });
        }
    </script>
</body>
</html>
