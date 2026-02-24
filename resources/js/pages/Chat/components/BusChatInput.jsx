import React from 'react';

export default function BusChatInput({ activeThreadId, text, setText, onSend }) {
  return (
    <div className="bus-chat-input px-lg-3">
      <div className="pt-3">
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
                    onSend?.();
                  }
                }}
                disabled={!activeThreadId}
              />
            </div>
          </div>

          <div className="col-auto">
            <button
              type="button"
              className="btn btn-primary chat-send w-md waves-effect waves-light"
              onClick={onSend}
              disabled={!activeThreadId || !text.trim()}
            >
              <span className="d-none d-sm-inline-block me-2">Send</span>
              <i className="mdi mdi-send"></i>
            </button>
          </div>
        </div>

        <div className="small text-muted mt-2">Replies are sent in realtime to the bus device.</div>
      </div>
    </div>
  );
}
