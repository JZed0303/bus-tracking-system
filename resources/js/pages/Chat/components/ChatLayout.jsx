// resources/js/pages/Chat/components/ChatLayout.jsx
import React, { useEffect, useMemo, useState } from 'react';
import ChatThreadPanel from './ChatThreadPanel';

export default function ChatLayout({
  busThreadId,
  groupThreadId,
  threadId, // optional legacy
  busThreads = [],
  groupThreads = [],
  threadsLoading = false,
  onSelectThread, // (id, tab) => void
  onRefreshThreads, // (tab) => void
  onThreadBump,
  defaultTab = 'bus',
}) {
  const authUser = window.authUser || null;
  const myId = authUser?.id ?? null;

  const [activeTab, setActiveTab] = useState(defaultTab);

  // ✅ rerender when global presence changes
  const [, setPresenceTick] = useState(0);
  useEffect(() => {
    const onChanged = () => setPresenceTick((t) => t + 1);
    window.addEventListener('presence:changed', onChanged);
    return () => window.removeEventListener('presence:changed', onChanged);
  }, []);

  const shortTime = (value) => {
    try {
      const d = new Date(value);
      if (Number.isNaN(d.getTime())) return '';
      return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    } catch {
      return '';
    }
  };

  const getLastMessageBody = (t) => {
    const body =
      (typeof t.last_message === 'string' ? t.last_message : null) ||
      (typeof t.last_message_body === 'string' ? t.last_message_body : null) ||
      (typeof t.latest_message_body === 'string' ? t.latest_message_body : null) ||
      t.last_message?.body ||
      t.latest_message?.body;

    return body?.trim() ? body : null;
  };

  const getLastMessageTime = (t) =>
    t.last_message_created_at || t.last_message?.created_at || t.latest_message?.created_at || t.updated_at || null;

  const threadSubtitle = (t) => getLastMessageBody(t) || 'No messages yet.';

  // -----------------------------
  // ✅ GLOBAL PRESENCE HELPERS
  // -----------------------------
  const isOnlineKey = (key) => window.__onlineUsers?.has?.(String(key)) === true;

  /**
   * Extract all user ids that represent "participants" for this thread.
   * This must match your API shape. I included several common shapes.
   *
   * Goal for GROUP: if ANY participant (except me) is online => dot online.
   * Goal for 1-to-1: if counterparty is online => dot online.
   */
  const getThreadParticipantUserIds = (t) => {
    // Common 1-to-1 fields
    const oneToOneCandidates = [
      t.other_user_id,
      t.counterparty_user_id,
      t.peer_user_id,
      t.user_id, // sometimes API stores "other user" here
    ].filter((x) => x != null);

    // Common group fields
    const fromParticipantsArray =
      Array.isArray(t.participants)
        ? t.participants
            .map((p) => p?.user_id ?? p?.id)
            .filter((x) => x != null)
        : [];

    // Some APIs return: users: [{id:..}]
    const fromUsersArray =
      Array.isArray(t.users)
        ? t.users.map((u) => u?.id).filter((x) => x != null)
        : [];

    // Merge + uniq
    const merged = [...oneToOneCandidates, ...fromParticipantsArray, ...fromUsersArray]
      .map((x) => String(x));

    return Array.from(new Set(merged));
  };

  /**
   * Presence rule:
   * - GROUP: online if ANY participant (excluding me) is online
   * - 1-to-1: online if the other user is online
   *
   * If your thread represents a BUS actor, this will stay offline (user-only presence).
   */
  const presenceValue = (t) => {
    const ids = getThreadParticipantUserIds(t);

    // If we have a participants array, treat as group-like:
    if (Array.isArray(t.participants) || Array.isArray(t.users)) {
      const others = ids.filter((id) => (myId != null ? String(id) !== String(myId) : true));
      const anyOnline = others.some((id) => isOnlineKey(`user:${id}`));
      return anyOnline ? 'online' : 'offline';
    }

    // 1-to-1 fallback: choose the first id that is not me
    const otherId = ids.find((id) => (myId != null ? String(id) !== String(myId) : true)) || null;
    return otherId && isOnlineKey(`user:${otherId}`) ? 'online' : 'offline';
  };

  const presenceClass = (t) => `presence-${presenceValue(t)}`;

  const authPresenceLabel = () => {
    if (!myId) return 'Offline';
    return isOnlineKey(`user:${myId}`) ? 'Active' : 'Offline';
  };

  // Pick list based on tab
  const threads = activeTab === 'bus' ? busThreads : groupThreads;

  // Pick active thread id based on tab (prefer this), fallback to threadId
  const activeThreadId = threadId ?? (activeTab === 'bus' ? busThreadId : groupThreadId) ?? null;

  const selectedTitle = useMemo(() => {
    const t = threads.find((x) => String(x.id) === String(activeThreadId));
    return t?.title || (activeThreadId ? `Thread #${activeThreadId}` : 'Select a thread');
  }, [threads, activeThreadId]);

  return (
    <div className="d-lg-flex chat-shell" style={{ height: 'calc(100vh - 170px)' }}>
      {/* LEFT */}
      <div className="chat-leftsidebar me-4" style={{ width: 360, minWidth: 320 }}>
        <div className="card mb-0">
          <div className="card-body pt-0">
            <div className="py-3 border-bottom">
              <div className="d-flex">
                <div className="align-self-center me-3">
                  <img
                    src={authUser?.avatar_url || '/build/images/users/avatar-2.jpg'}
                    className="avatar-xs rounded-circle"
                    alt="me"
                    onError={(e) => (e.currentTarget.src = '/build/images/users/avatar-2.jpg')}
                  />
                </div>

                <div className="flex-1">
                  <h5 className="font-size-15 mb-1">{authUser?.full_name || authUser?.name || '—'}</h5>
                  <p className="text-muted mb-0">
                    <i className="mdi mdi-circle text-primary font-size-10 align-middle me-1"></i>
                    {authPresenceLabel()}
                  </p>
                </div>

                <div>
                  <button
                    className="btn btn-sm btn-outline-secondary"
                    type="button"
                    onClick={() => onRefreshThreads?.(activeTab)}
                    disabled={threadsLoading}
                    title="Refresh threads"
                  >
                    {threadsLoading ? 'Loading…' : 'Refresh'}
                  </button>
                </div>
              </div>
            </div>

            {/* TABS */}
            <div className="pt-3">
              <ul className="nav nav-pills nav-justified" role="tablist">
                <li className="nav-item" role="presentation">
                  <button
                    type="button"
                    className={`nav-link ${activeTab === 'bus' ? 'active' : ''}`}
                    role="tab"
                    aria-selected={activeTab === 'bus'}
                    onClick={() => setActiveTab('bus')}
                  >
                    Bus Chat {!!busThreads.length && <span className="badge bg-secondary ms-2">{busThreads.length}</span>}
                  </button>
                </li>

                <li className="nav-item" role="presentation">
                  <button
                    type="button"
                    className={`nav-link ${activeTab === 'group' ? 'active' : ''}`}
                    role="tab"
                    aria-selected={activeTab === 'group'}
                    onClick={() => setActiveTab('group')}
                  >
                    Group Chat {!!groupThreads.length && <span className="badge bg-secondary ms-2">{groupThreads.length}</span>}
                  </button>
                </li>
              </ul>
            </div>
              <style>
                {`
                  .nav-pills .nav-link.active {
                    background-color: #f8d7da !important;
                    color: #842029 !important;
                    border-color: #f5c2c7 !important;
                  }

                  .nav-pills .nav-link:hover {
                    background-color: #f8d7da;
                    color: #842029;
                  }
                `}
              </style>

            {/* Search UI (optional) */}
            <div className="py-2 mt-3">
              <div className="search-box">
                <div className="position-relative">
                  <input type="text" className="form-control border" placeholder="Search..." disabled />
                  <i className="ri-search-line search-icon"></i>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* THREAD LIST */}
        <div style={{ height: 'calc(100% - 210px)', overflowY: 'auto' }} className="pt-3">
          <div className="px-1">
            <h5 className="font-size-14 mb-3">{activeTab === 'bus' ? 'Bus Inbox' : 'Group Inbox'}</h5>

            <ul className="list-unstyled chat-list mb-0">
              {threadsLoading && (
                <li>
                  <div className="text-muted small px-2">Loading threads…</div>
                </li>
              )}

              {!threadsLoading && !threads.length && (
                <li>
                  <div className="text-muted small px-2">
                    {activeTab === 'bus' ? 'No bus chats yet.' : 'No group chats yet.'}
                  </div>
                </li>
              )}

              {!threadsLoading &&
                threads.map((t) => {
                  const active = String(t.id) === String(activeThreadId);

                  return (
                    <li key={t.id} className={active ? 'active' : ''}>
                      <a
                        href="#"
                        onClick={(e) => {
                          e.preventDefault();
                          onSelectThread?.(t.id, activeTab);
                        }}
                      >
                        <div className="d-flex">
                          {/* ✅ Presence class now computed from GLOBAL presence */}
                          <div className={`user-img ${presenceClass(t)} align-self-center me-3`}>
                            <img
                              src={t.avatar_url || '/build/images/users/avatar-4.jpg'}
                              className="rounded-circle avatar-xs"
                              alt="thread"
                              onError={(e) => (e.currentTarget.src = '/build/images/users/avatar-4.jpg')}
                            />
                         
                          </div>

                          <div className="flex-1 overflow-hidden">
                            <h5 className="text-truncate font-size-14 mb-1">{t.title || `Thread #${t.id}`}</h5>
                            <p className="text-truncate mb-0">{threadSubtitle(t)}</p>
                          </div>

                          <div className="font-size-11">{shortTime(getLastMessageTime(t))}</div>
                        </div>
                      </a>
                    </li>
                  );
                })}
            </ul>
          </div>
        </div>
      </div>

      {/* RIGHT */}
      <div className="w-100 user-chat card mb-0" style={{ minHeight: 0 }}>
        <ChatThreadPanel
          threadId={activeThreadId}
          threadTitle={selectedTitle}
          onThreadBump={(payload) => onThreadBump?.({ tab: activeTab, threadId: activeThreadId, ...payload })}
        />
      </div>
    </div>
  );
}
