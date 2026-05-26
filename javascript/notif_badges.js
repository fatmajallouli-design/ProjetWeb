(function () {
  function updateBadge(linkEl, count) {
    if (!linkEl) return;
    let badge = linkEl.querySelector('.badge');
    if (count > 0) {
      if (!badge) {
        badge = document.createElement('span');
        badge.className = 'badge';
        linkEl.appendChild(badge);
      }
      badge.textContent = String(count);
    } else if (badge) {
      badge.remove();
    }
  }

  async function poll() {
    try {
      const r = await fetch('/php/fetch_notifications.php?limit=1', {
        headers: { 'Accept': 'application/json' },
        credentials: 'same-origin'
      });
      if (!r.ok) return;
      const data = await r.json();
      if (!data || !data.success) return;
      const notifLink = document.querySelector('a[href*="notifications.php"]');
      const msgLink = document.querySelector('a[href*="messages.php"]');
      updateBadge(notifLink, data.unread_count || 0);
      updateBadge(msgLink, data.unread_messages || 0);
    } catch (e) { /* silent */ }
  }

  document.addEventListener('DOMContentLoaded', function () {
    poll();
    setInterval(poll, 8000);
  });
})();
