const splitUrls = (value) =>
  String(value ?? '')
    .split(',')
    .map((item) => item.trim())
    .filter(Boolean);

const parseInteger = (value, fallback) => {
  const parsed = Number.parseInt(String(value ?? ''), 10);
  return Number.isFinite(parsed) ? parsed : fallback;
};

const parseBoolean = (value, fallback = false) => {
  const normalized = String(value ?? '').trim().toLowerCase();
  if (['1', 'true', 'yes', 'on'].includes(normalized)) return true;
  if (['0', 'false', 'no', 'off'].includes(normalized)) return false;
  return fallback;
};

const parseIceTransportPolicy = (value) => {
  const normalized = String(value ?? '').trim().toLowerCase();
  return normalized === 'relay' ? 'relay' : 'all';
};

export function buildWebRtcRuntimePolicy() {
  return {
    connectTimeoutMs: Math.max(5000, parseInteger(import.meta.env.VITE_WEBRTC_CONNECT_TIMEOUT_MS, 30000)),
    disconnectGraceMs: Math.max(1000, parseInteger(import.meta.env.VITE_WEBRTC_DISCONNECT_GRACE_MS, 5000)),
    maxIceRestartAttempts: Math.max(0, parseInteger(import.meta.env.VITE_WEBRTC_MAX_ICE_RESTARTS, 2)),
    forceRelay: parseBoolean(import.meta.env.VITE_WEBRTC_FORCE_RELAY, false),
  };
}

export function buildWebRtcIceConfiguration() {
  const stunUrls = splitUrls(import.meta.env.VITE_WEBRTC_STUN_URLS);
  const turnUrls = splitUrls(import.meta.env.VITE_WEBRTC_TURN_URLS);
  const turnUsername = import.meta.env.VITE_WEBRTC_TURN_USERNAME;
  const turnCredential = import.meta.env.VITE_WEBRTC_TURN_CREDENTIAL;
  const requestedIceTransportPolicy = parseIceTransportPolicy(import.meta.env.VITE_WEBRTC_ICE_TRANSPORT_POLICY);
  const runtimePolicy = buildWebRtcRuntimePolicy();

  const iceServers = [];

  if (stunUrls.length > 0) {
    iceServers.push({ urls: stunUrls });
  } else {
    iceServers.push({ urls: ['stun:stun.l.google.com:19302'] });
  }

  if (turnUrls.length > 0 && turnUsername && turnCredential) {
    iceServers.push({
      urls: turnUrls,
      username: turnUsername,
      credential: turnCredential,
    });
  }

  const hasTurnServer = turnUrls.length > 0 && Boolean(turnUsername) && Boolean(turnCredential);
  const iceTransportPolicy =
    runtimePolicy.forceRelay || requestedIceTransportPolicy === 'relay'
      ? hasTurnServer
        ? 'relay'
        : 'all'
      : 'all';

  if ((runtimePolicy.forceRelay || requestedIceTransportPolicy === 'relay') && !hasTurnServer) {
    console.warn('[WebRTC] Relay-only policy requested, but TURN credentials are incomplete. Falling back to policy "all".');
  }

  return {
    iceServers,
    iceTransportPolicy,
    bundlePolicy: 'max-bundle',
    iceCandidatePoolSize: Math.max(0, Math.min(10, parseInteger(import.meta.env.VITE_WEBRTC_ICE_CANDIDATE_POOL_SIZE, 10))),
  };
}
