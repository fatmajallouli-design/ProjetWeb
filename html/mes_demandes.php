<?php
session_start();
header("Cache-Control: public, max-age=3600*24*14");

if (!isset($_SESSION['user'])) {
    die("Utilisateur non connectÃ©");
}

$username = $_SESSION['user']['username'];

require_once(__DIR__ . '/../php/connexionBD.php');
$bdd = ConnexionBD::getInstance();

$req = $bdd->prepare("SELECT * FROM demande WHERE username = :username ORDER BY COALESCE(created_at, NOW()) DESC, id_demande DESC");
$req->execute(["username" => $username]);

$demandes = $req->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mes demandes</title>
    <link rel="stylesheet" href="../css/mes_demandes.css">
</head>

<body>

<header class="top-header">

    <div class="header-left">
        <a href="../html/client-interface.php" class="logo">
            <img src="/files_profil/logo.png" alt="Importy" class="logo-img">
        </a>
    </div>

    <div class="header-center">
        <h1 class="title">Mes demandes</h1>
    </div>

    <div class="header-right">
        <a href="client-interface.php" class="header-btn retour-btn"> Retour l'interface client</a>
        <a class="header-btn retour-btn" href="../html/demande.php">+ Ajouter une demande</a>
        <a href="../html/mon compte.php" class="header-btn small-btn">Mon compte</a>
        <a href="../html/messages.php" class="header-btn small-btn">Messages</a>
        <a href="../html/notifications.php" class="header-btn small-btn">Notifications</a>
    </div>

</header>

<div class="top-actions">
    <span class="count-pill"><?= count($demandes) ?> demande(s)</span>
</div>

<?php if (!empty($_SESSION['demande_error'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['demande_error']) ?></div>
    <?php unset($_SESSION['demande_error']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['demande_success'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['demande_success']) ?></div>
    <?php unset($_SESSION['demande_success']); ?>
<?php endif; ?>

<?php
function resolveDemandeImagePath(?string $path): string {
    $raw = trim((string)$path);
    if ($raw === '') {
        return '/files_profil/logo.png';
    }
    $normalized = str_replace('\\', '/', $raw);
    $normalized = preg_replace('#^\.\./+#', '/', $normalized);

    $candidates = [];
    if (strpos($normalized, '/files_demande/') === 0 || strpos($normalized, '/files_produit/') === 0 || strpos($normalized, '/files_produits/') === 0) {
        $candidates[] = $normalized;
    } else {
        $base = ltrim($normalized, '/');
        $candidates[] = '/files_demande/' . $base;
        $candidates[] = '/files_produits/' . $base;
        $candidates[] = '/files_produit/' . $base;
    }

    $root = realpath(__DIR__ . '/..');
    foreach ($candidates as $candidate) {
        $abs = realpath($root . $candidate);
        if ($abs !== false && is_file($abs)) {
            return $candidate;
        }
    }

    $basename = pathinfo($normalized, PATHINFO_BASENAME);
    if ($basename !== '') {
        foreach (['/files_demande', '/files_produit', '/files_produits'] as $dir) {
            $absDir = $root . $dir;
            if (!is_dir($absDir)) {
                continue;
            }
            foreach (glob($absDir . '/*' . $basename . '*') as $match) {
                if (is_file($match)) {
                    return $dir . '/' . basename($match);
                }
            }
        }
    }

    return '/files_profil/logo.png';
}
?>

<div class="container">

    <?php foreach ($demandes as $demande): ?>
        <?php
        $etat = strtolower(trim($demande['etat']));

        $class = match($etat) {
            'recu' => 'valide',
            'annule' => 'annule',
            default => 'en_attente'
        };
        ?>

        <div class="card" onclick="goToDetail(<?= (int)$demande['id_demande'] ?>)">
            <img src="<?= htmlspecialchars(resolveDemandeImagePath($demande['id_photo'] ?? '')) ?>" alt="Photo produit">

            <h3><?= htmlspecialchars($demande['nom_produit']) ?></h3>
            <p><?= htmlspecialchars($demande['created_at'] ?? '') ?></p>

            <p class="etat <?= $class ?>">
                <?= htmlspecialchars($etat) ?>
            </p>
        </div>
    <?php endforeach; ?>

    <?php if (empty($demandes)): ?>
        <div class="empty-state">
            <p>Vous n'avez encore aucune demande.</p>
            <a class="add-btn" href="/demande.php">Publier ma premiÃ¨re demande</a>
        </div>
    <?php endif; ?>

</div>

<script src="../javascript/mes_demandes.js"></script>

</body>
</html>
