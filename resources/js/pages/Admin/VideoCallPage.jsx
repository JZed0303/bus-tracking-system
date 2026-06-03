import React, { useEffect, useMemo, useRef, useState } from 'react';
import axios from 'axios';
import { buildWebRtcIceConfiguration, buildWebRtcRuntimePolicy } from '../../utils/webrtcIceConfiguration';
import { startCallSound, stopCallSound } from '../../chatSoundHelper';

function normalizeVideoCall(payload) {
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
}

function describeError(error, fallback) {
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
}

export default function VideoCallPage({ initialBusId = null, initialBusLabel = '' }) {
  const operatorUserId = Number(window.authUser?.id || 0) || null;
  const role = String(window.userRole || '').toLowerCase();
  const companyId = window.companyId ?? null;
  const isAdminRole = ['super_admin', 'system_admin', 'administrator', 'admin'].includes(role);
  const [busId] = useState(initialBusId ? Number(initialBusId) : null);
  const [busLabel] = useState(initialBusLabel || (busId ? `Bus #${busId}` : ''));
  const [activeVideoCall, setActiveVideoCall] = useState(null);
  const [incomingVideoCall, setIncomingVideoCall] = useState(null);
  const [videoCallBusy, setVideoCallBusy] = useState(false);
  const [videoCallError, setVideoCallError] = useState('');
  const [videoCallStatus, setVideoCallStatus] = useState(busId ? `Ready to call ${busLabel || `Bus #${busId}`}` : 'Waiting for calls...');
  const [isRecording, setIsRecording] = useState(false);
  const [recordingError, setRecordingError] = useState('');
  const [recordingInfo, setRecordingInfo] = useState('');

  const localVideoRef = useRef(null);
  const remoteVideoRef = useRef(null);
  const peerConnectionRef = useRef(null);
  const localStreamRef = useRef(null);
  const remoteStreamRef = useRef(null);
  const recorderRef = useRef(null);
  const recordedChunksRef = useRef([]);
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
  const startedBusCallRef = useRef(false);
  const connectTimeoutRef = useRef(null);
  const disconnectTimeoutRef = useRef(null);
  const restartInFlightRef = useRef(false);
  const iceRestartAttemptsRef = useRef(0);
  const fetchingIceRef = useRef(false);

  const iceConfiguration = useMemo(
    () => buildWebRtcIceConfiguration(),
    []
  );
  const webRtcRuntimePolicy = useMemo(
    () => buildWebRtcRuntimePolicy(),
    []
  );

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
      console.error('[VideoCallPage] Failed to restart ICE', error);
      setVideoCallError(describeError(error, 'Could not recover the WebRTC connection.'));
      return false;
    } finally {
      restartInFlightRef.current = false;
    }
  }

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
  };

  const buildRecordingStream = () => {
    const stream = new MediaStream();
    const localStream = localStreamRef.current;
    const remoteStream = remoteStreamRef.current;

    if (localStream) {
      localStream.getTracks().forEach((track) => stream.addTrack(track));
    }
    if (remoteStream) {
      remoteStream.getTracks().forEach((track) => stream.addTrack(track));
    }

    return stream;
  };

  const stopRecording = async () => {
    if (recorderRef.current) recorderRef.current.stop();
  };

  const uploadRecording = async (blob) => {
    if (!activeVideoCall?.id) return;

    const formData = new FormData();
    formData.append('recording', blob, `call-${activeVideoCall.id}.webm`);
    formData.append('recorded_by_type', 'user');
    formData.append('recorded_by_id', operatorUserId ?? '');

    const response = await axios.post(`/api/video-calls/${activeVideoCall.id}/recordings`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });

    const url = response.data?.data?.recording?.url ?? '';
    setRecordingInfo(url ? `Recording saved: ${url}` : 'Recording saved.');
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

      const stream = buildRecordingStream();
      if (!stream.getTracks().length) {
        setRecordingError('No audio/video tracks available to record yet.');
        return;
      }

      const mimeTypeCandidates = [
        'video/webm;codecs=vp9,opus',
        'video/webm;codecs=vp8,opus',
        'video/webm',
      ];
      const selectedMimeType =
        mimeTypeCandidates.find((mimeType) => MediaRecorder.isTypeSupported(mimeType)) || '';

      if (!selectedMimeType) {
        setRecordingError('This browser does not support recording in WebM.');
        return;
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

      recorder.onstop = async () => {
        const blob = new Blob(recordedChunksRef.current, { type: selectedMimeType });
        recordedChunksRef.current = [];
        recorderRef.current = null;
        setIsRecording(false);
        await uploadRecording(blob);
      };

      recorder.start(1000);
      setIsRecording(true);
      setRecordingInfo('Recording started.');
    } catch (error) {
      console.error('[VideoCallPage] Failed to start recording', error);
      setRecordingError('Could not start recording.');
    }
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
      console.error('[VideoCallPage] Failed to sync video call', error);
      setVideoCallError(describeError(error, 'Could not refresh the video call status.'));
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
    if (localVideoRef.current) localVideoRef.current.srcObject = stream;
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
        console.log('[VideoCallPage] addIceCandidate success', {
          candidate: queuedCandidate.candidate,
          sdpMid: queuedCandidate.sdpMid,
          sdpMLineIndex: queuedCandidate.sdpMLineIndex,
        });
      } catch (error) {
        console.error('[VideoCallPage] Failed to apply queued ICE candidate', error);
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
      console.log('[VideoCallPage] ICE candidate sent', {
        callId,
        candidate: rawCandidate,
        sdpMid: candidate.sdpMid ?? null,
        sdpMLineIndex: candidate.sdpMLineIndex ?? null,
      });
    } catch (error) {
      console.error('[VideoCallPage] Failed to send ICE candidate', error);
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
    if (remoteVideoRef.current) remoteVideoRef.current.srcObject = remoteStream;

    peerConnection.ontrack = (event) => {
      console.log('[VideoCallPage] Remote track received', {
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
          remoteStream.addTrack(track);
        });
      });
      if (remoteVideoRef.current) remoteVideoRef.current.srcObject = remoteStream;
      clearConnectionTimers();
      iceRestartAttemptsRef.current = 0;
      setVideoCallStatus('Remote video connected.');
    };

    peerConnection.onicecandidate = (event) => {
      console.log('[VideoCallPage] Local ICE candidate', event.candidate
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
      if (event.candidate) void sendLocalIceCandidate(event.candidate);
    };

    peerConnection.onconnectionstatechange = () => {
      if (peerConnection.connectionState) {
        const state = peerConnection.connectionState;
        console.log('[VideoCallPage] Connection state changed', {
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
      }
    };

    peerConnection.oniceconnectionstatechange = () => {
      if (peerConnection.iceConnectionState) {
        const state = peerConnection.iceConnectionState;
        console.log('[VideoCallPage] ICE connection state changed', {
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
    console.log('[VideoCallPage] Remote ICE candidate received', {
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
      console.error('[VideoCallPage] Failed to fetch remote ICE candidates', error);
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
      setVideoCallStatus('Preparing local media...');

      const peerConnection = await ensurePeerConnection();
      const remoteOffer = new RTCSessionDescription({
        type: videoCall.offer.type,
        sdp: sanitizeSdp(videoCall.offer.sdp),
      });

      if (!peerConnection.remoteDescription || peerConnection.remoteDescription.sdp !== remoteOffer.sdp) {
        setVideoCallStatus('Offer received. Applying remote offer...');
        await peerConnection.setRemoteDescription(remoteOffer);
        console.log('[VideoCallPage] setRemoteDescription success', {
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
      console.log('[VideoCallPage] answer sent', {
        callId: videoCall.id,
        type: answer.type,
        hasSdp: Boolean(answer.sdp),
      });

      answeredOfferKeyRef.current = offerKey;
      setVideoCallStatus('Answer sent. Connecting media...');
      await flushPendingRemoteIceCandidates();
      scheduleConnectTimeout();
    } catch (error) {
      console.error('[VideoCallPage] Failed to answer WebRTC call', error);
      setVideoCallError(describeError(error, 'Could not establish the WebRTC media session.'));
    } finally {
      setVideoCallBusy(false);
    }
  };

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
        console.warn('[VideoCallPage] Skipping offer creation because signaling is not stable', {
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
      console.log('[VideoCallPage] offer sent', {
        callId: videoCall.id,
        type: offer.type,
        hasSdp: Boolean(offer.sdp),
      });

      sentOfferKeyRef.current = offerStateKey;
      setVideoCallStatus('Offer sent. Waiting for answer...');
      await flushPendingRemoteIceCandidates();
      scheduleConnectTimeout();
    } catch (error) {
      console.error('[VideoCallPage] Failed to create/send WebRTC offer', error);
      setVideoCallError(describeError(error, 'Could not start the WebRTC media session.'));
    } finally {
      setVideoCallBusy(false);
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
          console.warn('[VideoCallPage] Skipping remote answer in unexpected signaling state', {
            callId: videoCall.id,
            signalingState: peerConnection.signalingState,
            remoteDescriptionType: peerConnection.remoteDescription?.type ?? null,
          });
          return;
        }

        setVideoCallStatus('Answer received. Applying remote answer...');
        await peerConnection.setRemoteDescription(remoteAnswer);
        console.log('[VideoCallPage] setRemoteDescription success', {
          kind: 'answer',
          callId: videoCall.id,
        });
      }

      setVideoCallStatus('Answer received. Connecting media...');
      console.log('[VideoCallPage] answer received', {
        callId: videoCall.id,
        type: videoCall.answer.type,
        hasSdp: Boolean(videoCall.answer?.sdp),
      });
      appliedAnswerKeyRef.current = answerKey;
      await flushPendingRemoteIceCandidates();
      scheduleConnectTimeout();
    } catch (error) {
      console.error('[VideoCallPage] Failed to apply remote WebRTC answer', error);
      setVideoCallError(describeError(error, 'Could not apply the remote WebRTC answer.'));
    }
  };

  const prewarmOutgoingWebRtcSession = async (videoCall) => {
    if (!videoCall?.id) return;
    if (videoCall.caller_type !== 'user' || Number(videoCall.caller_id) !== Number(operatorUserId)) return;
    if (peerConnectionRef.current && localStreamRef.current) return;

    try {
      await ensurePeerConnection();
    } catch (error) {
      console.error('[VideoCallPage] Failed to prewarm outgoing WebRTC session', error);
    }
  };

  const startVideoCall = async () => {
    if (!operatorUserId || !busId) {
      setVideoCallError('Missing operator or bus information for this call.');
      return;
    }

    setVideoCallBusy(true);
    setVideoCallError('');

    try {
      const response = await axios.post('/api/video-calls/start', {
        caller_type: 'user',
        caller_id: operatorUserId,
        callee_type: 'bus',
        callee_id: busId,
      });

      const videoCall = normalizeVideoCall(response.data?.data?.video_call);
      activeCallIdRef.current = videoCall?.id ?? null;
      setActiveVideoCall(videoCall);
      setIncomingVideoCall(null);
      setVideoCallStatus(`Calling ${busLabel || `Bus #${busId}`}...`);
    } catch (error) {
      console.error('[VideoCallPage] Failed to start video call', error);
      setVideoCallError(describeError(error, 'Could not start the video call.'));
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
      setVideoCallStatus('Call accepted. Waiting for the bus offer...');
    } catch (error) {
      console.error('[VideoCallPage] Failed to accept video call', error);
      setVideoCallError(describeError(error, 'Could not accept the incoming video call.'));
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
      console.error('[VideoCallPage] Failed to reject video call', error);
      setVideoCallError(describeError(error, 'Could not reject the incoming video call.'));
    } finally {
      setVideoCallBusy(false);
    }
  };

  const endVideoCall = async () => {
    if (!activeVideoCall?.id) return;

    setVideoCallBusy(true);
    setVideoCallError('');

    try {
      await axios.post(`/api/video-calls/${activeVideoCall.id}/end`);
      setActiveVideoCall(null);
      setIncomingVideoCall(null);
      activeCallIdRef.current = null;
      closeWebRtcSession();
      setVideoCallStatus('Call ended.');
    } catch (error) {
      console.error('[VideoCallPage] Failed to end video call', error);
      setVideoCallError(describeError(error, 'Could not end the video call.'));
    } finally {
      setVideoCallBusy(false);
    }
  };

  useEffect(() => {
    if (!window.Echo || !operatorUserId) return;

    const channelName = `video-calls.user.${operatorUserId}`;
    const channel = window.Echo.private(channelName);

    const onIncomingCall = (payload) => {
      console.log('[VideoCallPage] Incoming call payload received', {
        callId: payload?.call_id ?? payload?.id ?? null,
        payloadKeys: Object.keys(payload ?? {}),
      });
      const videoCall = normalizeVideoCall(payload);
      if (!videoCall) return;
      if (videoCall.callee_type !== 'user' || videoCall.callee_id !== operatorUserId) return;

      setIncomingVideoCall(videoCall);
      setActiveVideoCall((current) => current ?? videoCall);
      setVideoCallError('');
      setVideoCallStatus(`Incoming call from Bus #${videoCall.caller_id}.`);
    };

    const syncFromPayload = async (payload) => {
      console.log('[VideoCallPage] Signaling payload received', {
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
        setActiveVideoCall((current) => (current && current.id === videoCall.id ? { ...current, ...videoCall } : videoCall));
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

      if (currentActiveVideoCall && Number(callId) === Number(currentActiveVideoCall.id)) setActiveVideoCall(null);
      if (currentIncomingVideoCall && Number(callId) === Number(currentIncomingVideoCall.id)) setIncomingVideoCall(null);
      activeCallIdRef.current = null;
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
        console.log('[VideoCallPage] Subscribed to signaling channel', {
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
    if (busId && !startedBusCallRef.current) {
      startedBusCallRef.current = true;
      void startVideoCall();
    }
  }, [busId]);

  useEffect(() => {
    if (!activeVideoCall?.id) {
      closeWebRtcSession();
      if (isRecording) void stopRecording();
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

    if (activeVideoCall.has_offer && activeVideoCall.offer?.sdp) {
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
    if (!activeVideoCall?.id) return undefined;

    const interval = setInterval(() => {
      void flushPendingRemoteIceCandidates();
    }, 1500);

    return () => clearInterval(interval);
  }, [activeVideoCall?.id]);

  useEffect(() => {
    return () => closeWebRtcSession();
  }, []);

  const callTitle = busId ? `Calling ${busLabel || `Bus #${busId}`}` : 'Dedicated Video Call';
  const backHref = isAdminRole ? '/admin/live-map' : companyId ? '/company/live-map' : '/admin/live-map';

  return (
    <div className="container-fluid py-4">
      <div className="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
          <h3 className="mb-1">{callTitle}</h3>
          <div className="text-muted">{videoCallStatus}</div>
        </div>
        <div className="d-flex gap-2">
          <a href={backHref} className="btn btn-outline-secondary">
            Back to Live Map
          </a>
          {busId && !activeVideoCall && (
            <button type="button" className="btn btn-success" onClick={startVideoCall} disabled={videoCallBusy}>
              Call {busLabel || `Bus #${busId}`}
            </button>
          )}
          {activeVideoCall && (
            <>
              <button type="button" className="btn btn-outline-primary" onClick={() => syncVideoCall(activeVideoCall.id)} disabled={videoCallBusy}>
                Refresh
              </button>
              {!isRecording && (
                <button type="button" className="btn btn-outline-success" onClick={startRecording} disabled={videoCallBusy}>
                  Start Recording
                </button>
              )}
              {isRecording && (
                <button type="button" className="btn btn-outline-warning" onClick={stopRecording} disabled={videoCallBusy}>
                  Stop Recording
                </button>
              )}
              <button type="button" className="btn btn-outline-danger" onClick={endVideoCall} disabled={videoCallBusy}>
                End
              </button>
            </>
          )}
        </div>
      </div>

      {videoCallError && <div className="alert alert-danger">{videoCallError}</div>}
      {recordingError && <div className="alert alert-warning">{recordingError}</div>}
      {recordingInfo && <div className="alert alert-info">{recordingInfo}</div>}

      {incomingVideoCall && (
        <div className="alert alert-primary d-flex flex-wrap align-items-center justify-content-between gap-3">
          <div>
            <strong>Incoming bus call</strong>
            <div>Call #{incomingVideoCall.id} from Bus #{incomingVideoCall.caller_id}</div>
          </div>
          <div className="d-flex gap-2">
            <button type="button" className="btn btn-outline-secondary" onClick={rejectIncomingVideoCall} disabled={videoCallBusy}>
              Reject
            </button>
            <button type="button" className="btn btn-success" onClick={acceptIncomingVideoCall} disabled={videoCallBusy}>
              Accept
            </button>
          </div>
        </div>
      )}

      <div className="row g-4">
        <div className="col-12">
          <div className="card shadow-sm" style={{ borderRadius: 20, overflow: 'hidden' }}>
            <div
              className="card-header d-flex justify-content-between align-items-start"
              style={{
                padding: '14px 16px 12px',
                background: 'linear-gradient(180deg, #ffffff 0%, #f8fafc 100%)',
                borderBottom: '1px solid #e2e8f0',
              }}
            >
              <div>
                <strong style={{ display: 'block', fontSize: 18 }}>Remote bus video</strong>
                <div className="text-muted" style={{ fontSize: 12, marginTop: 2 }}>
                  {activeVideoCall ? `Call #${activeVideoCall.id}` : 'Waiting for active call'}
                </div>
              </div>
              {activeVideoCall && (
                <span className="badge bg-primary" style={{ textTransform: 'capitalize' }}>
                  {activeVideoCall.status}
                </span>
              )}
            </div>

            <div className="card-body" style={{ padding: 12 }}>
              <div
                style={{
                  position: 'relative',
                  width: '100%',
                  minHeight: 420,
                  height: 'min(72vh, 760px)',
                  background: '#0f172a',
                  borderRadius: 18,
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
                    right: 16,
                    top: 16,
                    width: 'clamp(120px, 18vw, 220px)',
                    aspectRatio: '3 / 4',
                    background: '#1e293b',
                    borderRadius: 14,
                    overflow: 'hidden',
                    border: '2px solid rgba(255,255,255,0.92)',
                    boxShadow: '0 16px 36px rgba(15,23,42,0.35)',
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
            </div>

            <div
              className="card-footer d-flex justify-content-between align-items-start flex-wrap"
              style={{ gap: 12, padding: 12, background: '#fff' }}
            >
              <div className="small text-muted" style={{ display: 'grid', gap: 6 }}>
                {activeVideoCall ? (
                  <>
                    <div>Call ID: #{activeVideoCall.id}</div>
                    <div>Caller: {activeVideoCall.caller_type} #{activeVideoCall.caller_id}</div>
                    <div>Callee: {activeVideoCall.callee_type} #{activeVideoCall.callee_id}</div>
                    <div>Offer: {activeVideoCall.has_offer ? 'yes' : 'no'} • Answer: {activeVideoCall.has_answer ? 'yes' : 'no'}</div>
                    <div>{videoCallStatus}</div>
                  </>
                ) : (
                  <div>No active call yet.</div>
                )}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
