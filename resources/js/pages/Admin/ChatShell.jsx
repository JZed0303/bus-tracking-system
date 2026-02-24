// resources/js/pages/Admin/ChatShell.jsx
import React, { useEffect, useState, useCallback, useMemo } from 'react';
import axios from 'axios';
import ChatLayout from '../Chat/components/ChatLayout';
import { playChatSound } from '../../chatSoundHelper';

export default function ChatShell() {
  const base = window.chatBase || '/admin/chat';
  const authUser = window.authUser || null;

  const [threads, setThreads] = useState([]);
  const [threadsLoading, setThreadsLoading] = useState(false);

  // Active per tab
  const [busThreadId, setBusThreadId] = useState(null);
  const [groupThreadId, setGroupThreadId] = useState(null);

  // Detect group vs bus
  const isGroupThread = (t) =>
    Boolean(t.is_group || t.type === 'group' || t.thread_type === 'group');

  const isBusThread = (t) => !isGroupThread(t); // adjust if you have explicit bus flag

  const fetchThreads = useCallback(async () => {
    setThreadsLoading(true);
    try {
      const res = await axios.get(`${base}/threads`, {
        headers: { Accept: 'application/json' },
      });

      const list = res.data?.data ?? [];
      setThreads(list);

      const busList = list.filter((t) => isBusThread(t));
      const groupList = list.filter((t) => isGroupThread(t));

      setBusThreadId((prev) => {
        if (
          (!prev && busList.length) ||
          (prev && !busList.some((t) => String(t.id) === String(prev)))
        ) {
          return busList.length ? busList[0].id : null;
        }
        return prev;
      });

      setGroupThreadId((prev) => {
        if (
          (!prev && groupList.length) ||
          (prev && !groupList.some((t) => String(t.id) === String(prev)))
        ) {
          return groupList.length ? groupList[0].id : null;
        }
        return prev;
      });
    } catch (e) {
      console.error('[ChatShell] fetchThreads failed', e);
    } finally {
      setThreadsLoading(false);
    }
  }, [base]);

  const busThreads = useMemo(() => threads.filter(isBusThread), [threads]);
  const groupThreads = useMemo(() => threads.filter(isGroupThread), [threads]);

  // Bump thread when a message arrives
  const bumpThreadFromMessage = useCallback((payload) => {
    if (!payload?.thread_id) return;

    const tid = payload.thread_id;

    setThreads((prev) => {
      const idx = prev.findIndex((t) => String(t.id) === String(tid));
      if (idx === -1) return prev;

      const next = [...prev];
      const old = next[idx];

      const updated = {
        ...old,
        last_message_body: payload.body ?? old.last_message_body,
        last_message_sender_id:
          payload.sender?.id ?? payload.sender_id ?? old.last_message_sender_id ?? null,
        last_message_created_at: payload.created_at ?? old.last_message_created_at,
        updated_at: payload.created_at ?? old.updated_at,
      };

      next.splice(idx, 1);
      next.unshift(updated);
      return next;
    });
  }, []);

  useEffect(() => {
    fetchThreads();
  }, [fetchThreads]);

  /**
   * 🔔 Realtime + sound
   *
   * - Subscribes to the current busThreadId and groupThreadId
   * - On `.chat.message.sent`:
   *    - bumps the thread in the list
   *    - plays sound if sender !== current user
   */
 useEffect(() => {
  if (!window.Echo) {
    console.warn('[ChatShell] Echo not available');
    return;
  }
  if (!authUser) {
    console.warn('[ChatShell] authUser not available');
    return;
  }

  const myId = parseInt(authUser.id, 10);
  const subscriptions = [];

  const attach = (threadId, label) => {
    if (!threadId) return;

    const channelName = `chat.thread.${threadId}`;
    console.log(`[ChatShell] Subscribing to ${channelName} (${label})`);

    const channel = window.Echo.private(channelName);

    channel.listen('.chat.message.sent', (payload) => {
      console.log('[ChatShell] Incoming payload on', channelName, payload);

      // Bump thread in list
      bumpThreadFromMessage(payload);

      const senderId = parseInt(
        payload.sender_id ??
          payload.senderId ??
          payload.sender?.id ??
          0,
        10
      );

      // Skip sound only if we are sure it's my own message
      if (senderId && senderId === myId) {
        console.log('[ChatShell] Skip sound (own message)', { senderId, myId });
        return;
      }

      // 🔔 Play sound for messages from others
      playChatSound();
    });

    subscriptions.push({ channelName, channel });
  };

  attach(busThreadId, 'bus');
  attach(groupThreadId, 'group');

  return () => {
    subscriptions.forEach(({ channelName, channel }) => {
      console.log('[ChatShell] Cleaning up channel', channelName);
      channel.stopListening('.chat.message.sent');
    });
  };
}, [authUser, busThreadId, groupThreadId, bumpThreadFromMessage]);

  return (
    <ChatLayout
      busThreads={busThreads}
      groupThreads={groupThreads}
      busThreadId={busThreadId}
      groupThreadId={groupThreadId}
      threadsLoading={threadsLoading}
      onRefreshThreads={() => fetchThreads()}
      onSelectThread={(id, tab) => {
        if (tab === 'bus') setBusThreadId(id);
        else setGroupThreadId(id);
      }}
      onThreadBump={bumpThreadFromMessage}
    />
  );
}
