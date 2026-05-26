<?php
session_start();
if (empty($_SESSION['user']['username'])) {
  header('Location: /login.php');
  exit();
}
$role = $_SESSION['user']['role'] ?? 'client';
require_once(__DIR__ . '/../php/connexionBD.php');
$bdd = ConnexionBD::getInstance();
ConnexionBD::ensureWorkflowTables();
$user = $_SESSION['user']['username'];
$homeUrl = ($role === 'vendeur') ? '/php/page_vendeur.php' : '/html/client-interface.php';

$stmt = $bdd->prepare('SELECT id_notif, type, title, body, link, actor_username, related_id, is_read, created_at
    FROM notification
    WHERE recipient_username = :u
    ORDER BY created_at DESC, id_notif DESC
    LIMIT 50');
$stmt->execute(['u' => $user]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$dealStmt = $bdd->prepare(($role === 'vendeur'
    ? "SELECT dr.*, d.nom_produit FROM deal_request dr JOIN demande d ON d.id_demande = dr.id_demande WHERE dr.vendeur_username = :c ORDER BY dr.created_at DESC, dr.id_deal DESC"
    : "SELECT dr.*, d.nom_produit FROM deal_request dr JOIN demande d ON d.id_demande = dr.id_demande WHERE dr.client_username = :c ORDER BY dr.created_at DESC, dr.id_deal DESC"));
$dealStmt->execute(['c' => $user]);
$dealRows = $dealStmt->fetchAll(PDO::FETCH_ASSOC);

if ($role === 'vendeur') {
  $bdd->prepare('UPDATE deal_request SET vendeur_seen_at = NOW() WHERE vendeur_username = :u')->execute(['u' => $user]);
} else {
  $bdd->prepare('UPDATE deal_request SET client_seen_at = NOW() WHERE client_username = :u')->execute(['u' => $user]);
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Notifications</title>
  <link rel="stylesheet" href="/css/style.css">
  <link rel="stylesheet" href="/css/notifications.css">
  <style>
    .notif-list { display:flex; flex-direction:column; gap:10px; margin:18px 0; }
    .notif-row { background:#fff; border:1px solid #e6dffc; border-radius:10px; padding:14px 16px; display:flex; gap:14px; align-items:flex-start; transition:background .2s; }
    .notif-row.unread { background:#f3edff; border-color:#c1a8ff; }
    .notif-icon { width:38px; height:38px; border-radius:50%; background:#7C3AED; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:600; }
    .notif-content { flex:1; }
    .notif-title { font-weight:600; margin:0 0 4px; color:#2a1b50; }
    .notif-body { margin:0; color:#444; font-size:0.92rem; }
    .notif-meta { color:#888; font-size:0.78rem; margin-top:6px; }
    .notif-actions { display:flex; gap:8px; flex-wrap:wrap; margin-top:8px; }
    .notif-actions a, .notif-actions button { font-size:0.82rem; padding:5px 10px; border-radius:6px; border:none; cursor:pointer; text-decoration:none; }
    .notif-actions .open-btn { background:#7C3AED; color:#fff; }
    .notif-actions .read-btn { background:#e6e6e6; color:#333; }
    .mark-all-btn { background:#7C3AED; color:#fff; border:none; padding:8px 14px; border-radius:8px; cursor:pointer; }
    .empty-notif { padding:20px; color:#777; text-align:center; }
  </style>
</head>

<body>
  <header class="top-header">
    <div class="header-left">
      <a href="<?= $homeUrl ?>" class="logo">
        <img src="/files_profil/logo.png" alt="Importy" class="logo-img">
      </a>
    </div>
    <div class="header-center">
      <h1 class="title">Mes notifications</h1>
    </div>
    <div class="header-right">
      <a href="<?= $homeUrl ?>" class="header-btn retour-btn">&larr; Retour a l'accueil</a>
    </div>
  </header>

  <main class="notif-wrap">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-top:12px;">
      <h2>Notifications recentes</h2>
      <button id="markAllBtn" class="mark-all-btn">Tout marquer comme lu</button>
    </div>

    <div id="notifList" class="notif-list">
      <?php foreach ($rows as $r): ?>
        <div class="notif-row <?= $r['is_read'] ? '' : 'unread' ?>" data-id="<?= (int)$r['id_notif'] ?>">
          <div class="notif-icon">
            <?php $i = strtoupper(substr($r['actor_username'] ?? $r['type'] ?? '?', 0, 1)); ?>
            <?= htmlspecialchars($i) ?>
          </div>
          <div class="notif-content">
            <p class="notif-title"><?= htmlspecialchars($r['title']) ?></p>
            <?php if (!empty($r['body'])): ?>
              <p class="notif-body"><?= nl2br(htmlspecialchars($r['body'])) ?></p>
            <?php endif; ?>
            <div class="notif-meta"><?= htmlspecialchars($r['created_at']) ?> &middot; type: <?= htmlspecialchars($r['type']) ?></div>
            <div class="notif-actions">
              <?php if (!empty($r['link'])): ?>
                <a class="open-btn" href="<?= htmlspecialchars($r['link']) ?>">Ouvrir</a>
              <?php endif; ?>
              <?php if (!$r['is_read']): ?>
                <button class="read-btn" data-mark="<?= (int)$r['id_notif'] ?>">Marquer lu</button>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (empty($rows)): ?>
        <div class="empty-notif">Aucune notification.</div>
      <?php endif; ?>
    </div>

    <?php if (!empty($dealRows)): ?>
      <h2 style="margin-top:30px;"><?= $role === 'vendeur' ? 'Offres envoyees' : 'Demandes des vendeurs' ?></h2>
      <?php foreach ($dealRows as $r): ?>
        <article class="notif-card">
          <h3><?= htmlspecialchars($r['nom_produit']) ?></h3>
          <p class="notif-meta">
            <?php if ($role === 'client'): ?>
              Vendeur:
              <a class="vendor-link" href="/html/vendor_profile_client.php?vendeur=<?= urlencode($r['vendeur_username']) ?>">
                <?= htmlspecialchars($r['vendeur_username']) ?>
              </a>
            <?php else: ?>
              Client: <?= htmlspecialchars($r['client_username']) ?>
            <?php endif; ?>
            | Prix: <?= htmlspecialchars($r['prix_propose']) ?> TND
            | Date: <?= htmlspecialchars($r['created_at']) ?>
          </p>
          <p><?= nl2br(htmlspecialchars($r['message'])) ?></p>
          <div class="notif-actions">
            <?php if ($role === 'client'): ?>
              <?php if (trim(strtolower($r['status'])) !== 'accepte'): ?>
                <form method="POST" action="/php/accepter_offre.php" style="display:inline;">
                  <input type="hidden" name="id_deal" value="<?= (int)$r['id_deal'] ?>">
                  <button type="submit" class="btn-accept">Accepter offre</button>
                </form>
              <?php else: ?>
                <span class="status-label">Offre acceptee</span>
              <?php endif; ?>
              <a href="/html/vendor_profile_client.php?vendeur=<?= urlencode($r['vendeur_username']) ?>">Voir profil vendeur</a>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    <?php endif; ?>
  </main>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const list = document.getElementById('notifList');

      async function markRead(id) {
        const fd = new FormData();
        if (id) fd.set('id_notif', String(id));
        const r = await fetch('/php/mark_notifications_read.php', { method: 'POST', body: fd });
        const data = await r.json();
        return data;
      }

      list.addEventListener('click', async function (e) {
        const btn = e.target.closest('button[data-mark]');
        if (!btn) return;
        const id = btn.dataset.mark;
        const row = btn.closest('.notif-row');
        try {
          await markRead(id);
          if (row) row.classList.remove('unread');
          btn.remove();
        } catch (err) { /* silent */ }
      });

      document.getElementById('markAllBtn').addEventListener('click', async function () {
        try {
          await markRead(null);
          document.querySelectorAll('.notif-row.unread').forEach(r => r.classList.remove('unread'));
          document.querySelectorAll('button[data-mark]').forEach(b => b.remove());
        } catch (err) { /* silent */ }
      });

      async function refresh() {
        try {
          const r = await fetch('/php/fetch_notifications.php?limit=20', { headers: { 'Accept': 'application/json' } });
          if (!r.ok) return;
          const data = await r.json();
          if (!data.success) return;
          const existingIds = new Set([...list.querySelectorAll('.notif-row')].map(el => el.dataset.id));
          data.notifications.forEach(n => {
            const idStr = String(n.id_notif);
            if (existingIds.has(idStr)) return;
            const div = document.createElement('div');
            div.className = 'notif-row' + (n.is_read ? '' : ' unread');
            div.dataset.id = n.id_notif;
            const icon = document.createElement('div');
            icon.className = 'notif-icon';
            icon.textContent = (n.actor_username || n.type || '?').slice(0, 1).toUpperCase();
            div.appendChild(icon);
            const content = document.createElement('div');
            content.className = 'notif-content';
            const title = document.createElement('p');
            title.className = 'notif-title';
            title.textContent = n.title;
            content.appendChild(title);
            if (n.body) {
              const body = document.createElement('p');
              body.className = 'notif-body';
              body.textContent = n.body;
              content.appendChild(body);
            }
            const meta = document.createElement('div');
            meta.className = 'notif-meta';
            meta.textContent = n.created_at + ' · type: ' + n.type;
            content.appendChild(meta);
            const actions = document.createElement('div');
            actions.className = 'notif-actions';
            if (n.link) {
              const a = document.createElement('a');
              a.className = 'open-btn';
              a.href = n.link;
              a.textContent = 'Ouvrir';
              actions.appendChild(a);
            }
            if (!n.is_read) {
              const b = document.createElement('button');
              b.className = 'read-btn';
              b.dataset.mark = n.id_notif;
              b.textContent = 'Marquer lu';
              actions.appendChild(b);
            }
            content.appendChild(actions);
            div.appendChild(content);
            list.insertBefore(div, list.firstChild);
            const empty = list.querySelector('.empty-notif');
            if (empty) empty.remove();
          });
        } catch (err) { /* silent */ }
      }
      setInterval(refresh, 8000);
    });
  </script>
</body>
</html>
