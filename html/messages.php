<?php
session_start();
if (empty($_SESSION['user']['username'])) {
  header('Location: /login.php');
  exit();
}
require_once(__DIR__ . '/../php/connexionBD.php');
$bdd = ConnexionBD::getInstance();
ConnexionBD::ensureWorkflowTables();

$user = $_SESSION['user']['username'];
$role = $_SESSION['user']['role'] ?? 'client';
$deal   = (int)($_GET['deal'] ?? 0);
$thread = trim($_GET['thread'] ?? '');

$dealStmt = $bdd->prepare("SELECT d.id_deal, d.client_username, d.vendeur_username, d.created_at,
    COALESCE(MAX(m.created_at), d.created_at) AS last_activity,
    COALESCE(COUNT(m.id_message), 0) AS message_count
FROM deal_request d
LEFT JOIN message m ON m.id_deal = d.id_deal AND (m.thread_key IS NULL OR m.thread_key = '')
WHERE d.client_username = :u OR d.vendeur_username = :u
GROUP BY d.id_deal
ORDER BY last_activity DESC");
$dealStmt->execute(['u' => $user]);
$deals = $dealStmt->fetchAll(PDO::FETCH_ASSOC);

$threadStmt = $bdd->prepare("SELECT thread_key,
    MAX(created_at) AS last_activity,
    COUNT(*) AS message_count
FROM message
WHERE thread_key IS NOT NULL AND thread_key <> ''
  AND (sender_username = :u OR receiver_username = :u)
GROUP BY thread_key
ORDER BY last_activity DESC");
$threadStmt->execute(['u' => $user]);
$threads = $threadStmt->fetchAll(PDO::FETCH_ASSOC);

$lastMessageStmt = $bdd->prepare('SELECT contenu FROM message WHERE id_deal = :id AND (thread_key IS NULL OR thread_key = "") ORDER BY created_at DESC, id_message DESC LIMIT 1');
$lastThreadMsgStmt = $bdd->prepare('SELECT contenu FROM message WHERE thread_key = :tk ORDER BY created_at DESC, id_message DESC LIMIT 1');

$conversations = [];
foreach ($deals as $d) {
  $other = ($d['client_username'] === $user) ? $d['vendeur_username'] : $d['client_username'];
  $lastMessageStmt->execute(['id' => (int)$d['id_deal']]);
  $row = $lastMessageStmt->fetch(PDO::FETCH_ASSOC);
  $conversations[] = [
    'kind' => 'deal',
    'key' => 'deal-' . (int)$d['id_deal'],
    'id_deal' => (int)$d['id_deal'],
    'thread_key' => null,
    'other' => $other,
    'last_activity' => $d['last_activity'],
    'message_count' => (int)$d['message_count'],
    'last_preview' => $row['contenu'] ?? '',
  ];
}
foreach ($threads as $t) {
  if (!preg_match('/^direct:([^|]+)\|(.+)$/', $t['thread_key'], $m)) continue;
  $other = ($m[1] === $user) ? $m[2] : $m[1];
  $lastThreadMsgStmt->execute(['tk' => $t['thread_key']]);
  $row = $lastThreadMsgStmt->fetch(PDO::FETCH_ASSOC);
  $conversations[] = [
    'kind' => 'thread',
    'key' => 'thread-' . $t['thread_key'],
    'id_deal' => 0,
    'thread_key' => $t['thread_key'],
    'other' => $other,
    'last_activity' => $t['last_activity'],
    'message_count' => (int)$t['message_count'],
    'last_preview' => $row['contenu'] ?? '',
  ];
}
usort($conversations, fn($a, $b) => strcmp($b['last_activity'], $a['last_activity']));

$messages = [];
$other = '';
$activeKey = '';

if ($deal <= 0 && $thread === '' && !empty($conversations)) {
  $first = $conversations[0];
  if ($first['kind'] === 'deal') $deal = $first['id_deal'];
  else $thread = $first['thread_key'];
}

if ($deal > 0) {
  $d = $bdd->prepare('SELECT * FROM deal_request WHERE id_deal = :id');
  $d->execute(['id' => $deal]);
  $row = $d->fetch(PDO::FETCH_ASSOC);
  if ($row && ($row['client_username'] === $user || $row['vendeur_username'] === $user)) {
    $other = ($row['client_username'] === $user) ? $row['vendeur_username'] : $row['client_username'];
    $m = $bdd->prepare('SELECT * FROM message WHERE id_deal = :id AND (thread_key IS NULL OR thread_key = "") ORDER BY created_at ASC, id_message ASC');
    $m->execute(['id' => $deal]);
    $messages = $m->fetchAll(PDO::FETCH_ASSOC);

    $bdd->prepare('UPDATE message SET is_read = 1 WHERE id_deal = :id AND receiver_username = :u AND is_read = 0 AND (thread_key IS NULL OR thread_key = "")')
      ->execute(['id' => $deal, 'u' => $user]);
    $activeKey = 'deal-' . $deal;
  } else {
    $deal = 0;
  }
} elseif ($thread !== '') {
  if (preg_match('/^direct:([^|]+)\|(.+)$/', $thread, $tm)) {
    if ($tm[1] === $user || $tm[2] === $user) {
      $other = ($tm[1] === $user) ? $tm[2] : $tm[1];
      $m = $bdd->prepare('SELECT * FROM message WHERE thread_key = :tk ORDER BY created_at ASC, id_message ASC');
      $m->execute(['tk' => $thread]);
      $messages = $m->fetchAll(PDO::FETCH_ASSOC);

      $bdd->prepare('UPDATE message SET is_read = 1 WHERE thread_key = :tk AND receiver_username = :u AND is_read = 0')
        ->execute(['tk' => $thread, 'u' => $user]);
      $activeKey = 'thread-' . $thread;
    } else {
      $thread = '';
    }
  } else {
    $thread = '';
  }
}

$conversationOpen = ($deal > 0 || $thread !== '');
$lastMessageId = !empty($messages) ? (int)end($messages)['id_message'] : 0;
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Messages</title>
  <link rel="stylesheet" href="/css/style.css">
  <link rel="stylesheet" href="/css/messages.css">
</head>

<body>
  <header class="top-header">
    <div class="header-left">
      <a href="<?= ($role === 'vendeur') ? '/php/page_vendeur.php' : '/html/client-interface.php' ?>" class="logo">
        <img src="/files_profil/logo.png" alt="Importy" class="logo-img">
      </a>
    </div>
    <div class="header-center">
      <h1 class="title">Mes Messages</h1>
    </div>
    <div class="header-right">
      <a href="<?= ($role === 'vendeur') ? '/php/page_vendeur.php' : '/html/client-interface.php' ?>" class="btn-retour-pro">
        <span class="arrow"></span>Retour a l'interface
      </a>
    </div>
  </header>

  <main class="msg-wrap">
    <section class="conversations-list">
      <h2>Conversations</h2>
      <?php if (empty($conversations)): ?>
        <p>Aucune conversation.</p>
      <?php else: ?>
        <?php foreach ($conversations as $c): $active = ($c['key'] === $activeKey) ? 'active' : ''; ?>
          <div class="conv-item <?= $active ?>" data-conv-key="<?= htmlspecialchars($c['key']) ?>">
            <?php if ($c['kind'] === 'deal'): ?>
              <a href="/html/messages.php?deal=<?= $c['id_deal'] ?>">Deal avec <?= htmlspecialchars($c['other']) ?></a>
            <?php else: ?>
              <a href="/html/messages.php?thread=<?= urlencode($c['thread_key']) ?>">Chat avec <?= htmlspecialchars($c['other']) ?></a>
            <?php endif; ?>
            <?php if ($role === 'client'): ?>
              - <a class="vendor-link" href="/html/vendor_profile_client.php?vendeur=<?= urlencode($c['other']) ?>">Profil vendeur</a>
            <?php endif; ?>
            <div><small>Derniere activite: <?= htmlspecialchars($c['last_activity']) ?> | Messages: <span class="meta-count"><?= $c['message_count'] ?></span></small></div>
            <div class="last-preview"><?= $c['last_preview'] !== '' ? 'Dernier message : ' . htmlspecialchars(mb_strimwidth($c['last_preview'], 0, 48, '...')) : 'Aucun message encore.' ?></div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </section>

    <section class="conversation-detail">
      <?php if (!$conversationOpen): ?>
        <div class="no-deal">Selectionnez une conversation pour afficher le chat.</div>
      <?php else: ?>
        <h2>
          Chat avec <?= htmlspecialchars($other) ?>
          <?php if ($role === 'client'): ?>
            - <a class="vendor-link" href="/html/vendor_profile_client.php?vendeur=<?= urlencode($other) ?>">Voir profil vendeur</a>
          <?php endif; ?>
        </h2>
        <div class="chat-box"
             data-deal="<?= (int)$deal ?>"
             data-thread="<?= htmlspecialchars($thread) ?>"
             data-last-id="<?= $lastMessageId ?>"
             data-me="<?= htmlspecialchars($user) ?>">
          <?php foreach ($messages as $msg): ?>
            <div class="msg-line <?= $msg['sender_username'] === $user ? 'msg-me' : 'msg-other' ?>" data-id="<?= (int)$msg['id_message'] ?>">
              <strong><?= htmlspecialchars($msg['sender_username']) ?></strong>
              (<?= htmlspecialchars($msg['created_at']) ?>): <?= nl2br(htmlspecialchars($msg['contenu'])) ?>
            </div>
          <?php endforeach; ?>
          <?php if (empty($messages)): ?><p class="empty-chat">Aucun message. Lance la conversation !</p><?php endif; ?>
        </div>
        <form id="msgForm" action="/php/send_message.php" method="post" class="msg-form">
          <?php if ($deal > 0): ?>
            <input type="hidden" name="id_deal" value="<?= $deal ?>">
          <?php else: ?>
            <input type="hidden" name="thread" value="<?= htmlspecialchars($thread) ?>">
          <?php endif; ?>
          <input id="msgInput" type="text" name="contenu" required placeholder="Votre message..." autocomplete="off">
          <button type="submit">Envoyer</button>
        </form>
        <?php if ($role === 'client' && $deal > 0): ?>
          <form action="/php/leave_review.php" method="post" class="review-box">
            <h3>Laisser un avis au fournisseur</h3>
            <input type="hidden" name="id_deal" value="<?= $deal ?>">
            <div class="stars">
              <input type="radio" name="rating" id="star5" value="5"><label for="star5">&#9733;</label>
              <input type="radio" name="rating" id="star4" value="4"><label for="star4">&#9733;</label>
              <input type="radio" name="rating" id="star3" value="3"><label for="star3">&#9733;</label>
              <input type="radio" name="rating" id="star2" value="2"><label for="star2">&#9733;</label>
              <input type="radio" name="rating" id="star1" value="1"><label for="star1">&#9733;</label>
            </div>
            <input type="text" name="commentaire" placeholder="Commentaire">
            <button type="submit">Publier avis</button>
          </form>
        <?php endif; ?>
      <?php endif; ?>
    </section>
  </main>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const msgForm = document.getElementById('msgForm');
      const msgInput = document.getElementById('msgInput');
      const chatBox = document.querySelector('.chat-box');

      function scrollChatToBottom() {
        if (chatBox) chatBox.scrollTop = chatBox.scrollHeight;
      }

      function appendMessage(msg, me) {
        const empty = chatBox.querySelector('.empty-chat');
        if (empty) empty.remove();
        const line = document.createElement('div');
        const mine = msg.sender_username === me;
        line.className = 'msg-line ' + (mine ? 'msg-me' : 'msg-other');
        line.dataset.id = msg.id_message;
        const strong = document.createElement('strong');
        strong.textContent = msg.sender_username;
        line.appendChild(strong);
        line.appendChild(document.createTextNode(' (' + msg.created_at + '): '));
        const span = document.createElement('span');
        span.textContent = msg.contenu;
        line.appendChild(span);
        chatBox.appendChild(line);
      }

      if (chatBox) scrollChatToBottom();

      if (msgForm) {
        msgForm.addEventListener('submit', async function (e) {
          e.preventDefault();
          try {
            const formData = new FormData(msgForm);
            const response = await fetch(msgForm.action, {
              method: 'POST',
              body: formData,
              headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            });
            const data = await response.json();
            if (!data.success) {
              alert(data.message || 'Erreur lors de l\'envoi du message.');
              return;
            }
            const msg = data.message_data;
            const me = chatBox.dataset.me;
            appendMessage(msg, me);
            chatBox.dataset.lastId = msg.id_message;
            msgInput.value = '';
            scrollChatToBottom();
          } catch (err) {
            console.error('send error', err);
            alert('Erreur de connexion au serveur.');
          }
        });
      }

      if (chatBox) {
        const me = chatBox.dataset.me;
        async function poll() {
          try {
            const params = new URLSearchParams();
            if (chatBox.dataset.deal && parseInt(chatBox.dataset.deal, 10) > 0) {
              params.set('deal', chatBox.dataset.deal);
            }
            if (chatBox.dataset.thread) {
              params.set('thread', chatBox.dataset.thread);
            }
            params.set('after', chatBox.dataset.lastId || '0');
            const r = await fetch('/php/fetch_messages.php?' + params.toString(), {
              headers: { 'Accept': 'application/json' }
            });
            if (!r.ok) return;
            const data = await r.json();
            if (data && data.success && Array.isArray(data.messages)) {
              data.messages.forEach(m => {
                appendMessage(m, me);
                chatBox.dataset.lastId = m.id_message;
              });
              if (data.messages.length > 0) scrollChatToBottom();
            }
          } catch (err) { /* silent */ }
        }
        setInterval(poll, 4000);
      }
    });
  </script>
</body>
</html>
