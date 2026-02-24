// resources/js/bootstrap.js
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const csrfToken = document
  .querySelector('meta[name="csrf-token"]')
  ?.getAttribute('content');

// OPTIONAL debug (remove later)
// Pusher.logToConsole = true;

window.Echo = new Echo({
  broadcaster: 'pusher',
  key: import.meta.env.VITE_PUSHER_APP_KEY,
  cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
  forceTLS: true,

  // IMPORTANT for private channels in Laravel (web session)
  authEndpoint: '/broadcasting/auth',
  auth: {
    headers: {
      'X-CSRF-TOKEN': csrfToken,
      'X-Requested-With': 'XMLHttpRequest',
      Accept: 'application/json',
    },
  },
});

console.log('[bootstrap] Echo initialized:', !!window.Echo);

// 🔓 Unlock chat sound after first user interaction
document.addEventListener('DOMContentLoaded', () => {
  const audioEl = document.getElementById('chat-sound');
  if (!audioEl) {
    console.warn('[chat-sound] #chat-sound element not found on DOMContentLoaded');
    return;
  }

  const unlock = () => {
    audioEl
      .play()
      .then(() => {
        // Immediately pause; this marks it as user-initiated
        audioEl.pause();
        audioEl.currentTime = 0;
        window.chatSoundReady = true;
        console.log('[chat-sound] Audio unlocked and ready');
      })
      .catch((err) => {
        console.warn('[chat-sound] Unlock play blocked:', err);
      });

    document.removeEventListener('click', unlock);
    document.removeEventListener('keydown', unlock);
  };

  // First click/keypress unlocks audio
  document.addEventListener('click', unlock, { once: true });
  document.addEventListener('keydown', unlock, { once: true });
});
