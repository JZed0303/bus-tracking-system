import React from 'react';
import { formatBusDisplayName } from '../../../util/busFormatters';
import BusMessageList from './BusMessageList';
import BusChatInput from './BusChatInput';

export default function BusChatRightChat({
  activeThread,
  activeThreadId,
  loadingMsgs,
  onRefreshMessages,
  onScrollBottom,
  messages,
  isMine,
  getSenderLabel,
  authUser,
  bottomRef,
  text,
  setText,
  onSend,
}) {
  return (
    <div className="w-100 user-chat mt-4 mt-sm-0 card mb-0 bus-chat-right-card">
      <div className="card-body bus-chat-right-inner">
        <div className="bus-chat-header pb-3 user-chat-border">
          <div className="row align-items-center">
            <div className="col-md-8 col-8">
              <h5 className="font-size-15 mb-1 text-truncate">
                {activeThread ? `${formatBusDisplayName(activeThread)} Support` : 'Select a bus chat'}
              </h5>
              <p className="text-muted text-truncate mb-0">
                <i className="mdi mdi-circle text-primary font-size-10 align-middle me-1"></i>
                {activeThreadId ? `Thread #${activeThreadId}` : 'No thread selected'}
              </p>
            </div>

            <div className="col-md-4 col-4">
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
                        onClick={onRefreshMessages}
                        disabled={!activeThreadId || loadingMsgs}
                      >
                        {loadingMsgs ? 'Loading…' : 'Refresh messages'}
                      </button>
                    </div>
                  </div>
                </li>

                <li className="list-inline-item">
                  <button
                    className="btn nav-btn"
                    type="button"
                    onClick={onScrollBottom}
                    title="Scroll to bottom"
                    disabled={!activeThreadId}
                  >
                    <i className="mdi mdi-arrow-down"></i>
                  </button>
                </li>
              </ul>
            </div>
          </div>
        </div>

        <BusMessageList
          loadingMsgs={loadingMsgs}
          activeThreadId={activeThreadId}
          messages={messages}
          isMine={isMine}
          getSenderLabel={getSenderLabel}
          authUser={authUser}
          bottomRef={bottomRef}
        />

        <BusChatInput
          activeThreadId={activeThreadId}
          text={text}
          setText={setText}
          onSend={onSend}
        />
      </div>
    </div>
  );
}
