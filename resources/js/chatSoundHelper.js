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

function resolveCallSoundSource() {
  const audioEl = document.getElementById('chat-sound');
  return audioEl?.getAttribute('src') || audioEl?.src || '/sounds/chat.mp3';
}

export function startCallSound() {
  const src = resolveCallSoundSource();
  if (!src) return;

  const existingSound = window.__incomingCallSound;
  if (existingSound) {
    existingSound.currentTime = 0;
    existingSound.loop = true;
    existingSound.play().catch((err) => {
      console.error('[call-sound] play() rejected:', err);
    });
    return;
  }

  try {
    const sound = new Audio(src);
    sound.volume = 1.0;
    sound.loop = true;
    window.__incomingCallSound = sound;
    sound.play().catch((err) => {
      console.error('[call-sound] play() rejected:', err);
    });
  } catch (error) {
    console.error('[call-sound] Exception when playing sound:', error);
  }
}

export function stopCallSound() {
  const sound = window.__incomingCallSound;
  if (!sound) return;

  try {
    sound.pause();
    sound.currentTime = 0;
  } catch (error) {
    console.error('[call-sound] Exception when stopping sound:', error);
  }
}
