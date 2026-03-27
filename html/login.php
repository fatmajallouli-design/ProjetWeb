<?php
session_start();

$error = $_SESSION['login_error'] ?? '';
$old = $_SESSION['login_old'] ?? [];

unset($_SESSION['login_error'], $_SESSION['login_old']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Login - IMPORTY</title>
    <link rel="stylesheet" href="../css/style_inscription.css">
</head>
<body class="inscription-body">
    <div class="inscription-container">
        <h2>Login</h2>

        <?php if (!empty($error)): ?>
            <div style="margin-bottom:16px;padding:12px 14px;border-radius:12px;background:#fff1f1;color:#b42318;border:1px solid #f1b5b5;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form id="loginForm" action="../php/login_user.php" method="post">
            <input
                type="text"
                name="username"
                placeholder="Nom d'utilisateur"
                value="<?php echo htmlspecialchars($old['username'] ?? ''); ?>"
                required
            >

            <input type="password" name="password" placeholder="Mot de passe" required>

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

            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>
