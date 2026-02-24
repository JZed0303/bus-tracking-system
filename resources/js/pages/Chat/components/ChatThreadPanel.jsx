import React, { useEffect, useMemo, useRef, useState } from 'react';
import axios from 'axios';

export default function ChatThreadPanel({ threadId, threadTitle, onThreadBump }) {
  const base = window.chatBase || '/admin/chat';
  const authUser = window.authUser || null;
  const myId = authUser?.id ?? null;

  const [meta, setMeta] = useState(null);
  const [messages, setMessages] = useState([]);
  const [text, setText] = useState('');
  const [loading, setLoading] = useState(false);
  const [metaLoading, setMetaLoading] = useState(false);

  // ✅ rerender when global presence changes
  const [, setPresenceTick] = useState(0);

  const bottomRef = useRef(null);
  const listRef = useRef(null);

  const title = useMemo(() => {
    if (!threadId) return 'Select a thread';
    if (!meta) return threadTitle || `Thread #${threadId}`;
    if (meta.context_type === 'bus_support') return `Bus #${meta.context_id} Support`;
    return meta.title || threadTitle || `Group Chat #${threadId}`;
  }, [meta, threadId, threadTitle]);

  const formatTime = (value) => {
    try {
      const d = new Date(value);
      if (Number.isNaN(d.getTime())) return String(value ?? '');
      return d.toLocaleString();
    } catch {
      return String(value ?? '');
    }
  };

  const scrollToBottom = (smooth = false) => {
    if (!bottomRef.current) return;
    bottomRef.current.scrollIntoView({ behavior: smooth ? 'smooth' : 'auto', block: 'end' });
  };

  const getSenderId = (m) => {
    if (m?.sender?.id != null) return m.sender.id;
    if (m?.sender_id != null) return m.sender_id;
    if (m?.user_id != null) return m.user_id;
    return null;
  };

  const getSenderLabel = (m) =>
    m?.sender?.label || m?.sender_name || m?.user?.full_name || m?.user_name || 'Unknown';

  const isMine = (m) => {
    const sid = getSenderId(m);
    if (sid == null || myId == null) return false;
    return String(sid) === String(myId);
  };

  // ✅ GLOBAL online status (works across all pages if initGlobalPresence runs in app.js)
  const isUserOnline = (userId) => {
    if (!userId) return false;
    return window.__onlineUsers?.has?.(`user:${userId}`) === true;
  };

  const fetchMeta = async (id) => {
    setMetaLoading(true);
    try {
      const res = await axios.get(`${base}/threads/${id}/meta`, {
        headers: { Accept: 'application/json' },
      });
      setMeta(res.data?.data ?? null);
    } catch (e) {
      console.error('[ChatThreadPanel] fetchMeta failed', e);
      setMeta(null);
    } finally {
      setMetaLoading(false);
    }
  };

  const fetchMessages = async (id, shouldScroll = true) => {
    setLoading(true);
    try {
      const res = await axios.get(`${base}/threads/${id}/messages`, {
        headers: { Accept: 'application/json' },
      });
      setMessages(res.data?.data ?? []);
      if (shouldScroll) setTimeout(() => scrollToBottom(false), 50);
    } catch (e) {
      console.error('[ChatThreadPanel] fetchMessages failed', e);
      setMessages([]);
    } finally {
      setLoading(false);
    }
  };

  // ✅ listen to presence changes (so dots update)
  useEffect(() => {
    const onChanged = () => setPresenceTick((t) => t + 1);
    window.addEventListener('presence:changed', onChanged);
    return () => window.removeEventListener('presence:changed', onChanged);
  }, []);

  // Reset state on thread change
  useEffect(() => {
    setMeta(null);
    setMessages([]);
    setText('');
  }, [threadId]);

  // Load meta/messages
  useEffect(() => {
    if (!threadId) return;
    fetchMeta(threadId);
    fetchMessages(threadId, true);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [threadId]);

  // Realtime per thread (messages only)
  useEffect(() => {
    if (!threadId) return;
    if (!window.Echo) return;

    const channelName = `chat.thread.${threadId}`;
    const channel = window.Echo.private(channelName);

    const handler = (payload) => {
      setMessages((prev) => {
        if (payload?.id != null && prev.some((m) => String(m.id) === String(payload.id))) return prev;
        return [...prev, payload];
      });

      onThreadBump?.(payload);
      setTimeout(() => scrollToBottom(true), 50);
    };

    channel.listen('.chat.message.sent', handler);

    return () => {
      channel.stopListening('.chat.message.sent', handler);
      window.Echo.leave(channelName);
    };
  }, [threadId, onThreadBump]);

  const send = async () => {
    const body = text.trim();
    if (!threadId || !myId || !body) return;

    setText('');

    try {
      await axios.post(
        `${base}/threads/${threadId}/messages`,
        { body },
        { headers: { Accept: 'application/json' } }
      );

      setTimeout(() => scrollToBottom(true), 50);
    } catch (e) {
      console.error('[ChatThreadPanel] send failed', e);
      setText(body);
    }
  };

  return (
    <div className="card-body d-flex flex-column p-0" style={{ height: '100%' }}>
      <style>{`
        .chat-avatar { position: relative; display: inline-block; }
        .chat-avatar .status-dot{
         position: absolute;
    bottom: 2px;
    right: 2px;
    top: 27px;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    border: 2px solid #fff;
        }
        .chat-avatar .status-dot.online{ background:#28a745; }
        .chat-avatar .status-dot.offline{ background:#adb5bd; }
      `}</style>

      {/* Header (fixed) */}
      <div className="pb-3 user-chat-border px-3 pt-3 chat-header">
        <div className="row align-items-center">
          <div className="col-md-6 col-6">
            <h5 className="font-size-15 mb-1 text-truncate">{title}</h5>
            <p className="text-muted text-truncate mb-0">
              <i className="mdi mdi-circle text-primary font-size-10 align-middle me-1"></i>
              {threadId ? (metaLoading ? 'Loading…' : 'Active now') : 'No thread selected'}
            </p>
          </div>

          <div className="col-md-6 col-6">
            <ul className="list-inline user-chat-nav text-end mb-0">
              <li className="list-inline-item m-0 d-none d-sm-inline-block">
                <div className="dropdown">
                  <button
                    className="btn nav-btn dropdown-toggle"
                    type="button"
                    data-bs-toggle="dropdown"
                    aria-haspopup="true"
                    aria-expanded="false"
                  >
                    <i className="mdi mdi-cog"></i>
                  </button>
                  <div className="dropdown-menu dropdown-menu-end">
                    <button
                      className="dropdown-item"
                      type="button"
                      onClick={() => threadId && fetchMessages(threadId, true)}
                      disabled={!threadId || loading}
                    >
                      {loading ? 'Loading…' : 'Refresh'}
                    </button>
                  </div>
                </div>
              </li>

              <li className="list-inline-item">
                <div className="dropdown">
                  <button
                    className="btn nav-btn dropdown-toggle"
                    type="button"
                    data-bs-toggle="dropdown"
                    aria-haspopup="true"
                    aria-expanded="false"
                  >
                    <i className="mdi mdi-dots-horizontal"></i>
                  </button>
                  <div className="dropdown-menu dropdown-menu-end">
                    <button className="dropdown-item" type="button" onClick={() => scrollToBottom(true)}>
                      Scroll to bottom
                    </button>
                  </div>
                </div>
              </li>
            </ul>
          </div>
        </div>

        {/* Participants */}
        {meta?.context_type !== 'bus_support' && meta?.participants?.length ? (
          <div className="mt-2">
            <div className="small text-muted mb-1">Participants</div>
            <div className="small d-flex flex-wrap gap-2">
              {meta.participants.map((p) => (
                <span key={p.user_id} className="badge bg-light text-dark border">
                  {p.full_name}
                </span>
              ))}
            </div>
          </div>
        ) : null}
      </div>

      {/* Messages area */}
      <div className="chat-conversation px-3 py-3">
        <ul ref={listRef} className="list-unstyled mb-0 pe-3 chat-messages">
          {!threadId && (
            <li>
              <div className="alert alert-info mb-0">Select a thread on the left to open the chat.</div>
            </li>
          )}

          {threadId && loading && (
            <li>
              <div className="text-muted">Loading…</div>
            </li>
          )}

          {threadId && !loading && !messages.length && (
            <li>
              <div className="text-muted">No messages yet.</div>
            </li>
          )}

          {threadId &&
            !loading &&
            messages.map((m) => {
              const mine = isMine(m);
              const senderLabel = getSenderLabel(m);
              const sid = getSenderId(m);
              const senderOnline = isUserOnline(sid);

              return (
                <li
                  key={m.id != null ? String(m.id) : `${m.created_at}-${sid ?? 'x'}`}
                  className={mine ? 'right' : ''}
                >
                  <div className="conversation-list">
                    <div className="d-flex">
                      {!mine && (
                        <div className="chat-avatar">
                          <img
                            src={m?.sender?.avatar_url || '/build/images/users/avatar-4.jpg'}
                            alt="avatar"
                            onError={(e) => (e.currentTarget.src = '/build/images/users/avatar-4.jpg')}
                          />
                          <span className={`status-dot ${senderOnline ? 'online' : 'offline'}`} />
                        </div>
                      )}

                      <div className={mine ? 'flex-grow-0' : 'flex-grow-1'}>
                        <div className="ctext-wrap">
                          <div className="ctext-wrap-content">
                            {!mine && <div className="small text-muted mb-1">{senderLabel}</div>}

                            <p className="mb-0" style={{ whiteSpace: 'pre-wrap', wordBreak: 'break-word' }}>
                              {m.body}
                            </p>

                            <p className="chat-time mb-0">
                              <i className="mdi mdi-clock-outline me-1"></i>
                              {formatTime(m.created_at)}
                            </p>
                          </div>
                        </div>
                      </div>

                      {mine && (
                        <div className="chat-avatar">
                          <img
                            src={authUser?.avatar_url || '/build/images/users/avatar-2.jpg'}
                            alt="me"
                            onError={(e) => (e.currentTarget.src = '/build/images/users/avatar-2.jpg')}
                          />
                          <span className="status-dot online" />
                        </div>
                      )}
                    </div>
                  </div>
                </li>
              );
            })}

          <li ref={bottomRef} />
        </ul>
      </div>

      {/* Composer */}
      <div className="px-lg-3 chat-composer pb-3">
        <div className="pt-2">
          <div className="row">
            <div className="col">
              <div className="position-relative">
                <input
                  type="text"
                  className="form-control chat-input"
                  placeholder="Enter Message..."
                  value={text}
                  onChange={(e) => setText(e.target.value)}
                  onKeyDown={(e) => {
                    if (e.key === 'Enter') {
                      e.preventDefault();
                      send();
                    }
                  }}
                  disabled={!myId || !threadId}
                />
              </div>

              {!myId && (
                <div className="small text-danger mt-1">authUser missing: set window.authUser in Blade.</div>
              )}
            </div>

            <div className="col-auto">
              <button
                type="button"
                className="btn btn-primary chat-send w-md waves-effect waves-light"
                onClick={send}
                disabled={!threadId || !myId || !text.trim()}
              >
                <span className="d-none d-sm-inline-block me-2">Send</span>
                <i className="mdi mdi-send"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
