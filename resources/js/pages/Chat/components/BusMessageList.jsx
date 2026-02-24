import React from 'react';
import { formatTime } from '../../../util/busFormatters';

export default function BusMessageList({
  loadingMsgs,
  activeThreadId,
  messages,
  isMine,
  getSenderLabel,
  authUser,
  bottomRef,
}) {
  return (
    <div className="bus-chat-messages chat-conversation py-3">
      <ul className="list-unstyled mb-0 pe-3">
        {loadingMsgs && (
          <li><div className="text-muted">Loading messages…</div></li>
        )}

        {!loadingMsgs && !!activeThreadId && !messages.length && (
          <li><div className="text-muted">No messages yet.</div></li>
        )}

        {!activeThreadId && (
          <li><div className="text-muted">Choose a bus to view messages.</div></li>
        )}

        {!loadingMsgs && messages.map((m) => {
          const mine = isMine(m);
          const senderLabel = getSenderLabel(m);

          return (
            <li key={String(m.id)} className={mine ? 'right' : ''}>
              <div className="conversation-list">
                <div className="d-flex">
                  {!mine && (
                    <div className="chat-avatar">
                      <img
                        src={m?.sender?.avatar_url || '/build/images/users/avatar-4.jpg'}
                        alt="avatar"
                        onError={(e) => (e.currentTarget.src = '/build/images/users/avatar-4.jpg')}
                      />
                    </div>
                  )}

                  <div className={mine ? 'flex-grow-0' : 'flex-grow-1'}>
                    <div className="ctext-wrap">
                      <div className="ctext-wrap-content">
                        {!mine && <div className="small text-muted mb-1">{senderLabel}</div>}

                        <p className="mb-0">{m.body}</p>

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
  );
}
