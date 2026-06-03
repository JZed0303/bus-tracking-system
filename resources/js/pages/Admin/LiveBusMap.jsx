import React, { useEffect, useMemo, useRef, useState } from 'react';
import { MapContainer, TileLayer, Marker, Popup, useMap } from 'react-leaflet';
import L from 'leaflet';
import axios from 'axios';
import { buildWebRtcIceConfiguration, buildWebRtcRuntimePolicy } from '../../utils/webrtcIceConfiguration';
import { startCallSound, stopCallSound } from '../../chatSoundHelper';

const busMarkerIcon = L.icon({
  iconUrl: '/images/marker/bus icon.png',
  iconSize: [42, 42],
  iconAnchor: [21, 21],
  popupAnchor: [0, -20],
});

function normalizeBusPayload(payload) {
  if (!payload) return null;

  const lat = payload.lat ?? payload.latitude ?? null;
  const lng = payload.lng ?? payload.longitude ?? null;
  const plateNumber = payload.plate_number ?? payload.bus ?? null;

  const busId =
    payload.bus_id !== undefined && payload.bus_id !== null
      ? Number(payload.bus_id)
      : payload.id !== undefined && payload.id !== null
      ? Number(payload.id)
      : null;

  if (!Number.isFinite(busId)) return null;

  return {
    ...payload,
    bus_id: busId,
    plate_number: plateNumber,
    trip_id: payload.trip_id ?? null,
    lat: lat !== null && lat !== '' ? Number(lat) : null,
    lng: lng !== null && lng !== '' ? Number(lng) : null,
    speed:
      payload.speed !== null && payload.speed !== undefined
        ? Number(payload.speed)
        : null,
    onboard_count:
      payload.onboard_count !== null && payload.onboard_count !== undefined
        ? Number(payload.onboard_count)
        : null,
    bus_capacity:
      payload.bus_capacity !== null && payload.bus_capacity !== undefined
        ? Number(payload.bus_capacity)
        : null,
    available_capacity:
      payload.available_capacity !== null && payload.available_capacity !== undefined
        ? Number(payload.available_capacity)
        : null,
    route_start:
      payload.route_start &&
      Number.isFinite(Number(payload.route_start?.lat)) &&
      Number.isFinite(Number(payload.route_start?.lng))
        ? {
            label: payload.route_start?.label ?? 'Start',
            lat: Number(payload.route_start.lat),
            lng: Number(payload.route_start.lng),
          }
        : null,
    route_end:
      payload.route_end &&
      Number.isFinite(Number(payload.route_end?.lat)) &&
      Number.isFinite(Number(payload.route_end?.lng))
        ? {
            label: payload.route_end?.label ?? 'End',
            lat: Number(payload.route_end.lat),
            lng: Number(payload.route_end.lng),
          }
        : null,
    route_stops: Array.isArray(payload.route_stops)
      ? payload.route_stops
          .map((stop) => {
            const stopLat = stop?.lat ?? stop?.latitude ?? null;
            const stopLng = stop?.lng ?? stop?.longitude ?? null;

            if (!Number.isFinite(Number(stopLat)) || !Number.isFinite(Number(stopLng))) {
              return null;
            }

            return {
              id: stop?.id ?? null,
              address: stop?.address ?? 'Stop',
              stop_order:
                stop?.stop_order !== null && stop?.stop_order !== undefined
                  ? Number(stop.stop_order)
                  : 0,
              lat: Number(stopLat),
              lng: Number(stopLng),
            };
          })
          .filter(Boolean)
          .sort((a, b) => (a.stop_order ?? 0) - (b.stop_order ?? 0))
      : [],
    last_seen_ms: payload.last_seen_ms ?? null,
  };
}

function mergeBus(prevBus, incomingBus) {
  const now = Date.now();

  if (!prevBus) return { ...incomingBus, last_seen_ms: now };

  return {
    ...prevBus,
    ...incomingBus,
    lat: incomingBus.lat ?? prevBus.lat ?? null,
    lng: incomingBus.lng ?? prevBus.lng ?? null,
    speed: incomingBus.speed ?? prevBus.speed ?? null,
    last_seen_ms: now,
  };
}

function MapRefBinder({ mapRef }) {
  const map = useMap();

  useEffect(() => {
    mapRef.current = map;
    setTimeout(() => map.invalidateSize(), 50);
  }, [map, mapRef]);

  return null;
}

function MapTopLeftControls({ onHome, onReset, onFit }) {
  const map = useMap();

  useEffect(() => {
    const Control = L.Control.extend({
      options: { position: 'topleft' },

      onAdd() {
        const container = L.DomUtil.create('div', 'leaflet-bar map-custom-controls');

        const home = L.DomUtil.create('div', 'map-control-btn', container);
        home.title = 'Home';
        home.innerHTML = '🏠';

        const reset = L.DomUtil.create('div', 'map-control-btn', container);
        reset.title = 'Reset View';
        reset.innerHTML = '⟳';

        const fit = L.DomUtil.create('div', 'map-control-btn', container);
        fit.title = 'Fit to Buses';
        fit.innerHTML = '⤢';

        L.DomEvent.disableClickPropagation(container);

        [home, reset, fit].forEach((el) => {
          L.DomEvent.on(el, 'click', L.DomEvent.stop);
        });

        L.DomEvent.on(home, 'click', () => onHome?.());
        L.DomEvent.on(reset, 'click', () => onReset?.());
        L.DomEvent.on(fit, 'click', () => onFit?.());

        return container;
      },
    });

    const control = new Control();
    map.addControl(control);

    return () => {
      map.removeControl(control);
    };
  }, [map, onHome, onReset, onFit]);

  return null;
}

export default function LiveBusMap() {
  const [buses, setBuses] = useState([]);
  const [incidents, setIncidents] = useState([]);
  const [latestIncident, setLatestIncident] = useState(null);
  const [isIncidentModalOpen, setIsIncidentModalOpen] = useState(false);
  const [incidentSoundEnabled, setIncidentSoundEnabled] = useState(true);
  const [activeVideoCall, setActiveVideoCall] = useState(null);
  const [incomingVideoCall, setIncomingVideoCall] = useState(null);
  const [videoCallBusy, setVideoCallBusy] = useState(false);
  const [videoCallError, setVideoCallError] = useState('');
  const [videoCallStatus, setVideoCallStatus] = useState('Idle');
  const [isRecording, setIsRecording] = useState(false);
  const [recordingError, setRecordingError] = useState('');
  const [recordingInfo, setRecordingInfo] = useState('');

  const [query, setQuery] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');
  const [companyFilter, setCompanyFilter] = useState('all');
  const [routeFilter, setRouteFilter] = useState('all');

  const [followBusId, setFollowBusId] = useState(null);
  const FOLLOW_FLY_ZOOM = 17;
  const FOLLOW_INTERVAL_MS = 1500;

  const mapRef = useRef(null);
  const markerRefs = useRef(new Map());
  const didAutoFollowRef = useRef(false);
  const seenIncidentKeysRef = useRef(new Set());
  const hasHydratedIncidentsRef = useRef(false);
  const incidentAudioRef = useRef(null);
  const fetchInFlightRef = useRef(false);

  const localVideoRef = useRef(null);
  const remoteVideoRef = useRef(null);
  const peerConnectionRef = useRef(null);
  const localStreamRef = useRef(null);
  const remoteStreamRef = useRef(null);

  const recorderRef = useRef(null);
  const recordedChunksRef = useRef([]);
  const recordingCanvasRef = useRef(null);
  const recordingCanvasStreamRef = useRef(null);
  const recordingAnimationFrameRef = useRef(null);
  const recordingMixedStreamRef = useRef(null);
  const recordingAudioContextRef = useRef(null);
  const recordingAudioDestinationRef = useRef(null);

  const sentIceKeysRef = useRef(new Set());
  const appliedIceKeysRef = useRef(new Set());
  const queuedIceKeysRef = useRef(new Set());
  const pendingRemoteIceCandidatesRef = useRef([]);
  const answeredOfferKeyRef = useRef(null);
  const sentOfferKeyRef = useRef(null);
  const appliedAnswerKeyRef = useRef(null);
  const activeCallIdRef = useRef(null);
  const activeVideoCallRef = useRef(null);
  const incomingVideoCallRef = useRef(null);
  const syncingCallIdsRef = useRef(new Set());
  const connectTimeoutRef = useRef(null);
  const disconnectTimeoutRef = useRef(null);
  const restartInFlightRef = useRef(false);
  const iceRestartAttemptsRef = useRef(0);
  const fetchingIceRef = useRef(false);

  const OFFLINE_REMOVE_MS = 10 * 60 * 1000;
  const OFFLINE_LABEL_MS = 30 * 1000;
  const POLLING_INTERVAL_MS = 3000;
  const MISSING_FROM_FEED_GRACE_MS = 6000;

  const DEFAULT_CENTER = [14.3290, 121.0450];
  const DEFAULT_ZOOM = 13;

  const initialTripIdParam =
    typeof window !== 'undefined'
      ? Number(new URLSearchParams(window.location.search).get('trip'))
      : null;

  const role = String(window.userRole || '').toLowerCase();
  const companyId = window.companyId ?? null;
  const operatorUserId = Number(window.authUser?.id || 0) || null;
  const isAdminRole = ['super_admin', 'system_admin', 'administrator', 'admin'].includes(role);

  const liveBusesEndpoint =
    !isAdminRole && (role === 'company_admin' || companyId)
      ? '/company/api/live-buses'
      : '/admin/api/live-buses';

  const normalizeVideoCall = (payload) => {
    if (!payload) return null;

    const source = payload.video_call ?? payload;
    const id = source.id ?? payload.call_id ?? null;

    if (!Number.isFinite(Number(id))) return null;

    return {
      id: Number(id),
      caller_type: source.caller_type ?? null,
      caller_id:
        source.caller_id !== null && source.caller_id !== undefined
          ? Number(source.caller_id)
          : null,
      callee_type: source.callee_type ?? null,
      callee_id:
        source.callee_id !== null && source.callee_id !== undefined
          ? Number(source.callee_id)
          : null,
      status: source.status ?? 'ringing',
      offer: source.offer ?? null,
      answer: source.answer ?? null,
      has_offer: Boolean(source.has_offer),
      has_answer: Boolean(source.has_answer),
      updated_at: source.updated_at ?? null,
    };
  };

  const iceConfiguration = useMemo(() => buildWebRtcIceConfiguration(), []);
  const webRtcRuntimePolicy = useMemo(() => buildWebRtcRuntimePolicy(), []);

  const clearConnectionTimers = () => {
    if (connectTimeoutRef.current) {
      clearTimeout(connectTimeoutRef.current);
      connectTimeoutRef.current = null;
    }

    if (disconnectTimeoutRef.current) {
      clearTimeout(disconnectTimeoutRef.current);
      disconnectTimeoutRef.current = null;
    }
  };

  const scheduleConnectTimeout = () => {
    if (connectTimeoutRef.current) return;

    connectTimeoutRef.current = setTimeout(() => {
      connectTimeoutRef.current = null;
      void restartIceConnection('Connection timed out while negotiating media.');
    }, webRtcRuntimePolicy.connectTimeoutMs);
  };

  const scheduleDisconnectedRecovery = () => {
    if (disconnectTimeoutRef.current) return;

    disconnectTimeoutRef.current = setTimeout(() => {
      disconnectTimeoutRef.current = null;
      void restartIceConnection('Connection dropped. Attempting recovery...');
    }, webRtcRuntimePolicy.disconnectGraceMs);
  };

  async function restartIceConnection(reason) {
    const peerConnection = peerConnectionRef.current;
    const activeVideoCall = activeVideoCallRef.current;

    if (!peerConnection || !activeVideoCall?.id) return false;
    if (peerConnection.signalingState !== 'stable') return false;
    if (!peerConnection.localDescription || !peerConnection.remoteDescription) return false;
    if (restartInFlightRef.current) return false;

    const isCaller =
      activeVideoCall.caller_type === 'user' &&
      Number(activeVideoCall.caller_id) === Number(operatorUserId);

    if (!isCaller) {
      setVideoCallStatus('Connection interrupted. Waiting for the caller to retry...');
      return false;
    }

    if (iceRestartAttemptsRef.current >= webRtcRuntimePolicy.maxIceRestartAttempts) {
      setVideoCallError('Call connection failed after multiple retry attempts. Please start the call again.');
      setVideoCallStatus('Connection failed.');
      return false;
    }

    restartInFlightRef.current = true;
    iceRestartAttemptsRef.current += 1;
    clearConnectionTimers();
    setVideoCallError('');
    setVideoCallStatus(
      `${reason} Retrying (${iceRestartAttemptsRef.current}/${webRtcRuntimePolicy.maxIceRestartAttempts})...`
    );

    try {
      const restartOffer = await peerConnection.createOffer({
        iceRestart: true,
        offerToReceiveAudio: true,
        offerToReceiveVideo: true,
      });

      await peerConnection.setLocalDescription(restartOffer);

      await axios.post(`/api/video-calls/${activeVideoCall.id}/offer`, {
        offer: {
          type: restartOffer.type,
          sdp: restartOffer.sdp,
        },
      });

      scheduleConnectTimeout();
      return true;
    } catch (error) {
      console.error('[LiveBusMap] Failed to restart ICE', error);
      setVideoCallError(describeError(error, 'Could not recover the WebRTC connection.'));
      return false;
    } finally {
      restartInFlightRef.current = false;
    }
  }

  const describeError = (error, fallback) => {
    const apiMessage = error?.response?.data?.message;
    const apiErrors = error?.response?.data?.errors;
    const rtcMessage = error?.message;

    if (apiMessage && typeof apiMessage === 'string') return apiMessage;

    if (apiErrors && typeof apiErrors === 'object') {
      const firstKey = Object.keys(apiErrors)[0];
      const firstValue = firstKey ? apiErrors[firstKey] : null;
      if (Array.isArray(firstValue) && firstValue.length > 0) {
        return String(firstValue[0]);
      }
    }

    if (rtcMessage && typeof rtcMessage === 'string') return rtcMessage;

    return fallback;
  };

  const formatSince = (ms) => {
    if (ms == null) return '—';
    const s = Math.max(0, Math.floor(ms / 1000));
    const m = Math.floor(s / 60);
    const r = s % 60;
    if (m <= 0) return `${r}s`;
    return `${m}m ${r}s`;
  };

  const formatUpdatedAt = (value) => {
    if (!value) return '—';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return String(value);
    return date.toLocaleString();
  };

  const getIsOffline = (bus) => {
    const now = Date.now();
    const msSince = bus?.last_seen_ms ? now - bus.last_seen_ms : null;
    return msSince !== null && msSince > OFFLINE_LABEL_MS;
  };

  const unique = (arr) => Array.from(new Set(arr.filter(Boolean)));
  const companyOptions = useMemo(() => unique(buses.map((b) => b.company)), [buses]);
  const routeOptions = useMemo(() => unique(buses.map((b) => b.route)), [buses]);

  const sanitizeSdp = (sdp) => {
    if (typeof sdp !== 'string') return '';
    return sdp
      .replace(/\r\n/g, '\n')
      .replace(/\r/g, '\n')
      .split('\n')
      .map((line) => line.trim())
      .filter(Boolean)
      .join('\r\n')
      .concat('\r\n');
  };

  const stopCompositeRendering = () => {
    if (recordingAnimationFrameRef.current) {
      cancelAnimationFrame(recordingAnimationFrameRef.current);
      recordingAnimationFrameRef.current = null;
    }
  };

  const cleanupRecordingInfrastructure = () => {
    stopCompositeRendering();

    if (recordingCanvasStreamRef.current) {
      recordingCanvasStreamRef.current.getTracks().forEach((track) => track.stop());
      recordingCanvasStreamRef.current = null;
    }

    if (recordingMixedStreamRef.current) {
      recordingMixedStreamRef.current.getTracks().forEach((track) => track.stop());
      recordingMixedStreamRef.current = null;
    }

    if (recordingAudioContextRef.current && recordingAudioContextRef.current.state !== 'closed') {
      recordingAudioContextRef.current.close().catch(() => {});
    }
    recordingAudioContextRef.current = null;
    recordingAudioDestinationRef.current = null;
    recordingCanvasRef.current = null;
  };

  const playIncidentSound = () => {
    if (!incidentSoundEnabled) return;
    const audio = incidentAudioRef.current;
    if (!audio) return;

    audio.currentTime = 0;
    audio.play().catch(() => {});
  };

  const syncVideoCall = async (callId) => {
    const normalizedCallId = Number(callId);
    if (!Number.isFinite(normalizedCallId)) return null;
    if (syncingCallIdsRef.current.has(normalizedCallId)) return null;

    syncingCallIdsRef.current.add(normalizedCallId);

    try {
      const response = await axios.get(`/api/video-calls/${normalizedCallId}`);
      const videoCall = normalizeVideoCall(response.data?.data?.video_call);
      if (!videoCall) return null;
      activeCallIdRef.current = videoCall.id;
      setActiveVideoCall(videoCall);
      setVideoCallError('');
      return videoCall;
    } catch (error) {
      console.error('[LiveBusMap] Failed to sync video call', error);
      setVideoCallError('Could not refresh the video call status.');
      return null;
    } finally {
      syncingCallIdsRef.current.delete(normalizedCallId);
    }
  };

  const ensureLocalMedia = async () => {
    if (localStreamRef.current) return localStreamRef.current;

    const stream = await navigator.mediaDevices.getUserMedia({
      audio: true,
      video: {
        facingMode: 'user',
        width: { ideal: 640 },
        height: { ideal: 480 },
      },
    });

    localStreamRef.current = stream;
    if (localVideoRef.current) {
      localVideoRef.current.srcObject = stream;
    }

    return stream;
  };

  const queueRemoteIceCandidate = (candidate) => {
    const rawCandidate = candidate?.candidate ?? '';
    if (!rawCandidate.trim()) return;

    const iceKey = `${rawCandidate}|${candidate?.sdpMid ?? ''}|${candidate?.sdpMLineIndex ?? ''}`;
    if (appliedIceKeysRef.current.has(iceKey) || queuedIceKeysRef.current.has(iceKey)) return;

    pendingRemoteIceCandidatesRef.current.push({
      iceKey,
      candidate: rawCandidate,
      sdpMid: candidate?.sdpMid ?? null,
      sdpMLineIndex:
        candidate?.sdpMLineIndex !== null && candidate?.sdpMLineIndex !== undefined
          ? Number(candidate.sdpMLineIndex)
          : null,
    });
    queuedIceKeysRef.current.add(iceKey);
  };

  const closeWebRtcSession = () => {
    clearConnectionTimers();
    restartInFlightRef.current = false;
    iceRestartAttemptsRef.current = 0;
    const peerConnection = peerConnectionRef.current;
    if (peerConnection) {
      peerConnection.onicecandidate = null;
      peerConnection.ontrack = null;
      peerConnection.onconnectionstatechange = null;
      peerConnection.oniceconnectionstatechange = null;
      peerConnection.close();
    }

    peerConnectionRef.current = null;
    sentIceKeysRef.current.clear();
    appliedIceKeysRef.current.clear();
    queuedIceKeysRef.current.clear();
    pendingRemoteIceCandidatesRef.current = [];
    answeredOfferKeyRef.current = null;
    sentOfferKeyRef.current = null;
    appliedAnswerKeyRef.current = null;

    const remoteStream = remoteStreamRef.current;
    if (remoteStream) {
      remoteStream.getTracks().forEach((track) => remoteStream.removeTrack(track));
    }
    remoteStreamRef.current = null;

    const localStream = localStreamRef.current;
    if (localStream) {
      localStream.getTracks().forEach((track) => track.stop());
    }
    localStreamRef.current = null;

    if (localVideoRef.current) localVideoRef.current.srcObject = null;
    if (remoteVideoRef.current) remoteVideoRef.current.srcObject = null;

    cleanupRecordingInfrastructure();
  };

  const flushPendingRemoteIceCandidates = async () => {
    const peerConnection = peerConnectionRef.current;
    if (!peerConnection || !peerConnection.remoteDescription) return;

    while (pendingRemoteIceCandidatesRef.current.length > 0) {
      const queuedCandidate = pendingRemoteIceCandidatesRef.current.shift();
      if (!queuedCandidate) continue;

      queuedIceKeysRef.current.delete(queuedCandidate.iceKey);
      if (appliedIceKeysRef.current.has(queuedCandidate.iceKey)) continue;

      try {
        await peerConnection.addIceCandidate({
          candidate: queuedCandidate.candidate,
          sdpMid: queuedCandidate.sdpMid,
          sdpMLineIndex: queuedCandidate.sdpMLineIndex,
        });

        appliedIceKeysRef.current.add(queuedCandidate.iceKey);
        console.log('[LiveBusMap] addIceCandidate success', {
          candidate: queuedCandidate.candidate,
          sdpMid: queuedCandidate.sdpMid,
          sdpMLineIndex: queuedCandidate.sdpMLineIndex,
        });
      } catch (error) {
        console.error('[LiveBusMap] Failed to apply queued ICE candidate', error);
      }
    }
  };

  const sendLocalIceCandidate = async (candidate) => {
    const callId = activeCallIdRef.current;
    if (!callId || !operatorUserId) return;

    const rawCandidate = candidate?.candidate ?? '';
    if (!rawCandidate.trim()) return;

    const iceKey = `${rawCandidate}|${candidate.sdpMid ?? ''}|${candidate.sdpMLineIndex ?? ''}`;
    if (sentIceKeysRef.current.has(iceKey)) return;
    sentIceKeysRef.current.add(iceKey);

    try {
      await axios.post(`/api/video-calls/${callId}/ice`, {
        sender_type: 'user',
        sender_id: operatorUserId,
        candidate: {
          candidate: rawCandidate,
          sdpMid: candidate.sdpMid ?? null,
          sdpMLineIndex: candidate.sdpMLineIndex ?? null,
        },
      });
      console.log('[LiveBusMap] ICE candidate sent', {
        callId,
        candidate: rawCandidate,
        sdpMid: candidate.sdpMid ?? null,
        sdpMLineIndex: candidate.sdpMLineIndex ?? null,
      });
    } catch (error) {
      console.error('[LiveBusMap] Failed to send ICE candidate', error);
    }
  };

  const ensurePeerConnection = async () => {
    if (peerConnectionRef.current) return peerConnectionRef.current;

    const localStream = await ensureLocalMedia();
    const peerConnection = new RTCPeerConnection(iceConfiguration);
    peerConnectionRef.current = peerConnection;

    localStream.getTracks().forEach((track) => {
      peerConnection.addTrack(track, localStream);
    });

    const remoteStream = new MediaStream();
    remoteStreamRef.current = remoteStream;

    if (remoteVideoRef.current) {
      remoteVideoRef.current.srcObject = remoteStream;
    }

    peerConnection.ontrack = (event) => {
      console.log('[LiveBusMap] Remote track received', {
        streams: event.streams.map((stream) => ({
          id: stream.id,
          tracks: stream.getTracks().map((track) => ({
            id: track.id,
            kind: track.kind,
            enabled: track.enabled,
            readyState: track.readyState,
          })),
        })),
      });
      event.streams.forEach((stream) => {
        stream.getTracks().forEach((track) => {
          const alreadyExists = remoteStream
            .getTracks()
            .some((existingTrack) => existingTrack.id === track.id);

          if (!alreadyExists) {
            remoteStream.addTrack(track);
          }
        });
      });

      if (remoteVideoRef.current) {
        remoteVideoRef.current.srcObject = remoteStream;
      }

      clearConnectionTimers();
      iceRestartAttemptsRef.current = 0;
      setVideoCallStatus('Remote video connected.');
    };

    peerConnection.onicecandidate = (event) => {
      console.log('[LiveBusMap] Local ICE candidate', event.candidate
        ? {
            candidate: event.candidate.candidate,
            sdpMid: event.candidate.sdpMid,
            sdpMLineIndex: event.candidate.sdpMLineIndex,
            type: event.candidate.type ?? null,
            protocol: event.candidate.protocol ?? null,
            address: event.candidate.address ?? null,
            port: event.candidate.port ?? null,
          }
        : 'ICE gathering complete');
      if (event.candidate) {
        void sendLocalIceCandidate(event.candidate);
      }
    };

    peerConnection.onconnectionstatechange = () => {
      const state = peerConnection.connectionState;
      if (!state) return;
      console.log('[LiveBusMap] Connection state changed', {
        connectionState: state,
        iceConnectionState: peerConnection.iceConnectionState,
        iceGatheringState: peerConnection.iceGatheringState,
        signalingState: peerConnection.signalingState,
      });

      setVideoCallStatus(`Connection: ${state}`);

      if (state === 'connected') {
        clearConnectionTimers();
        iceRestartAttemptsRef.current = 0;
      } else if (state === 'connecting') {
        scheduleConnectTimeout();
      } else if (state === 'disconnected') {
        scheduleDisconnectedRecovery();
      } else if (state === 'failed') {
        void restartIceConnection('Connection failed.');
      } else if (state === 'closed') {
        clearConnectionTimers();
      }
    };

    peerConnection.oniceconnectionstatechange = () => {
      const state = peerConnection.iceConnectionState;
      if (!state) return;
      console.log('[LiveBusMap] ICE connection state changed', {
        iceConnectionState: state,
        connectionState: peerConnection.connectionState,
        iceGatheringState: peerConnection.iceGatheringState,
        signalingState: peerConnection.signalingState,
      });

      setVideoCallStatus(`ICE: ${state}`);

      if (state === 'connected' || state === 'completed') {
        clearConnectionTimers();
        iceRestartAttemptsRef.current = 0;
      } else if (state === 'checking') {
        scheduleConnectTimeout();
      } else if (state === 'disconnected') {
        scheduleDisconnectedRecovery();
      } else if (state === 'failed') {
        void restartIceConnection('ICE negotiation failed.');
      } else if (state === 'closed') {
        clearConnectionTimers();
      }
    };

    scheduleConnectTimeout();

    return peerConnection;
  };

  const applyRemoteIceCandidateFromPayload = async (payload) => {
    const record = payload?.candidate;
    if (!record) return;
    if (record?.sender_type === 'user' && Number(record?.sender_id) === operatorUserId) return;

    const candidate = record?.candidate ?? {};
    const rawCandidate = candidate?.candidate ?? '';
    if (!rawCandidate.trim()) return;
    console.log('[LiveBusMap] Remote ICE candidate received', {
      candidate: rawCandidate,
      sdpMid: candidate?.sdpMid ?? null,
      sdpMLineIndex: candidate?.sdpMLineIndex ?? null,
      senderType: record?.sender_type ?? null,
      senderId: record?.sender_id ?? null,
    });

    const peerConnection = peerConnectionRef.current;
    queueRemoteIceCandidate(candidate);

    if (!peerConnection || !peerConnection.remoteDescription) return;

    await flushPendingRemoteIceCandidates();
  };

  const fetchRemoteIceCandidates = async (callId) => {
    const normalizedCallId = Number(callId);
    if (!Number.isFinite(normalizedCallId) || fetchingIceRef.current) return;

    fetchingIceRef.current = true;

    try {
      const response = await axios.get(`/api/video-calls/${normalizedCallId}/ice`);
      const candidates = Array.isArray(response.data?.data?.ice_candidates)
        ? response.data.data.ice_candidates
        : [];

      for (const record of candidates) {
        await applyRemoteIceCandidateFromPayload({ candidate: record });
      }
    } catch (error) {
      console.error('[LiveBusMap] Failed to fetch remote ICE candidates', error);
    } finally {
      fetchingIceRef.current = false;
    }
  };

  const answerVideoCall = async (videoCall) => {
    if (!videoCall?.id || !videoCall?.offer?.sdp) return;

    const offerKey = `${videoCall.id}|${videoCall.offer.type}|${videoCall.offer.sdp}`;
    if (answeredOfferKeyRef.current === offerKey) {
      await flushPendingRemoteIceCandidates();
      return;
    }

    try {
      setVideoCallBusy(true);
      setVideoCallError('');
      setVideoCallStatus('Call accepted. Preparing local media to answer...');

      const peerConnection = await ensurePeerConnection();
      const remoteOffer = new RTCSessionDescription({
        type: videoCall.offer.type,
        sdp: sanitizeSdp(videoCall.offer.sdp),
      });

      if (!peerConnection.remoteDescription || peerConnection.remoteDescription.sdp !== remoteOffer.sdp) {
        setVideoCallStatus('Offer received. Applying remote offer...');
        await peerConnection.setRemoteDescription(remoteOffer);
        console.log('[LiveBusMap] setRemoteDescription success', {
          kind: 'offer',
          callId: videoCall.id,
        });
      }

      await flushPendingRemoteIceCandidates();

      setVideoCallStatus('Creating local answer...');
      const answer = await peerConnection.createAnswer();
      await peerConnection.setLocalDescription(answer);

      await axios.post(`/api/video-calls/${videoCall.id}/answer`, {
        answer: {
          type: answer.type,
          sdp: answer.sdp,
        },
      });
      console.log('[LiveBusMap] answer sent', {
        callId: videoCall.id,
        type: answer.type,
        hasSdp: Boolean(answer.sdp),
      });

      answeredOfferKeyRef.current = offerKey;
      setVideoCallStatus('Answer sent. Connecting media...');
      await flushPendingRemoteIceCandidates();
      scheduleConnectTimeout();
    } catch (error) {
      console.error('[LiveBusMap] Failed to answer WebRTC call', error);
      setVideoCallError(describeError(error, 'Could not establish the WebRTC media session.'));
    } finally {
      setVideoCallBusy(false);
    }
  };

  const sendOfferForCaller = async (videoCall) => {
    if (!videoCall?.id) return;
    if (videoCall.caller_type !== 'user' || Number(videoCall.caller_id) !== Number(operatorUserId)) return;
    if (videoCall.status !== 'accepted' && videoCall.status !== 'connected') return;
    if (videoCall.has_offer && videoCall.offer?.sdp) return;

    const offerStateKey = `${videoCall.id}|${videoCall.status}|${videoCall.updated_at ?? 'no-update'}`;
    if (sentOfferKeyRef.current === offerStateKey) return;

    try {
      setVideoCallBusy(true);
      setVideoCallError('');
      setVideoCallStatus('Preparing local media...');

      const peerConnection = await ensurePeerConnection();

      if (peerConnection.signalingState !== 'stable') {
        console.warn('[LiveBusMap] Offer skipped because signaling is not stable', {
          callId: videoCall.id,
          signalingState: peerConnection.signalingState,
        });
        return;
      }

      setVideoCallStatus('Creating local offer...');
      const offer = await peerConnection.createOffer({
        offerToReceiveAudio: true,
        offerToReceiveVideo: true,
      });

      await peerConnection.setLocalDescription(offer);

      await axios.post(`/api/video-calls/${videoCall.id}/offer`, {
        offer: {
          type: offer.type,
          sdp: offer.sdp,
        },
      });
      console.log('[LiveBusMap] offer sent', {
        callId: videoCall.id,
        type: offer.type,
        hasSdp: Boolean(offer.sdp),
      });

      sentOfferKeyRef.current = offerStateKey;
      setVideoCallStatus('Offer sent. Waiting for answer...');
      await flushPendingRemoteIceCandidates();
      scheduleConnectTimeout();
    } catch (error) {
      console.error('[LiveBusMap] Failed to create/send WebRTC offer', error);
      setVideoCallError(describeError(error, 'Could not start the WebRTC media session.'));
    } finally {
      setVideoCallBusy(false);
    }
  };

  const prewarmOutgoingWebRtcSession = async (videoCall) => {
    if (!videoCall?.id) return;
    if (videoCall.caller_type !== 'user' || Number(videoCall.caller_id) !== Number(operatorUserId)) return;
    if (peerConnectionRef.current && localStreamRef.current) return;

    try {
      await ensurePeerConnection();
    } catch (error) {
      console.error('[LiveBusMap] Failed to prewarm outgoing WebRTC session', error);
    }
  };

  const applyRemoteAnswerForCaller = async (videoCall) => {
    if (!videoCall?.id || !videoCall?.answer?.sdp) return;
    if (videoCall.caller_type !== 'user' || Number(videoCall.caller_id) !== Number(operatorUserId)) return;

    const answerKey = `${videoCall.id}|${videoCall.answer.type}|${videoCall.answer.sdp}`;
    if (appliedAnswerKeyRef.current === answerKey) {
      await flushPendingRemoteIceCandidates();
      return;
    }

    try {
      const peerConnection = await ensurePeerConnection();
      const remoteAnswer = new RTCSessionDescription({
        type: videoCall.answer.type,
        sdp: sanitizeSdp(videoCall.answer.sdp),
      });

      const hasMatchingRemoteAnswer =
        peerConnection.remoteDescription?.type === 'answer' &&
        peerConnection.remoteDescription.sdp === remoteAnswer.sdp;
      const canApplyRemoteAnswer =
        peerConnection.signalingState === 'have-local-offer' ||
        peerConnection.signalingState === 'have-remote-pranswer';

      if (!hasMatchingRemoteAnswer) {
        if (!canApplyRemoteAnswer) {
          console.warn('[LiveBusMap] Skipping remote answer in unexpected signaling state', {
            callId: videoCall.id,
            signalingState: peerConnection.signalingState,
            remoteDescriptionType: peerConnection.remoteDescription?.type ?? null,
          });
          return;
        }

        setVideoCallStatus('Answer received. Applying remote answer...');
        await peerConnection.setRemoteDescription(remoteAnswer);
        console.log('[LiveBusMap] setRemoteDescription success', {
          kind: 'answer',
          callId: videoCall.id,
        });
      }

      setVideoCallStatus('Answer received. Connecting media...');
      console.log('[LiveBusMap] answer received', {
        callId: videoCall.id,
        type: videoCall.answer.type,
        hasSdp: Boolean(videoCall.answer?.sdp),
      });
      appliedAnswerKeyRef.current = answerKey;
      await flushPendingRemoteIceCandidates();
      scheduleConnectTimeout();
    } catch (error) {
      console.error('[LiveBusMap] Failed to apply remote WebRTC answer', error);
      setVideoCallError(describeError(error, 'Could not apply the remote WebRTC answer.'));
    }
  };

const buildCompositeRecordingStream = async () => {
  const localStream = localStreamRef.current;
  const remoteStream = remoteStreamRef.current;
  const localVideo = localVideoRef.current;
  const remoteVideo = remoteVideoRef.current;

  if (!localStream || !remoteStream) {
    throw new Error('Both local and remote streams must be connected before recording.');
  }

  const localVideoTrack = localStream.getVideoTracks()[0];
  const remoteVideoTrack = remoteStream.getVideoTracks()[0];
  const localAudioTrack = localStream.getAudioTracks()[0];
  const remoteAudioTrack = remoteStream.getAudioTracks()[0];

  if (!localVideoTrack || !remoteVideoTrack) {
    throw new Error('Both participants must have active video before recording.');
  }

  if (!localVideo || !remoteVideo) {
    throw new Error('Video elements are not ready.');
  }

  const canvas = document.createElement('canvas');
  canvas.width = 1280;
  canvas.height = 720;
  recordingCanvasRef.current = canvas;

  const context = canvas.getContext('2d', { alpha: false });
  if (!context) {
    throw new Error('Unable to create canvas context for recording.');
  }

  const drawVideoContained = (video, x, y, width, height, bgColor = '#000') => {
    context.fillStyle = bgColor;
    context.fillRect(x, y, width, height);

    const videoWidth = video.videoWidth || 16;
    const videoHeight = video.videoHeight || 9;

    const videoAspect = videoWidth / videoHeight;
    const boxAspect = width / height;

    let drawWidth = width;
    let drawHeight = height;
    let drawX = x;
    let drawY = y;

    if (videoAspect > boxAspect) {
      drawWidth = width;
      drawHeight = width / videoAspect;
      drawY = y + (height - drawHeight) / 2;
    } else {
      drawHeight = height;
      drawWidth = height * videoAspect;
      drawX = x + (width - drawWidth) / 2;
    }

    try {
      context.drawImage(video, drawX, drawY, drawWidth, drawHeight);
    } catch (_) {}
  };

  const drawFrame = () => {
    context.fillStyle = '#05070a';
    context.fillRect(0, 0, canvas.width, canvas.height);

    const padding = 24;
    const headerHeight = 56;
    const contentY = padding + headerHeight;
    const panelGap = 24;
    const panelWidth = (canvas.width - padding * 2 - panelGap) / 2;
    const panelHeight = canvas.height - contentY - padding;

    context.fillStyle = 'rgba(220, 38, 38, 0.95)';
    context.fillRect(padding, padding, 150, 34);

    context.fillStyle = '#ffffff';
    context.font = 'bold 18px Arial';
    context.fillText('REC • LIVE', padding + 14, padding + 23);

    context.fillStyle = '#ffffff';
    context.font = 'bold 22px Arial';
    context.fillText(`Emergency Call #${activeCallIdRef.current ?? ''}`, 200, padding + 24);

    // Left panel - Citizen / Remote
    context.fillStyle = '#0f172a';
    context.fillRect(padding, contentY, panelWidth, panelHeight);
    drawVideoContained(remoteVideo, padding, contentY, panelWidth, panelHeight, '#0f172a');

    context.fillStyle = 'rgba(15, 23, 42, 0.82)';
    context.fillRect(padding, contentY + panelHeight - 42, panelWidth, 42);
    context.fillStyle = '#ffffff';
    context.font = 'bold 18px Arial';
    context.fillText('Citizen / Remote', padding + 14, contentY + panelHeight - 15);

    // Right panel - Operator
    const rightX = padding + panelWidth + panelGap;
    context.fillStyle = '#1e293b';
    context.fillRect(rightX, contentY, panelWidth, panelHeight);
    drawVideoContained(localVideo, rightX, contentY, panelWidth, panelHeight, '#1e293b');

    context.fillStyle = 'rgba(15, 23, 42, 0.82)';
    context.fillRect(rightX, contentY + panelHeight - 42, panelWidth, 42);
    context.fillStyle = '#ffffff';
    context.font = 'bold 18px Arial';
    context.fillText('Operator', rightX + 14, contentY + panelHeight - 15);

    recordingAnimationFrameRef.current = requestAnimationFrame(drawFrame);
  };

  drawFrame();

  const canvasStream = canvas.captureStream(30);
  recordingCanvasStreamRef.current = canvasStream;

  const AudioContextClass = window.AudioContext || window.webkitAudioContext;
  if (!AudioContextClass) {
    throw new Error('AudioContext is not supported in this browser.');
  }

  const audioContext = new AudioContextClass();
  recordingAudioContextRef.current = audioContext;

  const destination = audioContext.createMediaStreamDestination();
  recordingAudioDestinationRef.current = destination;

  if (localAudioTrack) {
    const localAudioStream = new MediaStream([localAudioTrack]);
    const localSource = audioContext.createMediaStreamSource(localAudioStream);
    const localGain = audioContext.createGain();
    localGain.gain.value = 1.0;
    localSource.connect(localGain).connect(destination);
  }

  if (remoteAudioTrack) {
    const remoteAudioStream = new MediaStream([remoteAudioTrack]);
    const remoteSource = audioContext.createMediaStreamSource(remoteAudioStream);
    const remoteGain = audioContext.createGain();
    remoteGain.gain.value = 1.0;
    remoteSource.connect(remoteGain).connect(destination);
  }

  const finalStream = new MediaStream([
    ...canvasStream.getVideoTracks(),
    ...destination.stream.getAudioTracks(),
  ]);

  recordingMixedStreamRef.current = finalStream;
  return finalStream;
};

  const uploadRecording = async (blob, callId) => {
    if (!callId) {
      throw new Error('Missing call ID for recording upload.');
    }

    const formData = new FormData();
    formData.append('recording', blob, `call-${callId}.webm`);
    formData.append('recorded_by_type', 'user');
    formData.append('recorded_by_id', operatorUserId ? String(operatorUserId) : '');

    try {
      const response = await axios.post(`/api/video-calls/${callId}/recordings`, formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
        timeout: 1000 * 60 * 10, // 10 minutes for large files
      });

      const url = response.data?.data?.recording?.url ?? '';
      setRecordingInfo(url ? `Recording saved: ${url}` : 'Recording saved.');
      return response.data;
    } catch (error) {
      console.error('[LiveBusMap] Recording upload failed', error);

      const apiMessage = error?.response?.data?.message;
      const validationErrors = error?.response?.data?.errors;
      const status = error?.response?.status;

      let message = 'Recording upload failed.';

      if (apiMessage) {
        message = `${message} ${apiMessage}`;
      } else if (validationErrors) {
        const firstKey = Object.keys(validationErrors)[0];
        const firstValue = firstKey ? validationErrors[firstKey] : null;
        if (Array.isArray(firstValue) && firstValue.length > 0) {
          message = `${message} ${firstValue[0]}`;
        }
      } else if (status) {
        message = `${message} HTTP ${status}.`;
      } else if (error?.message) {
        message = `${message} ${error.message}`;
      }

      throw new Error(message);
    }
  };

  const stopRecording = async () => {
    if (recorderRef.current && recorderRef.current.state !== 'inactive') {
      recorderRef.current.stop();
    }
  };

  const startRecording = async () => {
    if (isRecording) return;

    setRecordingError('');
    setRecordingInfo('');

    try {
      if (!activeVideoCall?.id) {
        setRecordingError('No active call to record.');
        return;
      }

      if (videoCallBusy) {
        setRecordingError('Please wait until call setup finishes.');
        return;
      }

      const stream = await buildCompositeRecordingStream();

      const mimeTypeCandidates = [
        'video/webm;codecs=vp9,opus',
        'video/webm;codecs=vp8,opus',
        'video/webm',
      ];

      const selectedMimeType =
        mimeTypeCandidates.find((mimeType) => MediaRecorder.isTypeSupported(mimeType)) || '';

      if (!selectedMimeType) {
        throw new Error('This browser does not support WebM recording.');
      }

      const recorder = new MediaRecorder(stream, {
        mimeType: selectedMimeType,
        videoBitsPerSecond: 900000,
        audioBitsPerSecond: 96000,
      });

      recorderRef.current = recorder;
      recordedChunksRef.current = [];

      recorder.ondataavailable = (event) => {
        if (event.data && event.data.size > 0) {
          recordedChunksRef.current.push(event.data);
        }
      };

      recorder.onerror = (event) => {
        console.error('[LiveBusMap] Recorder error', event);
        setRecordingError('Recording failed while running.');
      };

      recorder.onstop = async () => {
        try {
          const finishedCallId = activeCallIdRef.current;
          const blob = new Blob(recordedChunksRef.current, { type: selectedMimeType });
          recordedChunksRef.current = [];
          recorderRef.current = null;
          setIsRecording(false);
          cleanupRecordingInfrastructure();

          if (!blob.size) {
            setRecordingError('Recording finished but no video data was produced.');
            return;
          }

          setRecordingInfo('Uploading recording...');
          await uploadRecording(blob, finishedCallId);
        } catch (error) {
          console.error('[LiveBusMap] Failed after recording stop', error);
          setRecordingError(error?.message || 'Recording was created but upload failed.');
          setRecordingInfo('');
        }
      };

      recorder.start(1000);
      setIsRecording(true);
      setRecordingInfo('Recording started. Dual video and mixed audio are now being captured.');
    } catch (error) {
      console.error('[LiveBusMap] Failed to start recording', error);
      cleanupRecordingInfrastructure();
      setRecordingError(describeError(error, 'Could not start recording.'));
    }
  };

  const startVideoCall = async (bus) => {
    if (!operatorUserId) {
      setVideoCallError('No operator user is available for this web session.');
      return;
    }

    setVideoCallBusy(true);
    setVideoCallError('');

    try {
      const response = await axios.post('/api/video-calls/start', {
        caller_type: 'user',
        caller_id: operatorUserId,
        callee_type: 'bus',
        callee_id: bus.bus_id,
      });

      const videoCall = normalizeVideoCall(response.data?.data?.video_call);
      activeCallIdRef.current = videoCall?.id ?? null;
      setActiveVideoCall(videoCall);
      setIncomingVideoCall(null);
      setVideoCallStatus(`Calling Bus #${bus.bus_id}...`);
    } catch (error) {
      console.error('[LiveBusMap] Failed to start video call', error);
      setVideoCallError('Could not start the video call.');
    } finally {
      setVideoCallBusy(false);
    }
  };

  const acceptIncomingVideoCall = async () => {
    if (!incomingVideoCall?.id) return;

    setVideoCallBusy(true);
    setVideoCallError('');

    try {
      const response = await axios.post(`/api/video-calls/${incomingVideoCall.id}/accept`);
      const videoCall = normalizeVideoCall(response.data?.data?.video_call) ?? incomingVideoCall;
      activeCallIdRef.current = videoCall.id;
      setActiveVideoCall(videoCall);
      setIncomingVideoCall(null);
      setVideoCallStatus('Call accepted. Waiting for the remote offer...');
    } catch (error) {
      console.error('[LiveBusMap] Failed to accept video call', error);
      setVideoCallError('Could not accept the incoming video call.');
    } finally {
      setVideoCallBusy(false);
    }
  };

  const rejectIncomingVideoCall = async () => {
    if (!incomingVideoCall?.id) return;

    setVideoCallBusy(true);
    setVideoCallError('');

    try {
      await axios.post(`/api/video-calls/${incomingVideoCall.id}/reject`);
      setIncomingVideoCall(null);
      setActiveVideoCall(null);
      activeCallIdRef.current = null;
      setVideoCallStatus('Call rejected.');
    } catch (error) {
      console.error('[LiveBusMap] Failed to reject video call', error);
      setVideoCallError('Could not reject the incoming video call.');
    } finally {
      setVideoCallBusy(false);
    }
  };

  const endVideoCall = async () => {
    if (!activeVideoCall?.id) return;

    setVideoCallBusy(true);
    setVideoCallError('');

    try {
      if (isRecording) {
        await stopRecording();
      }

      await axios.post(`/api/video-calls/${activeVideoCall.id}/end`);
      setActiveVideoCall(null);
      setIncomingVideoCall(null);
      activeCallIdRef.current = null;
      setVideoCallStatus('Call ended.');
    } catch (error) {
      console.error('[LiveBusMap] Failed to end video call', error);
      setVideoCallError('Could not end the video call.');
    } finally {
      setVideoCallBusy(false);
    }
  };

  const fetchLiveBuses = async () => {
    if (fetchInFlightRef.current) return;
    fetchInFlightRef.current = true;

    try {
      const res = await axios.get(liveBusesEndpoint);
      const list = res.data?.data ?? [];
      const normalized = list.map(normalizeBusPayload).filter(Boolean);
      const incidentList = Array.isArray(res.data?.incidents) ? res.data.incidents : [];

      setIncidents(incidentList);

      if (!hasHydratedIncidentsRef.current) {
        incidentList.forEach((incident) => {
          const key = `${incident.trip_id}-${incident.reported_at}`;
          seenIncidentKeysRef.current.add(key);
        });
        hasHydratedIncidentsRef.current = true;
      } else {
        let detectedIncident = null;
        incidentList.forEach((incident) => {
          const key = `${incident.trip_id}-${incident.reported_at}`;
          if (!seenIncidentKeysRef.current.has(key)) {
            seenIncidentKeysRef.current.add(key);
            detectedIncident = incident;
          }
        });

        if (detectedIncident) {
          setLatestIncident(detectedIncident);
          setIsIncidentModalOpen(true);
          playIncidentSound();
        }
      }

      setBuses((prev) => {
        const now = Date.now();
        const incomingIds = new Set(normalized.map((b) => b.bus_id));
        const map = new Map(prev.map((b) => [b.bus_id, b]));

        normalized.forEach((b) => {
          const existing = map.get(b.bus_id);
          map.set(b.bus_id, {
            ...mergeBus(existing, b),
            missing_since_ms: null,
          });
        });

        for (const [busId, bus] of map.entries()) {
          if (incomingIds.has(busId)) continue;

          const missingSince = Number.isFinite(bus?.missing_since_ms)
            ? bus.missing_since_ms
            : now;

          if (now - missingSince >= MISSING_FROM_FEED_GRACE_MS) {
            map.delete(busId);
            continue;
          }

          map.set(busId, { ...bus, missing_since_ms: missingSince });
        }

        return Array.from(map.values());
      });
    } catch (error) {
      console.error('[LiveBusMap] Live fetch failed', error);
    } finally {
      fetchInFlightRef.current = false;
    }
  };

  useEffect(() => {
    fetchLiveBuses();
    const interval = setInterval(fetchLiveBuses, POLLING_INTERVAL_MS);
    return () => clearInterval(interval);
  }, []);

  useEffect(() => {
    if (!window.Echo) return;
    const channelName = isAdminRole ? 'admin.buses' : companyId ? `company.${companyId}` : null;
    if (!channelName) return;

    const channel = window.Echo.private(channelName);

    const onLocation = (payload) => {
      const bus = normalizeBusPayload(payload);
      if (!bus) return;

      setBuses((prev) => {
        const map = new Map(prev.map((b) => [b.bus_id, b]));
        const existing = map.get(bus.bus_id);
        map.set(bus.bus_id, mergeBus(existing, bus));
        return Array.from(map.values());
      });
    };

    const onStatus = (payload) => {
      const busId = payload?.bus_id != null ? Number(payload.bus_id) : null;
      if (!Number.isFinite(busId)) return;

      if (payload?.type === 'logout' || payload?.status === 'offline') {
        setBuses((prev) =>
          prev.map((b) => (b.bus_id === busId ? { ...b, last_seen_ms: b.last_seen_ms ?? Date.now() } : b))
        );

        setTimeout(() => {
          fetchLiveBuses();
        }, 150);
      }
    };

    const onIncident = (payload) => {
      const incident = payload ?? null;
      if (!incident) return;

      const key = `${incident.trip_id}-${incident.reported_at}`;
      if (seenIncidentKeysRef.current.has(key)) return;

      seenIncidentKeysRef.current.add(key);
      hasHydratedIncidentsRef.current = true;
      setLatestIncident(incident);
      setIsIncidentModalOpen(true);
      playIncidentSound();
      setIncidents((prev) => [incident, ...prev]);
    };

    channel.listen('.bus.location.updated', onLocation);
    channel.listen('.bus.status.updated', onStatus);
    channel.listen('.bus.incident.reported', onIncident);

    return () => {
      channel.stopListening('.bus.location.updated', onLocation);
      channel.stopListening('.bus.status.updated', onStatus);
      channel.stopListening('.bus.incident.reported', onIncident);
      window.Echo.leave(`private-${channelName}`);
    };
  }, [companyId, isAdminRole, incidentSoundEnabled]);

  useEffect(() => {
    if (!window.Echo || !operatorUserId) return;

    const channelName = `video-calls.user.${operatorUserId}`;
    const channel = window.Echo.private(channelName);

    const onIncomingCall = (payload) => {
      console.log('[LiveBusMap] Incoming call payload received', {
        callId: payload?.call_id ?? payload?.id ?? null,
        payloadKeys: Object.keys(payload ?? {}),
      });
      const videoCall = normalizeVideoCall(payload);
  if (!videoCall) return;
  if (videoCall.callee_type !== 'user' || videoCall.callee_id !== operatorUserId) return;

  setIncomingVideoCall(videoCall);
  setVideoCallError('');
  setVideoCallStatus(`Incoming call from Bus #${videoCall.caller_id}. Waiting for acceptance.`);
};

    const syncFromPayload = async (payload) => {
      console.log('[LiveBusMap] Signaling payload received', {
        callId: payload?.call_id ?? payload?.id ?? null,
        payloadKeys: Object.keys(payload ?? {}),
        hasOffer: Boolean(payload?.offer || payload?.has_offer),
        hasAnswer: Boolean(payload?.answer || payload?.has_answer),
        hasCandidate: Boolean(payload?.candidate),
      });
      await applyRemoteIceCandidateFromPayload(payload);

      const videoCall = normalizeVideoCall(payload);
      const callId = videoCall?.id ?? payload?.call_id ?? payload?.id ?? null;
      if (!Number.isFinite(Number(callId))) return;

      if (videoCall) {
        activeCallIdRef.current = videoCall.id;
        setActiveVideoCall((current) =>
          current && current.id === videoCall.id ? { ...current, ...videoCall } : videoCall
        );
        setVideoCallError('');
      }

      const needsFreshSync =
        !videoCall ||
        (Boolean(payload?.has_offer) && !videoCall.offer?.sdp) ||
        (Boolean(payload?.has_answer) && !videoCall.answer?.sdp);

      if (needsFreshSync) {
        await syncVideoCall(Number(callId));
      }
    };

    const onEnded = (payload) => {
      const callId = payload?.call_id ?? null;
      const currentActiveVideoCall = activeVideoCallRef.current;
      const currentIncomingVideoCall = incomingVideoCallRef.current;

      if (currentActiveVideoCall && Number(callId) === Number(currentActiveVideoCall.id)) {
        setActiveVideoCall(null);
      }
      if (currentIncomingVideoCall && Number(callId) === Number(currentIncomingVideoCall.id)) {
        setIncomingVideoCall(null);
      }

      activeCallIdRef.current = null;

      if (recorderRef.current && recorderRef.current.state !== 'inactive') {
        recorderRef.current.stop();
      } else {
        cleanupRecordingInfrastructure();
      }

      closeWebRtcSession();
      setVideoCallStatus('Call closed.');
    };

    channel.listen('.video-call.incoming', onIncomingCall);
    channel.listen('.video-call.accepted', syncFromPayload);
    channel.listen('.video-call.offer.sent', syncFromPayload);
    channel.listen('.video-call.answer.sent', syncFromPayload);
    channel.listen('.video-call.ice-candidate.sent', syncFromPayload);
    channel.listen('.video-call.rejected', onEnded);
    channel.listen('.video-call.ended', onEnded);

    if (typeof channel.subscribed === 'function') {
      channel.subscribed(() => {
        console.log('[LiveBusMap] Subscribed to signaling channel', {
          channelName,
          operatorUserId,
        });
      });
    }

    return () => {
      channel.stopListening('.video-call.incoming', onIncomingCall);
      channel.stopListening('.video-call.accepted', syncFromPayload);
      channel.stopListening('.video-call.offer.sent', syncFromPayload);
      channel.stopListening('.video-call.answer.sent', syncFromPayload);
      channel.stopListening('.video-call.ice-candidate.sent', syncFromPayload);
      channel.stopListening('.video-call.rejected', onEnded);
      channel.stopListening('.video-call.ended', onEnded);
      window.Echo.leave(`private-${channelName}`);
    };
  }, [operatorUserId]);

  useEffect(() => {
    activeCallIdRef.current = activeVideoCall?.id ?? null;
    activeVideoCallRef.current = activeVideoCall ?? null;
  }, [activeVideoCall]);

  useEffect(() => {
    incomingVideoCallRef.current = incomingVideoCall ?? null;
  }, [incomingVideoCall]);

  useEffect(() => {
    if (incomingVideoCall?.id) {
      startCallSound();
    } else {
      stopCallSound();
    }

    return () => {
      stopCallSound();
    };
  }, [incomingVideoCall?.id]);

  useEffect(() => {
    if (localVideoRef.current && localStreamRef.current) {
      localVideoRef.current.srcObject = localStreamRef.current;
    }
  }, [activeVideoCall?.id]);

  useEffect(() => {
    if (remoteVideoRef.current && remoteStreamRef.current) {
      remoteVideoRef.current.srcObject = remoteStreamRef.current;
    }
  }, [activeVideoCall?.id]);

  useEffect(() => {
    if (!activeVideoCall?.id) {
      closeWebRtcSession();
      return;
    }

    if (
      activeVideoCall.caller_type === 'user' &&
      Number(activeVideoCall.caller_id) === Number(operatorUserId) &&
      (activeVideoCall.status === 'ringing' || activeVideoCall.status === 'accepted')
    ) {
      void prewarmOutgoingWebRtcSession(activeVideoCall);
    }

    if (
      activeVideoCall.caller_type === 'user' &&
      Number(activeVideoCall.caller_id) === Number(operatorUserId) &&
      (activeVideoCall.status === 'accepted' || activeVideoCall.status === 'connected') &&
      activeVideoCall.has_answer &&
      activeVideoCall.answer?.sdp
    ) {
      void applyRemoteAnswerForCaller(activeVideoCall);
      return;
    }

    if (
      activeVideoCall.caller_type === 'user' &&
      Number(activeVideoCall.caller_id) === Number(operatorUserId) &&
      (activeVideoCall.status === 'accepted' || activeVideoCall.status === 'connected') &&
      !activeVideoCall.has_offer
    ) {
      void sendOfferForCaller(activeVideoCall);
      return;
    }

  if (
  activeVideoCall.status === 'accepted' &&
  activeVideoCall.has_offer &&
  activeVideoCall.offer?.sdp
) {
  void answerVideoCall(activeVideoCall);
}
  }, [
    activeVideoCall?.id,
    activeVideoCall?.caller_type,
    activeVideoCall?.caller_id,
    activeVideoCall?.status,
    activeVideoCall?.has_offer,
    activeVideoCall?.offer?.sdp,
    activeVideoCall?.has_answer,
    activeVideoCall?.answer?.sdp,
    activeVideoCall?.updated_at,
    operatorUserId,
  ]);

  useEffect(() => {
    if (!activeVideoCall?.id) return undefined;

    const interval = setInterval(() => {
      void flushPendingRemoteIceCandidates();
      void fetchRemoteIceCandidates(activeVideoCall.id);
    }, 1500);

    return () => clearInterval(interval);
  }, [activeVideoCall?.id]);

  useEffect(() => {
    return () => {
      if (recorderRef.current && recorderRef.current.state !== 'inactive') {
        recorderRef.current.stop();
      }
      cleanupRecordingInfrastructure();
      closeWebRtcSession();
    };
  }, []);

  useEffect(() => {
    const interval = setInterval(() => {
      const now = Date.now();
      setBuses((prev) =>
        prev.filter((b) => {
          if (!b.last_seen_ms) return true;
          return now - b.last_seen_ms < OFFLINE_REMOVE_MS;
        })
      );
    }, 30000);

    return () => clearInterval(interval);
  }, []);

  const filteredList = useMemo(() => {
    const q = query.trim().toLowerCase();

    const filtered = buses.filter((bus) => {
      const isOffline = getIsOffline(bus);

      if (statusFilter === 'live' && isOffline) return false;
      if (statusFilter === 'offline' && !isOffline) return false;
      if (companyFilter !== 'all' && (bus.company ?? '') !== companyFilter) return false;
      if (routeFilter !== 'all' && (bus.route ?? '') !== routeFilter) return false;

      if (!q) return true;

      const hay = [bus.plate_number, bus.bus_id, bus.driver, bus.company, bus.route]
        .filter((v) => v !== null && v !== undefined)
        .join(' ')
        .toLowerCase();

      return hay.includes(q);
    });

    filtered.sort((a, b) => (b.last_seen_ms ?? 0) - (a.last_seen_ms ?? 0));
    return filtered;
  }, [buses, query, statusFilter, companyFilter, routeFilter]);

  const goHome = () => {
    window.location.href = '/admin/dashboard';
  };

  const resetView = () => {
    const map = mapRef.current;
    if (!map) return;
    map.setView(DEFAULT_CENTER, DEFAULT_ZOOM);
  };

  const fitToBuses = () => {
    const map = mapRef.current;
    if (!map) return;

    const points = filteredList
      .map((b) => [Number(b.lat), Number(b.lng)])
      .filter(([lat, lng]) => Number.isFinite(lat) && Number.isFinite(lng));

    if (points.length === 0) return;

    const bounds = L.latLngBounds(points);
    map.fitBounds(bounds, { padding: [30, 30] });
  };

  const flyToBus = (bus, { follow = false } = {}) => {
    const map = mapRef.current;
    const lat = Number(bus?.lat);
    const lng = Number(bus?.lng);

    if (!map) return;
    if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;

    map.invalidateSize(true);
    map.flyTo([lat, lng], 17, { animate: true, duration: 0.75 });

    if (follow) setFollowBusId(bus.bus_id);

    setTimeout(() => {
      const marker = markerRefs.current.get(bus.bus_id);
      marker?.openPopup?.();
    }, 250);
  };

  const isBusVisibleInCurrentView = (map, lat, lng) => {
    if (!map) return false;
    if (!Number.isFinite(lat) || !Number.isFinite(lng)) return false;

    return map.getBounds().contains(L.latLng(lat, lng));
  };

  useEffect(() => {
    if (followBusId == null) return;

    const interval = setInterval(() => {
      const map = mapRef.current;
      if (!map) return;

      const bus = buses.find((b) => b.bus_id === followBusId);
      if (!bus) return;

      const lat = Number(bus.lat);
      const lng = Number(bus.lng);
      if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;

      if (isBusVisibleInCurrentView(map, lat, lng)) return;

      map.panTo([lat, lng], { animate: true });
      if (map.getZoom() < FOLLOW_FLY_ZOOM) map.setZoom(FOLLOW_FLY_ZOOM);
    }, FOLLOW_INTERVAL_MS);

    return () => clearInterval(interval);
  }, [followBusId, buses]);

  useEffect(() => {
    if (didAutoFollowRef.current) return;
    if (!Number.isFinite(initialTripIdParam)) return;
    if (!buses.length) return;

    const target = buses.find((b) => Number(b.trip_id) === initialTripIdParam);
    if (!target) return;

    flyToBus(target, { follow: true });
    didAutoFollowRef.current = true;
  }, [buses, initialTripIdParam]);

  return (
    <div style={{ height: '100vh', width: '100%', position: 'relative' }}>
      <audio ref={incidentAudioRef} src="/sounds/chat.mp3" preload="auto" />

      {incomingVideoCall && (
        <div
          className="fixed inset-0 z-[1300] flex items-center justify-center bg-black/50 p-4"
          style={{
            position: 'fixed',
            inset: 0,
            zIndex: 1300,
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            backgroundColor: 'rgba(0,0,0,0.5)',
            padding: 16,
          }}
        >
          <div
            className="w-full max-w-lg rounded-2xl bg-white shadow-2xl"
            style={{
              width: '100%',
              maxWidth: 560,
              borderRadius: 16,
              backgroundColor: '#fff',
              boxShadow: '0 25px 50px -12px rgba(0,0,0,0.35)',
              overflow: 'hidden',
            }}
          >
            <div style={{ padding: '16px 20px', borderBottom: '1px solid #e2e8f0' }}>
              <div style={{ fontSize: 18, fontWeight: 700 }}>Incoming Emergency Call</div>
              <div style={{ fontSize: 13, color: '#64748b', marginTop: 4 }}>
                Bus #{incomingVideoCall.caller_id} is calling this operator.
              </div>
            </div>

            <div style={{ padding: '16px 20px', fontSize: 14, color: '#334155' }}>
              Call ID: #{incomingVideoCall.id}
            </div>

            <div
              style={{
                display: 'flex',
                justifyContent: 'flex-end',
                gap: 8,
                padding: '16px 20px',
                borderTop: '1px solid #e2e8f0',
              }}
            >
              <button
                type="button"
                className="btn btn-outline-secondary"
                onClick={rejectIncomingVideoCall}
                disabled={videoCallBusy}
              >
                Reject
              </button>

              <button
                type="button"
                className="btn btn-success"
                onClick={acceptIncomingVideoCall}
                disabled={videoCallBusy}
              >
                Accept
              </button>
            </div>
          </div>
        </div>
      )}

      {(activeVideoCall || videoCallError || recordingError || recordingInfo) && (
        <div
          className={`alert ${videoCallError ? 'alert-danger' : 'alert-primary'} shadow-sm`}
          style={{
            position: 'absolute',
            top: 12,
            left: 56,
            zIndex: 1001,
            minWidth: 380,
            maxWidth: 640,
          }}
        >
          {videoCallError ? (
            <div>{videoCallError}</div>
          ) : (
            <>
              {recordingError && <div className="mb-2">{recordingError}</div>}
              {recordingInfo && <div className="mb-2">{recordingInfo}</div>}

   
            </>
          )}
        </div>
      )}

      {activeVideoCall && (
        <div
          className="card shadow-sm"
          style={{
            position: 'absolute',
            top: 80,
            left: 12,
            width: 'min(460px, calc(100vw - 24px))',
            zIndex: 1000,
            borderRadius: 18,
            overflow: 'hidden',
            maxHeight: 'calc(100vh - 108px)',
          }}
        >
          <div
            className="card-header d-flex justify-content-between align-items-start"
            style={{
              padding: '14px 16px 12px',
              background: 'linear-gradient(180deg, #ffffff 0%, #f8fafc 100%)',
              borderBottom: '1px solid #e2e8f0',
            }}
          >
            <div>
              <strong style={{ display: 'block', fontSize: 18 }}>Emergency Video Call</strong>
              <div className="text-muted" style={{ fontSize: 12, marginTop: 2 }}>
                Call #{activeVideoCall.id}
              </div>
            </div>
            <span
              className={`badge ${activeVideoCall.status === 'connected' ? 'bg-success' : 'bg-danger'}`}
              style={{ textTransform: 'capitalize' }}
            >
              {activeVideoCall.status}
            </span>
          </div>

          <div
            className="card-body"
            style={{
              padding: 12,
              display: 'grid',
              gap: 10,
            }}
          >
            <div className="text-muted" style={{ fontSize: 12 }}>
              Citizen / Remote video
            </div>

            <div
              style={{
                position: 'relative',
                width: '100%',
                aspectRatio: '4 / 5',
                maxHeight: 'calc(100vh - 320px)',
                minHeight: 340,
                background: '#0f172a',
                borderRadius: 16,
                overflow: 'hidden',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
              }}
            >
              <video
                ref={remoteVideoRef}
                autoPlay
                playsInline
                style={{
                  width: '100%',
                  height: '100%',
                  objectFit: 'contain',
                  background: '#0f172a',
                  display: 'block',
                }}
              />

              <div
                style={{
                  position: 'absolute',
                  right: 12,
                  top: 12,
                  width: '34%',
                  minWidth: 112,
                  maxWidth: 152,
                  aspectRatio: '3 / 4',
                  background: '#1e293b',
                  borderRadius: 12,
                  overflow: 'hidden',
                  border: '2px solid rgba(255,255,255,0.9)',
                  boxShadow: '0 12px 24px rgba(15,23,42,0.28)',
                }}
              >
                <video
                  ref={localVideoRef}
                  autoPlay
                  playsInline
                  muted
                  style={{
                    width: '100%',
                    height: '100%',
                    objectFit: 'cover',
                    background: '#1e293b',
                    display: 'block',
                  }}
                />
              </div>
            </div>

            <div
              style={{
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'space-between',
                gap: 8,
                flexWrap: 'wrap',
                color: '#64748b',
                fontSize: 12,
              }}
            >
              <span>Your camera is floating on top of the call.</span>
              <span>{videoCallStatus}</span>
            </div>

          </div>

          <div
            className="card-footer"
            style={{
              padding: 12,
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'space-between',
              gap: 8,
              flexWrap: 'wrap',
              background: '#fff',
              borderTop: '1px solid #e2e8f0',
            }}
          >
            <div className="d-flex gap-2 flex-wrap">
              <button
                type="button"
                className="btn btn-sm btn-outline-secondary"
                onClick={() => {
                  const stream = localStreamRef.current;
                  if (!stream) return;

                  stream.getAudioTracks().forEach((track) => {
                    track.enabled = !track.enabled;
                  });
                }}
                disabled={videoCallBusy || !localStreamRef.current}
              >
                Mute
              </button>

              {!isRecording ? (
                <button
                  type="button"
                  className="btn btn-sm btn-danger"
                  onClick={startRecording}
                  disabled={videoCallBusy}
                >
                  Start Recording
                </button>
              ) : (
                <button
                  type="button"
                  className="btn btn-sm btn-warning"
                  onClick={stopRecording}
                  disabled={videoCallBusy}
                >
                  Stop Recording
                </button>
              )}
            </div>

            <button
              type="button"
              className="btn btn-sm btn-outline-danger"
              onClick={endVideoCall}
              disabled={videoCallBusy}
            >
              End Call
            </button>
          </div>
        </div>
      )}
      {isIncidentModalOpen && latestIncident && (
        <div
          className="fixed inset-0 z-[1200] flex items-center justify-center bg-black/50 p-4"
          style={{
            position: 'fixed',
            inset: 0,
            zIndex: 1200,
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            backgroundColor: 'rgba(0,0,0,0.5)',
            padding: 16,
          }}
        >
          <div
            className="w-full max-w-xl rounded-2xl bg-white shadow-2xl ring-1 ring-red-200"
            style={{
              width: '100%',
              maxWidth: 720,
              borderRadius: 16,
              backgroundColor: '#fff',
              boxShadow: '0 25px 50px -12px rgba(0,0,0,0.35)',
              border: '1px solid #fecaca',
              overflow: 'hidden',
            }}
          >
            <div
              className="border-b border-red-100 bg-red-600 px-5 py-4 text-white"
              style={{
                borderBottom: '1px solid #fee2e2',
                backgroundColor: '#dc2626',
                padding: '16px 20px',
                color: '#fff',
              }}
            >
              <div className="text-lg font-semibold">Incident Alert</div>
              <div className="mt-1 text-sm text-red-100">A new incident has been reported by a live bus.</div>
            </div>

            <div style={{ padding: '16px 20px', color: '#334155', fontSize: 14 }}>
              <div><strong>Bus:</strong> {latestIncident.plate_number ?? `#${latestIncident.bus_id}`}</div>
              <div><strong>Trip:</strong> #{latestIncident.trip_id}</div>
              <div><strong>Type:</strong> {latestIncident.incident_type ?? 'unknown'}</div>
              <div><strong>Reason:</strong> {latestIncident.reason ?? 'N/A'}</div>
              <div><strong>Reported at:</strong> {latestIncident.reported_at ?? 'N/A'}</div>
            </div>

            <div
              style={{
                borderTop: '1px solid #f1f5f9',
                padding: '16px 20px',
                display: 'flex',
                justifyContent: 'flex-end',
                gap: 8,
              }}
            >
              <button
                type="button"
                className="btn btn-outline-secondary"
                onClick={() => setIncidentSoundEnabled((prev) => !prev)}
              >
                {incidentSoundEnabled ? 'Mute Alert Sound' : 'Unmute Alert Sound'}
              </button>

              <button
                type="button"
                className="btn btn-danger"
                onClick={() => setIsIncidentModalOpen(false)}
              >
                Acknowledge
              </button>
            </div>
          </div>
        </div>
      )}

      {latestIncident && (
        <div
          className="alert alert-danger shadow-sm"
          style={{ position: 'absolute', top: 12, left: 56, zIndex: 1000, minWidth: 360, maxWidth: 520 }}
        >
          <div className="d-flex justify-content-between align-items-start">
            <div>
              <strong>Incident Detected</strong>
              <div style={{ fontSize: 13 }}>
                Bus: {latestIncident.plate_number ?? `#${latestIncident.bus_id}`} | Trip: #{latestIncident.trip_id}
              </div>
              <div style={{ fontSize: 13 }}>
                Type: {latestIncident.incident_type ?? 'unknown'} | Reason: {latestIncident.reason ?? 'N/A'}
              </div>
              <div className="text-muted" style={{ fontSize: 12 }}>
                Reported: {latestIncident.reported_at ?? 'N/A'}
              </div>
            </div>

            <button type="button" className="btn-close" onClick={() => setLatestIncident(null)} />
          </div>
        </div>
      )}

      <div
        style={{
          position: 'absolute',
          top: 12,
          right: 12,
          width: 360,
          maxHeight: 'calc(-55px + 100vh)',
          zIndex: 1000,
          overflow: 'hidden',
          pointerEvents: 'auto',
        }}
        className="card shadow-sm"
      >
        <div className="card-header">
          <div className="d-flex align-items-center justify-content-between">
            <div>
              <strong>Live Buses</strong>
              <div className="text-muted" style={{ fontSize: 12 }}>
                Showing {filteredList.length} bus(es)
              </div>
              <div className="text-muted" style={{ fontSize: 12 }}>
                Incidents tracked: {incidents.length}
              </div>
            </div>

            <div className="d-flex gap-2">
              <button className="btn btn-sm btn-light" onClick={fetchLiveBuses}>
                Refresh
              </button>
              <button className="btn btn-sm btn-light" onClick={fitToBuses}>
                Fit
              </button>
            </div>
          </div>

          <div className="mt-2 d-flex gap-2">
            <input
              className="form-control form-control-sm"
              placeholder="Search plate, driver, company, route..."
              value={query}
              onChange={(e) => setQuery(e.target.value)}
            />
          </div>

          <div className="mt-2 d-flex gap-2">
            <select
              className="form-select form-select-sm"
              value={statusFilter}
              onChange={(e) => setStatusFilter(e.target.value)}
            >
              <option value="all">All</option>
              <option value="live">Live</option>
              <option value="offline">Offline</option>
            </select>

            <select
              className="form-select form-select-sm"
              value={companyFilter}
              onChange={(e) => setCompanyFilter(e.target.value)}
            >
              <option value="all">All companies</option>
              {companyOptions.map((c) => (
                <option key={`co-${c}`} value={c}>
                  {c}
                </option>
              ))}
            </select>

            <select
              className="form-select form-select-sm"
              value={routeFilter}
              onChange={(e) => setRouteFilter(e.target.value)}
            >
              <option value="all">All routes</option>
              {routeOptions.map((r) => (
                <option key={`rt-${r}`} value={r}>
                  {r}
                </option>
              ))}
            </select>
          </div>

          {followBusId != null && (
            <div className="mt-2 d-flex align-items-center justify-content-between">
              <div style={{ fontSize: 12 }} className="text-muted">
                Following Bus #{followBusId}
              </div>
              <button className="btn btn-sm btn-outline-secondary" onClick={() => setFollowBusId(null)}>
                Stop
              </button>
            </div>
          )}
        </div>

        <div style={{ overflowY: 'auto', maxHeight: 'calc(100vh - 200px)' }}>
          {filteredList.length === 0 ? (
            <div className="p-3 text-muted">No buses found.</div>
          ) : (
            <ul className="list-group list-group-flush">
              {filteredList.map((bus) => {
                const now = Date.now();
                const msSince = bus.last_seen_ms ? now - bus.last_seen_ms : null;
                const isOffline = msSince !== null && msSince > OFFLINE_LABEL_MS;
                const speedKph = Number(bus.speed);
                const showSpeed = Number.isFinite(speedKph) && speedKph > 0;

                return (
                  <li
                    key={`bus-row-${bus.bus_id}`}
                    title="Click to locate this bus on the map"
                    className="list-group-item bus-row"
                    style={{ cursor: 'pointer' }}
                    onClick={() => flyToBus(bus)}
                  >
                    <div className="d-flex justify-content-between align-items-start">
                      <div>
                        <div style={{ fontWeight: 600 }}>{bus.plate_number ?? `Bus #${bus.bus_id}`}</div>
                        <div className="text-muted" style={{ fontSize: 12 }}>
                          {bus.company ?? '—'} • {bus.route ?? '—'} • {bus.driver ?? '—'}
                        </div>
                        {showSpeed && (
                          <div className="text-muted" style={{ fontSize: 12 }}>
                            Speed: {speedKph} km/h
                          </div>
                        )}
                        <div className="text-muted" style={{ fontSize: 12 }}>
                          Onboard: {bus.onboard_count ?? 0} • Available: {bus.available_capacity ?? 0} • Capacity: {bus.bus_capacity ?? 0}
                        </div>
                      </div>

                      <div className="d-flex align-items-center gap-2">
                        <span className={`badge ${isOffline ? 'bg-secondary' : 'bg-success'}`}>
                          {isOffline ? 'offline' : 'live'}
                        </span>

                        <button
                          className="btn btn-sm btn-outline-primary"
                          onClick={(e) => {
                            e.stopPropagation();
                            flyToBus(bus, { follow: true });
                          }}
                        >
                          Follow
                        </button>

                        <button
                          className="btn btn-sm btn-outline-success"
                          onClick={(e) => {
                            e.stopPropagation();
                            startVideoCall(bus);
                          }}
                          disabled={videoCallBusy}
                        >
                          Call
                        </button>
                      </div>
                    </div>

                    {msSince !== null && (
                      <div className="text-muted" style={{ fontSize: 12, marginTop: 4 }}>
                        Last seen: {formatSince(msSince)} ago
                      </div>
                    )}
                  </li>
                );
              })}
            </ul>
          )}
        </div>
      </div>

      <MapContainer center={DEFAULT_CENTER} zoom={DEFAULT_ZOOM} style={{ height: '100%', width: '100%' }}>
        <MapRefBinder mapRef={mapRef} />
        <MapTopLeftControls onHome={goHome} onReset={resetView} onFit={fitToBuses} />

        <TileLayer
          attribution="&copy; OpenStreetMap contributors"
          url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
        />

        {buses
          .filter(
            (b) =>
              b.bus_id !== null &&
              b.lat !== null &&
              b.lng !== null &&
              Number.isFinite(Number(b.lat)) &&
              Number.isFinite(Number(b.lng))
          )
          .map((bus) => {
            const now = Date.now();
            const msSince = bus.last_seen_ms ? now - bus.last_seen_ms : null;
            const isOffline = msSince !== null && msSince > OFFLINE_LABEL_MS;
            const speedKph = Number(bus.speed);
            const showSpeed = Number.isFinite(speedKph) && speedKph > 0;

            return (
              <Marker
                key={`bus-${bus.bus_id}`}
                position={[Number(bus.lat), Number(bus.lng)]}
                icon={busMarkerIcon}
                ref={(m) => {
                  if (m) markerRefs.current.set(bus.bus_id, m);
                }}
              >
                <Popup>
                  <div style={{ minWidth: 220, color: '#0f172a' }}>
                    <div style={{ fontSize: 16, fontWeight: 700, marginBottom: 4 }}>
                      {bus.plate_number ?? `Bus #${bus.bus_id}`}
                    </div>

                    <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 8 }}>
                      <span
                        style={{
                          display: 'inline-block',
                          fontSize: 11,
                          fontWeight: 700,
                          textTransform: 'uppercase',
                          letterSpacing: '0.04em',
                          padding: '2px 8px',
                          borderRadius: 999,
                          color: isOffline ? '#475569' : '#166534',
                          backgroundColor: isOffline ? '#e2e8f0' : '#dcfce7',
                        }}
                      >
                        {isOffline ? 'Offline' : 'Live'}
                      </span>

                      <span style={{ fontSize: 12, color: '#64748b' }}>{bus.route ?? 'No route'}</span>
                    </div>

                    {showSpeed && (
                      <div style={{ fontSize: 13, marginBottom: 4 }}>
                        <strong>Speed:</strong> {speedKph} km/h
                      </div>
                    )}

                    <div style={{ fontSize: 13, marginBottom: 4 }}>
                      <strong>Occupancy:</strong> {bus.onboard_count ?? 0} / {bus.bus_capacity ?? 0}
                    </div>

                    {msSince !== null && (
                      <div style={{ fontSize: 12, color: '#64748b', marginBottom: 2 }}>
                        Last seen: {formatSince(msSince)} ago
                      </div>
                    )}

                    <div style={{ fontSize: 12, color: '#64748b' }}>
                      Updated: {formatUpdatedAt(bus.updated_at)}
                    </div>

                    <div style={{ marginTop: 10, display: 'flex', gap: 8 }}>
                      <button
                        type="button"
                        className="btn btn-sm btn-outline-primary"
                        onClick={() => flyToBus(bus, { follow: true })}
                      >
                        Follow
                      </button>

                      <button
                        type="button"
                        className="btn btn-sm btn-outline-success"
                        onClick={() => startVideoCall(bus)}
                        disabled={videoCallBusy}
                      >
                        Call
                      </button>
                    </div>
                  </div>
                </Popup>
              </Marker>
            );
          })}
      </MapContainer>
    </div>
  );
}
