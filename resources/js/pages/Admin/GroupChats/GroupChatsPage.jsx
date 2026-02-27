import React, { useEffect, useMemo, useRef, useState } from 'react';
import axios from 'axios';

export default function GroupChatsPage() {
  const [threads, setThreads] = useState([]);
  const [selectedThreadId, setSelectedThreadId] = useState(null);

  // Create group
  const [title, setTitle] = useState('');
  const [createSearch, setCreateSearch] = useState('');
  const [createUserResults, setCreateUserResults] = useState([]);
  const [createSelectedUserIds, setCreateSelectedUserIds] = useState([]);

  // Manage participants
  const [threadDetail, setThreadDetail] = useState(null);
  const [addSearch, setAddSearch] = useState('');
  const [addUserResults, setAddUserResults] = useState([]);
  const [addSelectedUserIds, setAddSelectedUserIds] = useState([]);

  const [loadingThreads, setLoadingThreads] = useState(false);
  const [loadingDetail, setLoadingDetail] = useState(false);
  const [creating, setCreating] = useState(false);
  const [adding, setAdding] = useState(false);

  // For debounce + cancel
  const createSearchTimer = useRef(null);
  const addSearchTimer = useRef(null);

  const fetchThreads = async () => {
    setLoadingThreads(true);
    try {
      const res = await axios.get('/admin/group-chats/threads');
      setThreads(res.data?.data ?? []);
    } finally {
      setLoadingThreads(false);
    }
  };

  const fetchThreadDetail = async (id) => {
    if (!id) return;
    setLoadingDetail(true);
    try {
      const res = await axios.get(`/admin/group-chats/threads/${id}`);
      setThreadDetail(res.data?.data ?? null);
    } finally {
      setLoadingDetail(false);
    }
  };

  const fetchUsers = async (q, setter, signal) => {
    const query = (q ?? '').trim();
    if (!query) {
      setter([]);
      return;
    }
    const res = await axios.get('/admin/group-chats/users', {
      params: { q: query },
      signal,
    });
    setter(res.data?.data ?? []);
  };

  // initial load
  useEffect(() => {
    fetchThreads();
  }, []);

  // thread detail when selected
  useEffect(() => {
    if (!selectedThreadId) {
      setThreadDetail(null);
      return;
    }
    fetchThreadDetail(selectedThreadId);
  }, [selectedThreadId]);

  // Debounced search for CREATE list
  useEffect(() => {
    const controller = new AbortController();

    if (createSearchTimer.current) clearTimeout(createSearchTimer.current);
    createSearchTimer.current = setTimeout(() => {
      fetchUsers(createSearch, setCreateUserResults, controller.signal).catch((e) => {
        if (e?.name !== 'CanceledError' && e?.code !== 'ERR_CANCELED') throw e;
      });
    }, 300);

    return () => {
      controller.abort();
      if (createSearchTimer.current) clearTimeout(createSearchTimer.current);
    };
  }, [createSearch]);

  // Debounced search for ADD list
  useEffect(() => {
    const controller = new AbortController();

    if (addSearchTimer.current) clearTimeout(addSearchTimer.current);
    addSearchTimer.current = setTimeout(() => {
      fetchUsers(addSearch, setAddUserResults, controller.signal).catch((e) => {
        if (e?.name !== 'CanceledError' && e?.code !== 'ERR_CANCELED') throw e;
      });
    }, 300);

    return () => {
      controller.abort();
      if (addSearchTimer.current) clearTimeout(addSearchTimer.current);
    };
  }, [addSearch]);

  const canCreate = title.trim().length > 0 && createSelectedUserIds.length > 0;

  const createGroup = async () => {
    if (!canCreate) return;

    setCreating(true);
    try {
      const payload = { title: title.trim(), user_ids: createSelectedUserIds };
      const res = await axios.post('/admin/group-chats/threads', payload);

      setTitle('');
      setCreateSelectedUserIds([]);
      setCreateSearch('');
      setCreateUserResults([]);

      await fetchThreads();

      const newId = res.data?.data?.id;
      if (newId) setSelectedThreadId(newId);
    } finally {
      setCreating(false);
    }
  };

  const addMore = async () => {
    if (!selectedThreadId || !addSelectedUserIds.length) return;

    setAdding(true);
    try {
      await axios.post(`/admin/group-chats/threads/${selectedThreadId}/participants`, {
        user_ids: addSelectedUserIds,
      });

      setAddSelectedUserIds([]);
      await fetchThreadDetail(selectedThreadId);
    } finally {
      setAdding(false);
    }
  };

  const removeUser = async (userId) => {
    if (!selectedThreadId) return;
    await axios.delete(`/admin/group-chats/threads/${selectedThreadId}/participants/${userId}`);
    await fetchThreadDetail(selectedThreadId);
  };

  const createSelectedUsers = useMemo(() => {
    const map = new Map(createUserResults.map((u) => [u.id, u]));
    return createSelectedUserIds.map((id) => map.get(id)).filter(Boolean);
  }, [createSelectedUserIds, createUserResults]);

  const addSelectedUsers = useMemo(() => {
    const map = new Map(addUserResults.map((u) => [u.id, u]));
    return addSelectedUserIds.map((id) => map.get(id)).filter(Boolean);
  }, [addSelectedUserIds, addUserResults]);

  return (
    <div className="row">
      {/* LEFT: Threads + Create */}
      <div className="col-md-4">
        <div className="card mb-3">
          <div className="card-body">
            <div className="d-flex justify-content-between align-items-center">
              <h5 className="mb-0">Group Chats</h5>
              <button
                className="btn btn-sm btn-outline-secondary"
                onClick={fetchThreads}
                disabled={loadingThreads}
              >
                {loadingThreads ? 'Loading…' : 'Refresh'}
              </button>
            </div>

            <div className="list-group mt-3 light-red-list-group">
              {threads.map((t) => (
                <button
                  key={t.id}
                  className={`list-group-item list-group-item-action ${
                    Number(t.id) === Number(selectedThreadId) ? 'active' : ''
                  }`}
                  onClick={() => setSelectedThreadId(t.id)}
                >
                  <div className="fw-semibold">{t.title}</div>
                  <div className="small text-truncate">Participants: {t.participants_count}</div>
                </button>
              ))}
              {!threads.length && <div className="text-muted small">No group chats yet.</div>}
            </div>
          </div>
        </div>

        <div className="card">
          <div className="card-body">
            <h6>Create Group</h6>

            <input
              className="form-control mb-2"
              placeholder="Group title"
              value={title}
              onChange={(e) => setTitle(e.target.value)}
            />

            <input
              className="form-control mb-2"
              placeholder="Search users by name/email…"
              value={createSearch}
              onChange={(e) => setCreateSearch(e.target.value)}
            />

            <div style={{ maxHeight: 220, overflowY: 'auto' }} className="border rounded p-2 mb-2">
              {createUserResults.map((u) => {
                const checked = createSelectedUserIds.includes(u.id);
                return (
                  <label
                    key={u.id}
                    className="d-flex align-items-center gap-2 mb-1"
                    style={{ cursor: 'pointer' }}
                  >
                    <input
                      type="checkbox"
                      checked={checked}
                      onChange={() => {
                        setCreateSelectedUserIds((prev) =>
                          checked ? prev.filter((x) => x !== u.id) : [...prev, u.id]
                        );
                      }}
                    />
                    <span className="small">
                      <span className="fw-semibold">{u.full_name}</span> — {u.email}
                    </span>
                  </label>
                );
              })}
              {!createUserResults.length && (
                <div className="text-muted small">
                  {createSearch.trim() ? 'No users found.' : 'Type to search users.'}
                </div>
              )}
            </div>

            <button
              className="btn btn-primary w-100"
              onClick={createGroup}
              disabled={!canCreate || creating}
            >
              {creating ? 'Creating…' : 'Create Group Chat'}
            </button>


            {!!createSelectedUsers.length && (
              <div className="mt-2 small text-muted">
                Selected: {createSelectedUsers.map((u) => u.full_name).join(', ')}
              </div>
            )}
          </div>
        </div>
      </div>

      {/* RIGHT: Manage participants */}
      <div className="col-md-8">
        <div className="card">
          <div className="card-body">
            {!selectedThreadId && (
              <div className="text-muted">Select a group chat to manage participants.</div>
            )}

            {selectedThreadId && loadingDetail && <div className="text-muted">Loading thread…</div>}

            {selectedThreadId && !loadingDetail && threadDetail && (
              <>
             <div className="d-flex justify-content-between align-items-center">
  <div>
    <h5 className="mb-0">{threadDetail.title}</h5>
    <div className="text-muted small">Thread #{threadDetail.id}</div>
  </div>

  <a
    className="btn btn-sm btn-outline-primary"
    href={`/admin/chat/${threadDetail.id}`}
  >
    Open Chat
  </a>
</div>


                <hr />

                <h6 className="mb-2">Participants</h6>
                <div className="list-group mb-3">
                  {threadDetail.participants.map((p) => (
                    <div
                      key={p.user_id}
                      className="list-group-item d-flex justify-content-between align-items-center"
                    >
                      <div>
                        <div className="fw-semibold">{p.full_name ?? `User #${p.user_id}`}</div>
                        <div className="small text-muted">{p.email ?? ''} • {p.role}</div>
                      </div>
                      <button
                        className="btn btn-sm btn-outline-danger"
                        onClick={() => removeUser(p.user_id)}
                        disabled={p.role === 'owner'}
                        title={p.role === 'owner' ? 'Owner cannot be removed' : 'Remove'}
                      >
                        Remove
                      </button>
                    </div>
                  ))}
                  {!threadDetail.participants?.length && (
                    <div className="text-muted small">No participants.</div>
                  )}
                </div>

                <h6 className="mb-2">Add Participants</h6>

                <input
                  className="form-control mb-2"
                  placeholder="Search users by name/email…"
                  value={addSearch}
                  onChange={(e) => setAddSearch(e.target.value)}
                />

                <div style={{ maxHeight: 220, overflowY: 'auto' }} className="border rounded p-2 mb-2">
                  {addUserResults.map((u) => {
                    const checked = addSelectedUserIds.includes(u.id);
                    return (
                      <label
                        key={u.id}
                        className="d-flex align-items-center gap-2 mb-1"
                        style={{ cursor: 'pointer' }}
                      >
                        <input
                          type="checkbox"
                          checked={checked}
                          onChange={() => {
                            setAddSelectedUserIds((prev) =>
                              checked ? prev.filter((x) => x !== u.id) : [...prev, u.id]
                            );
                          }}
                        />
                        <span className="small">
                          <span className="fw-semibold">{u.full_name}</span> — {u.email}
                        </span>
                      </label>
                    );
                  })}
                  {!addUserResults.length && (
                    <div className="text-muted small">
                      {addSearch.trim() ? 'No users found.' : 'Type to search users.'}
                    </div>
                  )}
                </div>

                <button
                  className="btn btn-outline-primary"
                  onClick={addMore}
                  disabled={!addSelectedUserIds.length || adding}
                >
                  {adding ? 'Adding…' : 'Add Selected'}
                </button>

                {!!addSelectedUsers.length && (
                  <div className="mt-2 small text-muted">
                    Selected: {addSelectedUsers.map((u) => u.full_name).join(', ')}
                  </div>
                )}
              </>
            )}

            {selectedThreadId && !loadingDetail && !threadDetail && (
              <div className="text-muted">Thread not found.</div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
