import React, { useEffect, useMemo, useState } from 'react';
import axios from 'axios';

export default function ChatThreadPage({ threadId }) {
  const [meta, setMeta] = useState(null);
  const [messages, setMessages] = useState([]);
  const [text, setText] = useState('');
  const [loading, setLoading] = useState(false);

  // ✅ Company route prefix
  const base = '/company/chat';

  const title = useMemo(() => {
    if (!meta) return `Thread #${threadId}`;
    if (meta.context_type === 'bus_support') return `Bus #${meta.context_id} Support`;
    return meta.title || `Group Chat #${threadId}`;
  }, [meta, threadId]);

  const fetchMeta = async () => {
    const res = await axios.get(`${base}/threads/${threadId}/meta`, {
      headers: { Accept: 'application/json' },
    });
    setMeta(res.data?.data ?? null);
  };

  const fetchMessages = async () => {
    setLoading(true);
    try {
      const res = await axios.get(`${base}/threads/${threadId}/messages`, {
        headers: { Accept: 'application/json' },
      });
      setMessages(res.data?.data ?? []);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchMeta();
    fetchMessages();
  }, [threadId]);

  // Realtime (same channel name; your backend broadcasts by threadId)
  useEffect(() => {
    if (!window.Echo) return;

    const channelName = `chat.thread.${threadId}`;
    const channel = window.Echo.private(channelName);

    const handler = (payload) => {
      setMessages((prev) => {
        if (prev.some((m) => Number(m.id) === Number(payload.id))) return prev;
        return [...prev, payload];
      });
    };

    channel.listen('.chat.message.sent', handler);

    return () => {
      channel.stopListening('.chat.message.sent', handler);
      window.Echo.leave(channelName);
    };
  }, [threadId]);

  const send = async () => {
    const body = text.trim();
    if (!body) return;

    // optimistic clear
    setText('');

    await axios.post(
      `${base}/threads/${threadId}/messages`,
      { body },
      { headers: { Accept: 'application/json' } }
    );
  };

  return (
    <div className="card">
      <div className="card-body" style={{ minHeight: '75vh' }}>
        <div className="d-flex justify-content-between align-items-start mb-2">
          <div>
            <h5 className="mb-0">{title}</h5>
            <div className="small text-muted">
              Thread #{threadId}
              {meta?.type ? ` • ${meta.type}` : ''}
              {meta?.context_type ? ` • ${meta.context_type}` : ''}
            </div>
          </div>

          <button className="btn btn-sm btn-outline-secondary" onClick={fetchMessages}>
            Refresh
          </button>
        </div>

        {meta?.context_type !== 'bus_support' && meta?.participants?.length ? (
          <div className="border rounded p-2 mb-3">
            <div className="small text-muted mb-1">Participants</div>
            <div className="small">
              {meta.participants.map((p) => (
                <span key={p.user_id} className="me-2">
                  {p.full_name}
                </span>
              ))}
            </div>
          </div>
        ) : null}

        <div className="border rounded p-3 mb-3" style={{ height: '52vh', overflowY: 'auto' }}>
          {loading && <div className="text-muted">Loading…</div>}
          {!loading && !messages.length && <div className="text-muted">No messages yet.</div>}

          {!loading &&
            messages.map((m) => (
              <div key={`msg-${m.id}`} className="mb-3">
                <div className="small text-muted">
                  <span className="fw-semibold">{m.sender?.label ?? 'Unknown'}</span>
                  {' • '}
                  {m.created_at}
                </div>
                <div>{m.body}</div>
              </div>
            ))}
        </div>

        <div className="input-group">
          <input
            className="form-control"
            placeholder="Type a message…"
            value={text}
            onChange={(e) => setText(e.target.value)}
            onKeyDown={(e) => {
              // prevent newline/submit oddities
              if (e.key === 'Enter') {
                e.preventDefault();
                send();
              }
            }}
          />
          <button className="btn btn-primary" onClick={send}>
            Send
          </button>
        </div>
      </div>
    </div>
  );
}
