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
  <style>
    body { background:#f6f7fb; }
    .msg-wrap { max-width:980px; margin:24px auto; padding:0 16px; }
    .conv-item { background:#fff; border:1px solid #e8e8ef; border-radius:12px; padding:12px; margin-bottom:10px; }
    .chat-box { background:#fff; border:1px solid #e8e8ef; border-radius:14px; padding:12px; max-height:420px; overflow:auto; }
    .msg-line { margin-bottom:8px; padding:10px; border-radius:10px; }
    .msg-me { background:#e9fff1; }
    .msg-other { background:#f1f3ff; }
    .msg-form { margin-top:12px; display:flex; gap:8px; }
    .msg-form input[type=text] { flex:1; border:1px solid #ddd; border-radius:10px; padding:10px; }
    .msg-form button { border:none; border-radius:10px; padding:10px 14px; background:#6a5cff; color:#fff; cursor:pointer; }
    .review-box { margin-top:12px; background:#fff; border:1px solid #e8e8ef; border-radius:14px; padding:12px; }
    .review-box input { width:100%; border:1px solid #ddd; border-radius:10px; padding:10px; margin-bottom:8px; }
    .vendor-link { font-weight:700; color:#5b46e5; text-decoration:none; }
  </style>
</head>
<body>
  <header class="top-header"><a class="logo" href="<?php echo ($role === 'vendeur') ? '../php/page_vendeur.php' : '../html/client-interface.php'; ?>"><img class="logo-img" src="../files_profil/logo.png" alt="Importy"></a></header>
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
