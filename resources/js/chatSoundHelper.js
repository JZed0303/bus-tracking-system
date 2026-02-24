// resources/js/chatSoundHelper.js

export function playChatSound() {
  const audioEl = document.getElementById('chat-sound');

  if (!audioEl) {
    console.warn('[chat-sound] #chat-sound element NOT found');
    return;
  }

  const src = audioEl.getAttribute('src') || audioEl.src;
  if (!src) {
    console.warn('[chat-sound] Audio element has no src');
    return;
  }

  console.log('[chat-sound] Creating new Audio for', src);

  try {
    const sound = new Audio(src);

    // Optional: make sure it doesn’t stack ridiculously loud if many messages
    sound.volume = 1.0;

    sound
      .play()
      .then(() => {
        console.log('[chat-sound] Sound played');
      })
      .catch((err) => {
        console.error('[chat-sound] play() rejected:', err);
      });
  } catch (e) {
    console.error('[chat-sound] Exception when playing sound:', e);
  }
}
