<?php
session_start();
if (empty($_SESSION['user']['username'])) {
    header('Location: ./login.php');
    exit();
}
require_once('../php/connexionBD.php');
$bdd = ConnexionBD::getInstance();
ConnexionBD::ensureWorkflowTables();

$user = $_SESSION['user']['username'];
$role = $_SESSION['user']['role'] ?? 'client';
$deal = (int)($_GET['deal'] ?? 0);

$list = $bdd->prepare("SELECT id_deal, client_username, vendeur_username, created_at FROM deal_request WHERE client_username = :u OR vendeur_username = :u ORDER BY id_deal DESC");
$list->execute(['u' => $user]);
$deals = $list->fetchAll(PDO::FETCH_ASSOC);

$messages = [];
$other = '';
if ($deal > 0) {
    $d = $bdd->prepare("SELECT * FROM deal_request WHERE id_deal = :id");
    $d->execute(['id' => $deal]);
    $row = $d->fetch(PDO::FETCH_ASSOC);
    if ($row && ($row['client_username'] === $user || $row['vendeur_username'] === $user)) {
        $other = ($row['client_username'] === $user) ? $row['vendeur_username'] : $row['client_username'];
        $m = $bdd->prepare("SELECT * FROM message WHERE id_deal = :id ORDER BY created_at ASC, id_message ASC");
        $m->execute(['id' => $deal]);
        $messages = $m->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Messages</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/messages.css">
</head>
<body>
  <header class="top-header">

    <div class="header-left">
        <a href="client-interface.php" class="logo">
            <img src="../files_profil/logo.png" alt="Importy" class="logo-img">
        </a>
    </div>

    <div class="header-center">
        <h1 class="title">Mes Messages</h1>
    </div>

    <div class="header-right" >
      <a href="client-interface.php" class="btn-retour-pro">
      <span class="arrow">←</span>Retour à l’interface
      </a>       
    </div>

</header>
  <main class="msg-wrap">
    <?php if ($deal <= 0): ?>
      <h2>Conversations</h2>
      <?php foreach ($deals as $d): $o = ($d['client_username']===$user)?$d['vendeur_username']:$d['client_username']; ?>
        <div class="conv-item">
          <a href="./messages.php?deal=<?= (int)$d['id_deal'] ?>">Deal #<?= (int)$d['id_deal'] ?> avec <?= htmlspecialchars($o) ?></a>
          <?php if ($role === 'client'): ?>
            - <a class="vendor-link" href="./vendor_profile.php?vendeur=<?= urlencode($d['vendeur_username']) ?>">Profil vendeur</a>
          <?php endif; ?>
          <div><?= htmlspecialchars($d['created_at']) ?></div>
        </div>
      <?php endforeach; ?>
      <?php if (empty($deals)): ?><p>Aucune conversation.</p><?php endif; ?>
    <?php else: ?>
      <h2>
        Chat avec <?= htmlspecialchars($other) ?>
        <?php if ($role === 'client'): ?>
          - <a class="vendor-link" href="./vendor_profile.php?vendeur=<?= urlencode($other) ?>">Voir profil vendeur</a>
        <?php endif; ?>
      </h2>
      <div class="chat-box">
        <?php foreach ($messages as $msg): ?>
          <div class="msg-line <?= $msg['sender_username']===$user ? 'msg-me' : 'msg-other' ?>">
            <strong>
              <?php if ($role === 'client' && $msg['sender_username'] !== $user): ?>
                <a class="vendor-link" href="./vendor_profile.php?vendeur=<?= urlencode($msg['sender_username']) ?>"><?= htmlspecialchars($msg['sender_username']) ?></a>
              <?php else: ?>
                <?= htmlspecialchars($msg['sender_username']) ?>
              <?php endif; ?>
            </strong>
            (<?= htmlspecialchars($msg['created_at']) ?>): <?= nl2br(htmlspecialchars($msg['contenu'])) ?>
          </div>
        <?php endforeach; ?>
        <?php if (empty($messages)): ?><p>Aucun message.</p><?php endif; ?>
      </div>
      <form action="../php/send_message.php" method="post" class="msg-form">
        <input type="hidden" name="id_deal" value="<?= $deal ?>">
        <input type="text" name="contenu" required placeholder="Votre message...">
        <button type="submit">Envoyer</button>
      </form>
      <?php if ($role === 'client'): ?>
      <form action="../php/leave_review.php" method="post" class="review-box">
        <h3>Laisser un avis au fournisseur</h3>
        <input type="hidden" name="id_deal" value="<?= $deal ?>">
        <input type="number" name="rating" min="1" max="5" required placeholder="Note (1-5)">
        <input type="text" name="commentaire" placeholder="Commentaire">
        <button type="submit">Publier avis</button>
      </form>
      <?php endif; ?>
    <?php endif; ?>
  </main>
</body>
</html>
