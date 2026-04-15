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

    // Mark messages in this deal as read for current user
    $bdd->prepare("UPDATE message SET is_read = 1 WHERE id_deal = :id AND receiver_username = :u AND is_read = 0")
      ->execute(['id' => $deal, 'u' => $user]);
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
      <a href="<?= ($role === 'vendeur') ? '/php/page_vendeur.php' : '../html/client-interface.php' ?>" class="logo">
        <img src="/files_profil/logo.png" alt="Importy" class="logo-img">
      </a>
    </div>

    <div class="header-center">
      <h1 class="title">Mes Messages</h1>
    </div>

    <div class="header-right">
      <a href="client-interface.php" class="btn-retour-pro">
        <span class="arrow"></span>Retour a l'interface
      </a>
    </div>

  </header>
  <main class="msg-wrap">
    <section class="conversations-list">
      <h2>Conversations</h2>
      <?php if (empty($deals)): ?>
        <p>Aucune conversation.</p>
      <?php else: ?>
        <?php foreach ($deals as $d): $o = ($d['client_username'] === $user) ? $d['vendeur_username'] : $d['client_username'];
          $active = ((int)$d['id_deal'] === $deal) ? 'active' : '';
          $lastMessageStmt->execute(['id' => (int)$d['id_deal']]);
          $lastMessageRow = $lastMessageStmt->fetch(PDO::FETCH_ASSOC);
          $lastPreview = $lastMessageRow['contenu'] ?? '';
        ?>
          <div class="conv-item <?= $active ?>" data-deal="<?= (int)$d['id_deal'] ?>">
            <a href="../html/messages.php?deal=<?= (int)$d['id_deal'] ?>">Deal avec <?= htmlspecialchars($o) ?></a>
            <?php if ($role === 'client'): ?>
              - <a class="vendor-link" href="../html/vendor_profile_client.php?vendeur=<?= urlencode($d['vendeur_username']) ?>">Profil vendeur</a>
            <?php endif; ?>
            <div><small>Derniere activite: <?= htmlspecialchars($d['last_activity']) ?> | Messages: <span class="meta-count"><?= (int)$d['message_count'] ?></span></small></div>
            <div class="last-preview"><?= $lastPreview !== '' ? 'Dernier message : ' . htmlspecialchars(mb_strimwidth($lastPreview, 0, 48, '...')) : 'Aucun message encore.' ?></div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </section>

    <section class="conversation-detail">
      <?php if ($deal <= 0): ?>
        <div class="no-deal">Selectionnez une conversation pour afficher le chat.</div>
      <?php else: ?>
        <h2>
          Chat avec <?= htmlspecialchars($other) ?>
          <?php if ($role === 'client'): ?>
            - <a class="vendor-link" href="../html/vendor_profile_client.php?vendeur=<?= urlencode($other) ?>">Voir profil vendeur</a>
          <?php endif; ?>
        </h2>
        <div class="chat-box">
          <?php foreach ($messages as $msg): ?>
            <div class="msg-line <?= $msg['sender_username'] === $user ? 'msg-me' : 'msg-other' ?>">
              <strong>
                <?php if ($role === 'client' && $msg['sender_username'] !== $user): ?>
                  <a class="vendor-link" href="../html/vendor_profile.php?vendeur=<?= urlencode($msg['sender_username']) ?>"><?= htmlspecialchars($msg['sender_username']) ?></a>
                <?php else: ?>
                  <?= htmlspecialchars($msg['sender_username']) ?>
                <?php endif; ?>
              </strong>
              (<?= htmlspecialchars($msg['created_at']) ?>): <?= nl2br(htmlspecialchars($msg['contenu'])) ?>
            </div>
          <?php endforeach; ?>
          <?php if (empty($messages)): ?><p>Aucun message.</p><?php endif; ?>
        </div>
        <form id="msgForm" action="../php/send_message.php" method="post" class="msg-form">
          <input type="hidden" name="id_deal" value="<?= $deal ?>">
          <input id="msgInput" type="text" name="contenu" required placeholder="Votre message...">
          <button type="submit">Envoyer</button>
        </form>
        <?php if ($role === 'client'): ?>
          <form action="/php/leave_review.php" method="post" class="review-box">
            <h3>Laisser un avis au fournisseur</h3>
            <input type="hidden" name="id_deal" value="<?= $deal ?>">
            <div class="stars">
              <input type="radio" name="rating" id="star5" value="5"><label for="star5">★</label>
              <input type="radio" name="rating" id="star4" value="4"><label for="star4">★</label>
              <input type="radio" name="rating" id="star3" value="3"><label for="star3">★</label>
              <input type="radio" name="rating" id="star2" value="2"><label for="star2">★</label>
              <input type="radio" name="rating" id="star1" value="1"><label for="star1">★</label>
            </div>
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

      function scrollChatToBottom() {
        if (chatBox) {
          chatBox.scrollTop = chatBox.scrollHeight;
        }
      }

      if (chatBox) {
        scrollChatToBottom();
      }

      if (msgForm) {
        msgForm.addEventListener('submit', async function(e) {
          e.preventDefault();

          try {
            const formData = new FormData(msgForm);

            const response = await fetch(msgForm.action, {
              method: 'POST',
              body: formData,
              headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
              }
            });

            const rawText = await response.text();
            const cleanText = rawText.replace(/^\uFEFF/, '').trim();
            console.log('REPONSE send_message.php =', cleanText, 'status=', response.status);

            if (!response.ok) {
              alert('Erreur de connexion au serveur.');
              return;
            }

            const data = JSON.parse(cleanText);

            if (!data.success) {
              alert(data.message || 'Erreur lors de l’envoi du message.');
              return;
            }

            const msg = data.message_data || data.message;

            if (!msg || !msg.sender_username || !msg.created_at) {
              alert('Le message a ete envoye, mais la reponse du serveur est incomplete.');
              return;
            }

            const msgLine = document.createElement('div');
            msgLine.className = 'msg-line msg-me';
            msgLine.innerHTML =
              '<strong>' + msg.sender_username + '</strong> (' +
              msg.created_at + '): ' +
              msg.contenu.replace(/\n/g, '<br>');

            chatBox.appendChild(msgLine);
            msgInput.value = '';
            scrollChatToBottom();

            const activeItem = document.querySelector('.conv-item.active');
            if (activeItem) {
              const countElem = activeItem.querySelector('.meta-count');
              if (countElem) {
                let count = parseInt(countElem.textContent || '0', 10) + 1;
                countElem.textContent = count;
              }

              const preview = activeItem.querySelector('.last-preview');
              if (preview) {
                preview.textContent =
                  'Dernier message : ' +
                  (msg.contenu.length > 48 ? msg.contenu.substr(0, 48) + '...' : msg.contenu);
              }
            }

          } catch (error) {
            console.error('Erreur JS message =', error);
            alert('Erreur de connexion au serveur.');
          }
        });
      }
    });
  </script>
  <div class="about-site">
    <h4><strong>A propos de nous</strong></h4>
    <p>
      Importy est un site de vente en ligne qui permet de découvrir et d’acheter
      facilement différents produits dans plusieurs catégories comme la beauté, la mode,
      l’électroménager ou encore les produits technologiques.
      Le but est de proposer une plateforme simple et agréable à utiliser,
      où l’utilisateur peut rechercher des articles.Ce qui distingue Importy,
      c’est qu'avec cette platforme, les utilisateurs peuvent également poster des demandes spécifiques pour des produits qu’ils recherchent,
      permettant ainsi aux vendeurs de proposer des offres personnalisées.

      Importy vise à offrir une expérience d’achat fluide et sécurisée, avec un large choix de produits
      pour répondre aux attentes de tous les clients.

    </p>
  </div>

  <div class="services">

    <div class="service">
      <div class="icon"><i class="fa-solid fa-store"></i></div>
      <div>
        <h4>pour les vendeurs</h4>
        <p>Proposez vos produits et gérez votre activité en toute simplicité.</p>
      </div>
    </div>

    <div class="service">
      <div class="icon"><i class="fa-solid fa-truck"></i></div>
      <div>
        <h4>Livraison standard offerte</h4>
        <p>just verifier votre adresse dans le compte</p>

      </div>
    </div>

    <div class="service">
      <div class="icon"><i class="fa-regular fa-credit-card"></i></div>
      <div>
        <h4>Paiements a la livraison</h4>
        <p>vous payez le livreur lorsque vous recevez votre commande</p>

      </div>
    </div>

    <div class="service">
      <div class="icon"><i class="fa-solid fa-undo"></i></div>
      <div>
        <h4>Retours</h4>
        <p>sous 14 jours</p>
      </div>
    </div>

  </div>
</body>

</html>
