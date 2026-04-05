<?php
session_start();

$error = $_SESSION['error'] ?? '';
$old = $_SESSION['old'] ?? [];

unset($_SESSION['error'], $_SESSION['old']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Sign up - IMPORTY</title>
    <link rel="stylesheet" href="../css/style_login.css">
</head>
<body class="login-body">

<div class="login-container">
    <h2>Sign up</h2>

    <div id="errorAlert" class="alert-box" style="<?php echo !empty($error) ? 'display:block' : 'display:none'; ?>">
        <div class="alert-header">
            <span class="alert-title">Erreur</span>
            <span class="close-btn" onclick="closeError()">x</span>
        </div>
        <div class="alert-body" id="errorMessage">
            <?php echo htmlspecialchars($error); ?>
        </div>
    </div>

    <form class="login-form" action="/php/register_login.php" method="post" id="registerForm" enctype="multipart/form-data">
        <input
            type="text"
            name="username"
            placeholder="Nom d'utilisateur"
            value="<?php echo htmlspecialchars($old['username'] ?? ''); ?>"
            required
        >

        <input type="password" name="password" id="password" placeholder="Mot de passe" required>

        <input type="password" name="confirmPassword" id="confirmPassword" placeholder="Confirmer le mot de passe" required>

        <input
            type="tel"
            name="tel"
            placeholder="Numero de telephone"
            value="<?php echo htmlspecialchars($old['tel'] ?? ''); ?>"
        >

        <input
            type="email"
            name="email"
            placeholder="Email"
            value="<?php echo htmlspecialchars($old['email'] ?? ''); ?>"
        >

        <input
            type="text"
            name="adresse"
            placeholder="Adresse"
            value="<?php echo htmlspecialchars($old['adresse'] ?? ''); ?>"
        >

        <div class="role">
            <label>
                <input type="radio" name="role" value="client" <?php echo (!isset($old['role']) || $old['role'] === 'client') ? 'checked' : ''; ?>>
                <span>Client</span>
            </label>

            <label>
                <input type="radio" name="role" value="vendeur" <?php echo (($old['role'] ?? '') === 'vendeur') ? 'checked' : ''; ?>>
                <span>Vendeur</span>
            </label>
        </div>

        <label class="upload">
            Ajouter une photo
            <input type="file" name="image" accept="image/*" capture="environment" id="photoInput" hidden>
        </label>

        <img id="preview" class="preview-img" alt="">

        <button type="submit">Sign up</button>
            <a href="../html/login.php">Tu as deja un compte ?Se connecter</a>

    </form>
</div>

<script src="../javascript/login.js"></script>
<script>
function closeError() {
    document.getElementById('errorAlert').style.display = 'none';
}
</script>
</body>
</html>

