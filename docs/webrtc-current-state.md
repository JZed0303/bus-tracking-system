# WebRTC Current State

## Purpose

This document describes the current WebRTC-related setup in the Bus Tracking System as it exists now in the codebase, including:

- how signaling works
- how media negotiation works
- which files are involved
- what was recently changed
- why calls work on the same network more easily than on different networks
- what is still pending for production-grade verification and hardening

## High-Level Summary

The system currently supports operator-to-bus video calling using:

- Laravel API endpoints for signaling and call state
- Axios requests from the frontend to exchange offer and answer, plus realtime signaling for ICE candidates
- WebRTC in the browser for media negotiation
- Pusher / Laravel Echo for realtime call notifications

The application is now configured to use a managed TURN service through Metered using Vite WebRTC environment variables.

Because of that:

- same-network calls can work
- different-network calls now have TURN relay fallback available
- cross-network reliability should be improved once the active call selects a relay candidate
- TURN usage still needs to be verified during a live call through browser WebRTC stats

## Main WebRTC Flow

The current implementation uses a standard WebRTC flow:

1. An operator starts a call to a bus.
2. The app creates a call record through the backend.
3. The caller creates an SDP offer.
4. The callee fetches and applies the offer.
5. The callee creates an SDP answer.
6. The caller fetches and applies the answer.
7. Both sides exchange ICE candidates through realtime backend signaling.
8. If a direct media path is possible, audio/video connects.

Important: the backend is acting as the signaling layer. It is not relaying media. The actual media still tries to flow peer-to-peer between browsers/devices.

## Signaling vs Media

### Signaling

Signaling is already implemented and working through your Laravel API:

- `POST /api/video-calls/start`
- `POST /api/video-calls/{id}/accept`
- `POST /api/video-calls/{id}/reject`
- `POST /api/video-calls/{id}/end`
- `POST /api/video-calls/{id}/offer`
- `POST /api/video-calls/{id}/answer`
- `POST /api/video-calls/{id}/ice`
- `GET /api/video-calls/{id}`
- `GET /api/video-calls/{id}/ice`

This means your application can:

- create and track a call
- store and retrieve SDP offers and answers
- receive and broadcast ICE candidates in realtime
- notify users about incoming and updated calls

### Media

Media is handled by `RTCPeerConnection` in the browser. This is where the actual camera and microphone streams are negotiated.

The connection can fail even if signaling succeeds.

That is the current situation when the UI shows:

- call exists
- status connected or accepted
- offer yes
- answer yes
- but connection remains `connecting`

That means signaling succeeded but media path establishment did not fully succeed.

## Why Same-Network Calls Work More Easily

When both devices are on the same LAN or on friendly NATs, WebRTC can often create a direct peer-to-peer path using local or public ICE candidates discovered through STUN.

That is why your call can work on the same network.

## Why Different-Network Calls Get Stuck on `connecting`

When users are on different networks, routers and NAT devices often block direct peer-to-peer traffic.

In that case:

- STUN can discover addresses
- but STUN cannot relay media
- if direct connectivity fails, WebRTC needs TURN

Without TURN:

- offer/answer can still complete
- ICE candidates can still be exchanged
- the browser can still show `connecting`
- remote audio/video may never arrive

That is the most likely reason for the current different-network issue.

## Current ICE Server Setup

The current app now reads ICE configuration from frontend environment variables through:

    - [webrtcIceConfiguration.js](/var/www/html/Bus-Tracking-System/resources/js/utils/webrtcIceConfiguration.js)

    The behavior is:

    - use custom STUN URLs from `VITE_WEBRTC_STUN_URLS` if provided
    - otherwise fall back to `stun:stun.l.google.com:19302`
    - include TURN only if all of these are present:
      - `VITE_WEBRTC_TURN_URLS`
      - `VITE_WEBRTC_TURN_USERNAME`
      - `VITE_WEBRTC_TURN_CREDENTIAL`

    The current `.env` contains STUN and TURN values for Metered, and the frontend has already been rebuilt so Vite can embed them into the browser bundle.

    ## Frontend Files Involved

    ### 1. Live bus map page

    - [LiveBusMap.jsx](/var/www/html/Bus-Tracking-System/resources/js/pages/Admin/LiveBusMap.jsx)

    Relevant responsibilities:

    - loads live bus data
    - starts a video call from the live map
    - accepts, rejects, and ends calls
    - creates and manages `RTCPeerConnection`
    - sends local ICE candidates to backend
    - fetches and applies remote ICE candidates
    - creates offer and answer
    - updates UI with connection state

    Key WebRTC areas:

    - ICE config setup: [LiveBusMap.jsx](/var/www/html/Bus-Tracking-System/resources/js/pages/Admin/LiveBusMap.jsx#L285)
    - start call request: [LiveBusMap.jsx](/var/www/html/Bus-Tracking-System/resources/js/pages/Admin/LiveBusMap.jsx#L376)
    - local media access: [LiveBusMap.jsx](/var/www/html/Bus-Tracking-System/resources/js/pages/Admin/LiveBusMap.jsx#L515)
    - send ICE candidate: [LiveBusMap.jsx](/var/www/html/Bus-Tracking-System/resources/js/pages/Admin/LiveBusMap.jsx#L536)
    - create peer connection: [LiveBusMap.jsx](/var/www/html/Bus-Tracking-System/resources/js/pages/Admin/LiveBusMap.jsx#L562)
    - fetch/apply remote ICE: [LiveBusMap.jsx](/var/www/html/Bus-Tracking-System/resources/js/pages/Admin/LiveBusMap.jsx#L616)
    - answer incoming offer: [LiveBusMap.jsx](/var/www/html/Bus-Tracking-System/resources/js/pages/Admin/LiveBusMap.jsx#L658)
    - create/send offer: [LiveBusMap.jsx](/var/www/html/Bus-Tracking-System/resources/js/pages/Admin/LiveBusMap.jsx#L720)
    - apply remote answer: [LiveBusMap.jsx](/var/www/html/Bus-Tracking-System/resources/js/pages/Admin/LiveBusMap.jsx#L770)

    ### 2. Dedicated video call page

    - [VideoCallPage.jsx](/var/www/html/Bus-Tracking-System/resources/js/pages/Admin/VideoCallPage.jsx)

    Relevant responsibilities:

- dedicated page for a bus call session
- duplicates the same WebRTC negotiation logic
- manages call state and local/remote streams
- sends/fetches ICE candidates

Key WebRTC areas:

- ICE config setup: [VideoCallPage.jsx](/var/www/html/Bus-Tracking-System/resources/js/pages/Admin/VideoCallPage.jsx#L81)
- sync call record: [VideoCallPage.jsx](/var/www/html/Bus-Tracking-System/resources/js/pages/Admin/VideoCallPage.jsx#L119)
- local media access: [VideoCallPage.jsx](/var/www/html/Bus-Tracking-System/resources/js/pages/Admin/VideoCallPage.jsx#L143)
- send ICE candidate: [VideoCallPage.jsx](/var/www/html/Bus-Tracking-System/resources/js/pages/Admin/VideoCallPage.jsx#L160)
- create peer connection: [VideoCallPage.jsx](/var/www/html/Bus-Tracking-System/resources/js/pages/Admin/VideoCallPage.jsx#L186)
- fetch/apply remote ICE: [VideoCallPage.jsx](/var/www/html/Bus-Tracking-System/resources/js/pages/Admin/VideoCallPage.jsx#L230)
- answer incoming offer: [VideoCallPage.jsx](/var/www/html/Bus-Tracking-System/resources/js/pages/Admin/VideoCallPage.jsx#L270)
- create/send offer: [VideoCallPage.jsx](/var/www/html/Bus-Tracking-System/resources/js/pages/Admin/VideoCallPage.jsx#L332)
- apply remote answer: [VideoCallPage.jsx](/var/www/html/Bus-Tracking-System/resources/js/pages/Admin/VideoCallPage.jsx#L382)

### 3. Shared ICE configuration helper

- [webrtcIceConfiguration.js](/var/www/html/Bus-Tracking-System/resources/js/utils/webrtcIceConfiguration.js)

This file was added to centralize ICE server creation so both pages use the same source of truth.

Responsibilities:

- read STUN URLs from Vite env
- read TURN URLs and credentials from Vite env
- build the `iceServers` array for `RTCPeerConnection`
- fall back to Google STUN if no custom STUN URLs exist

## Laravel / Infrastructure Files Involved

### 4. Reverse proxy trust

- [TrustProxies.php](/var/www/html/Bus-Tracking-System/app/Http/Middleware/TrustProxies.php)

This file was modified so Laravel trusts forwarded proxy headers:

- forwarded host
- forwarded port
- forwarded protocol

This is important because the app is accessed through ngrok, which is a reverse proxy.

Without correct proxy trust:

- Laravel may think the request is plain HTTP
- URL generation can be wrong
- secure behavior can become inconsistent

Current change:

- `protected $proxies = '*';`

### 5. Runtime environment

- [`.env`](/var/www/html/Bus-Tracking-System/.env)

Relevant current settings:

- `APP_URL=https://unboiled-soren-tonetically.ngrok-free.dev`
- `SESSION_SECURE_COOKIE=true`

Why they matter:

- `APP_URL` tells Laravel the external application URL
- `SESSION_SECURE_COOKIE=true` ensures cookies are treated as HTTPS-only

This improves remote access through ngrok.

### 6. Example environment template

- [`.env.example`](/var/www/html/Bus-Tracking-System/.env.example)

This file now documents the new frontend WebRTC env variables:

- `VITE_WEBRTC_STUN_URLS`
- `VITE_WEBRTC_TURN_URLS`
- `VITE_WEBRTC_TURN_USERNAME`
- `VITE_WEBRTC_TURN_CREDENTIAL`
- `VITE_WEBRTC_ICE_TRANSPORT_POLICY`
- `VITE_WEBRTC_ICE_CANDIDATE_POOL_SIZE`
- `VITE_WEBRTC_CONNECT_TIMEOUT_MS`
- `VITE_WEBRTC_DISCONNECT_GRACE_MS`
- `VITE_WEBRTC_MAX_ICE_RESTARTS`
- `VITE_WEBRTC_FORCE_RELAY`

Recommended production behavior:

- keep offer and answer persistence enabled for recovery and auditability
- keep ICE candidate delivery ephemeral and realtime
- configure TURN on a host you control or through a managed provider
- prefer short-lived TURN credentials in production
- set a finite connect timeout and automatic ICE restart budget so failed calls recover or fail fast

## Realtime Layer

The system also uses Laravel Echo and Pusher for realtime notifications.

Relevant file:

- [bootstrap.js](/var/www/html/Bus-Tracking-System/resources/js/bootstrap.js)

This is not the media transport itself. It is part of the notification/realtime layer used to keep call state in sync.

Important distinction:

- Echo/Pusher helps notify about call events
- WebRTC carries the camera/microphone media
- TURN is needed when WebRTC cannot create a direct path across networks

## Files Created and Modified

### Created

1. [webrtcIceConfiguration.js](/var/www/html/Bus-Tracking-System/resources/js/utils/webrtcIceConfiguration.js)
2. [webrtc-current-state.md](/var/www/html/Bus-Tracking-System/docs/webrtc-current-state.md)

### Modified

1. [LiveBusMap.jsx](/var/www/html/Bus-Tracking-System/resources/js/pages/Admin/LiveBusMap.jsx)
2. [VideoCallPage.jsx](/var/www/html/Bus-Tracking-System/resources/js/pages/Admin/VideoCallPage.jsx)
3. [TrustProxies.php](/var/www/html/Bus-Tracking-System/app/Http/Middleware/TrustProxies.php)
4. [`.env`](/var/www/html/Bus-Tracking-System/.env)
5. [`.env.example`](/var/www/html/Bus-Tracking-System/.env.example)

## What Was Changed in This Session

### 1. External app access was aligned with ngrok

In [`.env`](/var/www/html/Bus-Tracking-System/.env):

- `APP_URL` is set to the ngrok HTTPS URL
- `SESSION_SECURE_COOKIE=true` was added

This helps the app behave correctly when opened through `https://...ngrok-free.dev`.

### 2. Laravel was configured to trust proxy headers

In [TrustProxies.php](/var/www/html/Bus-Tracking-System/app/Http/Middleware/TrustProxies.php):

- trusted proxies were changed to `*`

This helps Laravel correctly interpret requests coming from ngrok.

### 3. WebRTC ICE configuration was centralized

Instead of hardcoding only:

- `stun:stun.l.google.com:19302`

the frontend now uses:

- configurable STUN
- optional TURN

through [webrtcIceConfiguration.js](/var/www/html/Bus-Tracking-System/resources/js/utils/webrtcIceConfiguration.js).

### 4. Both WebRTC pages now use the shared ICE configuration

Updated files:

- [LiveBusMap.jsx](/var/www/html/Bus-Tracking-System/resources/js/pages/Admin/LiveBusMap.jsx)
- [VideoCallPage.jsx](/var/www/html/Bus-Tracking-System/resources/js/pages/Admin/VideoCallPage.jsx)

This avoids duplicated hardcoded ICE server definitions.

### 5. Config cache was cleared

Laravel cache clear command was run:

```bash
php artisan optimize:clear
```

This ensured `.env` and framework configuration changes took effect.

## What Is Working Now

At the current state, the application has:

- working signaling endpoints
- working call record synchronization
- WebRTC offer and answer handling
- ICE candidate exchange through backend APIs
- frontend support for STUN
- frontend support for TURN through Metered
- ngrok-aware Laravel URL/session/proxy adjustments
- a successful frontend rebuild after the TURN env values were added

## What Is Not Yet Complete

Reliable different-network media connectivity has not been fully verified yet.

The missing piece is no longer TURN configuration itself.

What is still missing is proof from a live call that the browser is actually selecting a TURN relay candidate when direct connectivity is not possible.

Until that verification is done, a different-network failure could still be caused by:

- browser caching an older frontend build
- a stale or rotated TURN credential
- a candidate pair that never falls back to relay
- unrelated network or NAT behavior during the test

If TURN is not being used successfully, calls may still remain stuck at:

- `Connection: connecting`
- `ICE: checking`

even though the app appears to have started the call correctly.

## Current TURN Configuration Status

The current WebRTC environment values in [`.env`](/var/www/html/Bus-Tracking-System/.env) are now expected to follow this structure:

```env
VITE_WEBRTC_STUN_URLS="stun:stun.relay.metered.ca:80"
VITE_WEBRTC_TURN_URLS="turn:global.relay.metered.ca:80,turn:global.relay.metered.ca:80?transport=tcp,turn:global.relay.metered.ca:443,turns:global.relay.metered.ca:443?transport=tcp"
VITE_WEBRTC_TURN_USERNAME="your_metered_username"
VITE_WEBRTC_TURN_CREDENTIAL="your_metered_credential"
```

Important:

- do not commit live TURN credentials to version control
- do not paste real credentials into documentation
- Vite reads these values at build time, not dynamically at runtime

After adding or changing those values, the frontend must be rebuilt so Vite embeds them:

```bash
npm run build
```

That rebuild has already been completed in this session.

## How To Verify TURN Is Actually Working

Configuration alone does not prove TURN relay usage. A live call must be tested.

### Recommended test

1. Start a video call between two devices.
2. Prefer different networks:
   - one device on Wi-Fi
   - one device on mobile data or another external network
3. Keep the call active while checking browser stats.

### In-app signs

During the call, the UI should progress through states such as:

- `ICE: checking`
- `ICE: connected` or `ICE: completed`
- `Connection: connected`
- `Remote video connected.`

If the call connects successfully across different networks, TURN is very likely helping.

### Browser verification with `chrome://webrtc-internals`

Open `chrome://webrtc-internals` before or during an active call, then inspect the live `RTCPeerConnection` entry.

Proof that TURN is in use includes any of the following:

- selected candidate pair uses a candidate with `candidateType = relay`
- selected candidate pair references Metered relay hostnames such as `global.relay.metered.ca`
- the active connection succeeds across different networks and the selected ICE pair includes `relay`

Candidate type meanings:

- `host` = direct local candidate
- `srflx` = STUN-discovered public candidate
- `relay` = TURN relay candidate

### What the current screenshots proved

The browser screenshots reviewed in this session confirmed:

- `getUserMedia()` is working
- the browser can access microphone and camera devices

They did not yet confirm:

- that the selected ICE candidate pair is `relay`
- that Metered TURN was chosen during the live call

## Practical Interpretation of Current Status

Right now the project is in this state:

- signaling is implemented
- WebRTC browser flow is implemented
- TURN is configured through Metered
- the frontend build includes the TURN configuration
- live relay usage is not yet proven from browser stats

That means the application is now materially closer to reliable different-network calling, but production confidence still depends on confirming successful relay usage during real cross-network tests.

## Recommended Next Actions

1. Test one call on the same network and one call on different networks.
2. Use `chrome://webrtc-internals` during the live call.
3. Confirm that the selected ICE candidate pair includes `relay`.
4. If relay is confirmed, document the test result and date.
5. Rotate any TURN credential that was exposed in screenshots or chat during setup.
6. Consider moving to short-lived server-generated TURN credentials for production hardening.
