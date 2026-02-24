import { playChatSound } from './chatSoundHelper';

document.addEventListener('DOMContentLoaded', () => {
  if (!window.Echo || !window.authUser?.id) return;

  const userId = window.authUser.id;
  console.log('[chat-global] Listening on users.' + userId);

  window.Echo.private(`users.${userId}`)
    .listen('.chat.message.received', (payload) => {
      console.log('[chat-global] New message', payload);
      playChatSound();
    });
});
