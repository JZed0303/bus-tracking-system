import React, { useEffect, useState } from 'react';
import axios from 'axios';

export default function ChatIndex() {
  const [threads, setThreads] = useState([]);
  const [loading, setLoading] = useState(false);

  const fetchThreads = async () => {
    setLoading(true);
    try {
      const res = await axios.get('/admin/chat/threads', {
        headers: { Accept: 'application/json' },
      });
      setThreads(res.data?.data ?? []);
    } catch (e) {
      console.error('[ChatIndex] fetchThreads failed', e);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchThreads();
  }, []);

  return (
    <div className="row">
      <div className="col-lg-6">
        <div className="d-flex justify-content-between align-items-center mb-2">
          <h5 className="mb-0">Your Threads</h5>
          <button className="btn btn-sm btn-outline-secondary" onClick={fetchThreads} disabled={loading}>
            {loading ? 'Loading…' : 'Refresh'}
          </button>
        </div>

        <div className="list-group light-red-list-group">
          {threads.map((t) => (
            <div
              key={t.id}
              className="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
            >
              <div className="me-2">
                <div className="fw-semibold">{t.title}</div>
                <div className="small text-muted">
                  Participants: {t.participants_count} • Updated: {t.updated_at ?? '—'}
                </div>
              </div>

              <a className="btn btn-sm btn-outline-primary" href={`/admin/chat/${t.id}`}>
                Open Chat
              </a>
            </div>
          ))}

          {!threads.length && !loading && (
            <div className="text-muted small">No chats yet.</div>
          )}
        </div>
      </div>

      <div className="col-lg-6">
        <div className="alert alert-info mb-0">
          Select a thread and click <strong>Open Chat</strong>.
          <br />
          If you don’t see a thread here, you are not a participant of that thread.
        </div>
      </div>
    </div>
  );
}
