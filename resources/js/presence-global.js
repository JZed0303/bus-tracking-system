// resources/js/presence-global.js

export function initGlobalPresence() {
  if (!window.Echo) return;
  if (window.__globalPresenceInitialized) return;

  window.__globalPresenceInitialized = true;
  window.__onlineUsers = new Set();

  window.Echo.join('presence.app')
    .here((users) => {
      window.__onlineUsers = new Set((users || []).map((u) => String(u.presence_key)));
      window.dispatchEvent(new Event('presence:changed'));
    })
    .joining((u) => {
      if (u?.presence_key) window.__onlineUsers.add(String(u.presence_key));
      window.dispatchEvent(new Event('presence:changed'));
    })
    .leaving((u) => {
      if (u?.presence_key) window.__onlineUsers.delete(String(u.presence_key));
      window.dispatchEvent(new Event('presence:changed'));
    });
}
