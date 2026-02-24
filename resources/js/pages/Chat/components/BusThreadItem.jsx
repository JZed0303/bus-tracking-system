import React from 'react';
import { formatBusDisplayName, formatBusMetaLine, shortTime } from '../../../util/busFormatters';

export default function BusThreadItem({ t, active, onSelect, subtitle }) {
  return (
    <li className={active ? 'active' : ''}>
      <a
        href="#"
        className="mt-0"
        onClick={(e) => {
          e.preventDefault();
          onSelect?.(t);
        }}
      >
        <div className="d-flex">
          <div className="user-img online align-self-center me-3">
            <img
              src={t.avatar_url || '/build/images/users/avatar-4.jpg'}
              className="rounded-circle avatar-xs"
              alt="thread"
              onError={(e) => (e.currentTarget.src = '/build/images/users/avatar-4.jpg')}
            />
            <span className="user-status"></span>
          </div>

          <div className="flex-1 overflow-hidden">
            <h5 className="text-truncate font-size-14 mb-1">{formatBusDisplayName(t)}</h5>
            <p className="text-truncate mb-0">{formatBusMetaLine(t)}</p>
            <p className="text-truncate mb-0">{subtitle}</p>
          </div>

          <div className="font-size-11">{t.updated_at ? shortTime(t.updated_at) : ''}</div>
        </div>
      </a>
    </li>
  );
}
