import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import axios from 'axios';
import BusThreadItem from '../../../pages/Chat/components/BusThreadItem';
import BusChatRightChat from '../../../pages/Chat/components/BusChatRightChat';

export default function BusChatApp() {
  const base = window.chatBase || '/admin/bus-chat';
  const authUser = window.authUser || null;
  const myId = authUser?.id ?? null;

  const [threads, setThreads] = useState([]);
  const [loadingThreads, setLoadingThreads] = useState(false);
  const [activeThreadId, setActiveThreadId] = useState(null);

  const [messages, setMessages] = useState([]);
  const [loadingMsgs, setLoadingMsgs] = useState(false);
  const [text, setText] = useState('');

  const bottomRef = useRef(null);

  const activeThread = useMemo(
    () => threads.find((t) => String(t.id) === String(activeThreadId)) || null,
    [threads, activeThreadId]
  );

  const scrollToBottom = useCallback((smooth = false) => {
    if (!bottomRef.current) return;
    bottomRef.current.scrollIntoView({ behavior: smooth ? 'smooth' : 'auto', block: 'end' });
  }, []);

  const fetchThreads = useCallback(async () => {
    setLoadingThreads(true);
    try {
      const res = await axios.get(`${base}/threads`, { headers: { Accept: 'application/json' } });
      const list = res.data?.data ?? [];
      setThreads(list);

      setActiveThreadId((prev) => {
        if (prev && list.some((t) => String(t.id) === String(prev))) return prev;
        return list.length ? list[0].id : null;
      });
    } catch (e) {
      console.error('[BusChatApp] fetchThreads failed', e);
    } finally {
      setLoadingThreads(false);
    }
  }, [base]);

  const fetchMessages = useCallback(async (threadId) => {
    if (!threadId) {
      setMessages([]);
      return;
    }

    setLoadingMsgs(true);
    try {
      const res = await axios.get(`${base}/threads/${threadId}/messages`, {
        headers: { Accept: 'application/json' },
      });
      setMessages(res.data?.data ?? []);
      setTimeout(() => scrollToBottom(false), 50);
    } catch (e) {
      console.error('[BusChatApp] fetchMessages failed', e);
      setMessages([]);
    } finally {
      setLoadingMsgs(false);
    }
  }, [base, scrollToBottom]);

  useEffect(() => {
    fetchThreads();
  }, [fetchThreads]);

  useEffect(() => {
    fetchMessages(activeThreadId);
  }, [activeThreadId, fetchMessages]);

  useEffect(() => {
    if (!window.Echo || !activeThreadId) return;

    const channelName = `chat.thread.${activeThreadId}`;
    const channel = window.Echo.private(channelName);

    const onMessage = (payload) => {
      setMessages((prev) => {
        if (prev.some((m) => Number(m.id) === Number(payload.id))) return prev;
        return [...prev, payload];
      });

      setThreads((prev) => {
        const idx = prev.findIndex((t) => String(t.id) === String(activeThreadId));
        if (idx === -1) return prev;

        const next = [...prev];
        const old = next[idx];

        next[idx] = {
          ...old,
          latest_message: payload,
          updated_at: payload.created_at ?? old.updated_at,
        };

        return next;
      });

      setTimeout(() => scrollToBottom(true), 40);
    };

    channel.listen('.chat.message.sent', onMessage);

    return () => {
      channel.stopListening('.chat.message.sent', onMessage);
      window.Echo.leave(channelName);
    };
  }, [activeThreadId, scrollToBottom]);

  const onSend = async () => {
    const body = text.trim();
    if (!activeThreadId || !body) return;

    setText('');

    try {
      await axios.post(
        `${base}/threads/${activeThreadId}/messages`,
        { body },
        { headers: { Accept: 'application/json' } }
      );
    } catch (e) {
      console.error('[BusChatApp] send failed', e);
      setText(body);
    }
  };

  const isMine = (m) => {
    if (!myId) return false;
    const sid = m?.sender?.id ?? m?.sender_id ?? null;
    if (sid == null) return false;
    return String(sid) === String(myId);
  };

  const getSenderLabel = (m) => m?.sender?.label || 'Unknown';

  const getSubtitle = (t) => t?.latest_message?.body || 'No messages yet.';

  return (
    <div className="d-lg-flex bus-chat-shell" style={{ height: 'calc(100vh - 170px)' }}>
      <div className="chat-leftsidebar me-4" style={{ width: 360, minWidth: 320 }}>
        <div className="card mb-0 h-100">
          <div className="card-body d-flex flex-column">
            <div className="d-flex justify-content-between align-items-center mb-3">
              <h5 className="mb-0">Bus Threads</h5>
              <button
                className="btn btn-sm btn-outline-secondary"
                type="button"
                onClick={fetchThreads}
                disabled={loadingThreads}
              >
                {loadingThreads ? 'Loading...' : 'Refresh'}
              </button>
            </div>

            <div style={{ overflowY: 'auto' }}>
              <ul className="list-unstyled chat-list mb-0">
                {!loadingThreads && !threads.length && (
                  <li><div className="text-muted small px-2">No bus chats yet.</div></li>
                )}

                {threads.map((t) => (
                  <BusThreadItem
                    key={t.id}
                    t={t}
                    active={String(t.id) === String(activeThreadId)}
                    subtitle={getSubtitle(t)}
                    onSelect={() => setActiveThreadId(t.id)}
                  />
                ))}
              </ul>
            </div>
          </div>
        </div>
      </div>

      <BusChatRightChat
        activeThread={activeThread}
        activeThreadId={activeThreadId}
        loadingMsgs={loadingMsgs}
        onRefreshMessages={() => fetchMessages(activeThreadId)}
        onScrollBottom={() => scrollToBottom(true)}
        messages={messages}
        isMine={isMine}
        getSenderLabel={getSenderLabel}
        authUser={authUser}
        bottomRef={bottomRef}
        text={text}
        setText={setText}
        onSend={onSend}
      />
    </div>
  );
}
