<?php
session_start();
if (empty($_SESSION['user']['username'])) {
    header('Location: ./login.php');
    exit();
}
require_once(__DIR__ . '/../php/connexionBD.php');
$bdd = ConnexionBD::getInstance();
ConnexionBD::ensureWorkflowTables();

$user = $_SESSION['user']['username'];
$role = $_SESSION['user']['role'] ?? 'client';
$deal = (int)($_GET['deal'] ?? 0);

$list = $bdd->prepare("SELECT d.id_deal, d.client_username, d.vendeur_username, d.created_at,
    COALESCE(MAX(m.created_at), d.created_at) AS last_activity,
    COALESCE(COUNT(m.id_message), 0) AS message_count
FROM deal_request d
LEFT JOIN message m ON m.id_deal = d.id_deal
WHERE d.client_username = :u OR d.vendeur_username = :u
GROUP BY d.id_deal
ORDER BY last_activity DESC");
$list->execute(['u' => $user]);
$deals = $list->fetchAll(PDO::FETCH_ASSOC);

$lastMessageStmt = $bdd->prepare("SELECT contenu FROM message WHERE id_deal = :id ORDER BY created_at DESC, id_message DESC LIMIT 1");

$messages = [];
$other = '';

if ($deal <= 0 && !empty($deals)) {
    $deal = (int)$deals[0]['id_deal'];
}

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
            <img src="/files_profil/logo.png" alt="Importy" class="logo-img">
        </a>
    </div>

    <div class="header-center">
        <h1 class="title">Mes Messages</h1>
    </div>

    <div class="header-right" >
      <a href="client-interface.php" class="btn-retour-pro">
      <span class="arrow">â†</span>Retour Ã  lâ€™interface
      </a>       
    </div>

</header>
  <main class="msg-wrap">
    <section class="conversations-list">
      <h2>Conversations</h2>
      <?php if (empty($deals)): ?>
        <p>Aucune conversation.</p>
      <?php else: ?>
        <?php foreach ($deals as $d): $o = ($d['client_username']===$user)?$d['vendeur_username']:$d['client_username']; $active = ((int)$d['id_deal'] === $deal) ? 'active' : ''; 
            $lastMessageStmt->execute(['id' => (int)$d['id_deal']]);
            $lastMessageRow = $lastMessageStmt->fetch(PDO::FETCH_ASSOC);
            $lastPreview = $lastMessageRow['contenu'] ?? '';
        ?>
          <div class="conv-item <?= $active ?>" data-deal="<?= (int)$d['id_deal'] ?>">
            <a href="/messages.php?deal=<?= (int)$d['id_deal'] ?>">Deal #<?= (int)$d['id_deal'] ?> â€” <?= htmlspecialchars($o) ?></a>
          <?php if ($role === 'client'): ?>
            - <a class="vendor-link" href="/vendor_profile.php?vendeur=<?= urlencode($d['vendeur_username']) ?>">Profil vendeur</a>
          <?php endif; ?>
          <div><small>DerniÃ¨re activitÃ©: <?= htmlspecialchars($d['last_activity']) ?> | Messages: <span class="meta-count"><?= (int)$d['message_count'] ?></span></small></div>
          <div class="last-preview"><?= $lastPreview !== '' ? 'Dernier message : ' . htmlspecialchars(mb_strimwidth($lastPreview, 0, 48, '...')) : 'Aucun message encore.' ?></div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
    </section>

    <section class="conversation-detail">
      <?php if ($deal <= 0): ?>
        <div class="no-deal">SÃ©lectionnez une conversation pour afficher le chat.</div>
      <?php else: ?>
        <h2>
          Chat avec <?= htmlspecialchars($other) ?>
        <?php if ($role === 'client'): ?>
          - <a class="vendor-link" href="/vendor_profile.php?vendeur=<?= urlencode($other) ?>">Voir profil vendeur</a>
        <?php endif; ?>
      </h2>
      <div class="chat-box">
        <?php foreach ($messages as $msg): ?>
          <div class="msg-line <?= $msg['sender_username']===$user ? 'msg-me' : 'msg-other' ?>">
            <strong>
              <?php if ($role === 'client' && $msg['sender_username'] !== $user): ?>
                <a class="vendor-link" href="/vendor_profile.php?vendeur=<?= urlencode($msg['sender_username']) ?>"><?= htmlspecialchars($msg['sender_username']) ?></a>
              <?php else: ?>
                <?= htmlspecialchars($msg['sender_username']) ?>
              <?php endif; ?>
            </strong>
            (<?= htmlspecialchars($msg['created_at']) ?>): <?= nl2br(htmlspecialchars($msg['contenu'])) ?>
          </div>
        <?php endforeach; ?>
        <?php if (empty($messages)): ?><p>Aucun message.</p><?php endif; ?>
      </div>
      <form id="msgForm" action="/php/send_message.php" method="post" class="msg-form">
        <input type="hidden" name="id_deal" value="<?= $deal ?>">
        <input id="msgInput" type="text" name="contenu" required placeholder="Votre message...">
        <button type="submit">Envoyer</button>
      </form>
      <?php if ($role === 'client'): ?>
      <form action="/php/leave_review.php" method="post" class="review-box">
        <h3>Laisser un avis au fournisseur</h3>
        <input type="hidden" name="id_deal" value="<?= $deal ?>">
        <input type="number" name="rating" min="1" max="5" required placeholder="Note (1-5)">
        <input type="text" name="commentaire" placeholder="Commentaire">
        <button type="submit">Publier avis</button>
      </form>
      <?php endif; ?>
    <?php endif; ?>
    </section>
  </main>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const msgForm = document.getElementById('msgForm');
      const msgInput = document.getElementById('msgInput');
      const chatBox = document.querySelector('.chat-box');
      const convItems = document.querySelectorAll('.conv-item');

      function scrollChatToBottom() {
        if (chatBox) {
          chatBox.scrollTop = chatBox.scrollHeight;
        }
      }

      if (chatBox) {
        scrollChatToBottom();
      }

      if (msgForm) {
        msgForm.addEventListener('submit', function(e) {
          e.preventDefault();
          const formData = new FormData(msgForm);

          fetch(msgForm.action, {
            method: 'POST',
            body: formData,
            headers: {
              'X-Requested-With': 'XMLHttpRequest'
            }
          }).then(r => r.json()).then(data => {
            if (!data.success) {
              alert('Erreur lors de lâ€™envoi du message.');
              return;
            }
            const msg = data.message;
            const msgLine = document.createElement('div');
            msgLine.className = 'msg-line msg-me';
            msgLine.innerHTML = '<strong>' + msg.sender_username + '</strong> (' + msg.created_at + '): ' + msg.contenu.replace(/\n/g, '<br>');
            chatBox.appendChild(msgLine);
            msgInput.value = '';
            scrollChatToBottom();

            // Mise Ã  jour du rÃ©sumÃ© de conversation et du compteur
            const activeItem = document.querySelector('.conv-item.active');
            if (activeItem) {
              const countElem = activeItem.querySelector('.meta-count');
              if (countElem) {
                let count = parseInt(countElem.textContent || '0', 10) + 1;
                countElem.textContent = count;
              }
              const preview = activeItem.querySelector('.last-preview');
              if (preview) {
                preview.textContent = 'Dernier message : ' + (msg.contenu.length > 48 ? msg.contenu.substr(0, 48) + '...' : msg.contenu);
              }
            }
          }).catch(() => {
            alert('Erreur de connexion au serveur.');
          });
        });
      }

      convItems.forEach(function(item) {
        item.addEventListener('click', function(e) {
          // Leave native link behavior for full page load
        });
      });
    });
  </script>
</body>
</html>

