function relativeTimeLabel(dateInput) {
  const date = dateInput instanceof Date ? dateInput : new Date(dateInput);
  if (Number.isNaN(date.getTime())) return 'just now';

  const diffSeconds = Math.max(1, Math.floor((Date.now() - date.getTime()) / 1000));
  if (diffSeconds < 60) return `${diffSeconds}s ago`;
  if (diffSeconds < 3600) return `${Math.floor(diffSeconds / 60)}m ago`;
  if (diffSeconds < 86400) return `${Math.floor(diffSeconds / 3600)}h ago`;
  return `${Math.floor(diffSeconds / 86400)}d ago`;
}

function escapeHtml(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function parseStoredItems(storageKey) {
  try {
    const raw = localStorage.getItem(storageKey);
    const parsed = raw ? JSON.parse(raw) : [];
    return Array.isArray(parsed) ? parsed : [];
  } catch (_) {
    return [];
  }
}

function toNotificationItem(input) {
  return {
    id: String(input.id || `${Date.now()}-${Math.random().toString(36).slice(2, 9)}`),
    title: input.title || 'Notification',
    message: input.message || '',
    iconClass: input.iconClass || 'ri-notification-3-line',
    iconBgClass: input.iconBgClass || 'bg-primary',
    createdAt: input.createdAt || new Date().toISOString(),
    link: input.link || '#',
  };
}

function renderNotificationNode(item) {
  const anchor = document.createElement('a');
  anchor.className = 'text-reset notification-item';
  anchor.href = item.link || '#';

  const createdDate = new Date(item.createdAt);
  const prettyDate = Number.isNaN(createdDate.getTime()) ? new Date() : createdDate;

  anchor.innerHTML = `
    <div class="d-flex">
      <div class="avatar-xs me-3">
        <span class="avatar-title ${item.iconBgClass} rounded-circle font-size-16">
          <i class="${item.iconClass}"></i>
        </span>
      </div>
      <div class="flex-1">
        <h6 class="mb-1">${escapeHtml(item.title)}</h6>
        <div class="font-size-12 text-muted">
          <p class="mb-1">${escapeHtml(item.message)}</p>
          <p class="mb-0">
            <i class="mdi mdi-clock-outline"></i>
            ${relativeTimeLabel(prettyDate)}
          </p>
        </div>
      </div>
    </div>
  `;

  return anchor;
}

document.addEventListener('DOMContentLoaded', () => {
  const listEl = document.getElementById('rt-notification-list');
  const badgeEl = document.getElementById('rt-notification-count');
  const emptyEl = document.getElementById('rt-notification-empty');
  const clearBtn = document.getElementById('rt-notification-clear');

  if (!listEl || !badgeEl) return;
  if (!window.Echo || !window.authUser?.id) return;

  const storageKey = `rt_notifications_${window.authUser.id}`;
  let items = parseStoredItems(storageKey).slice(0, 25);

  const getListContentEl = () => listEl.querySelector('.simplebar-content') || listEl;

  const persist = () => {
    try {
      localStorage.setItem(storageKey, JSON.stringify(items.slice(0, 25)));
    } catch (_) {
      // Ignore storage quota/access issues.
    }
  };

  const render = () => {
    const contentEl = getListContentEl();

    // Keep only element children and repopulate.
    Array.from(contentEl.children).forEach((el) => el.remove());

    if (!items.length) {
      if (emptyEl) {
        emptyEl.classList.remove('d-none');
        contentEl.appendChild(emptyEl);
      }
      badgeEl.classList.add('d-none');
      badgeEl.textContent = '0';
      return;
    }

    if (emptyEl) {
      emptyEl.classList.add('d-none');
    }

    items.forEach((item) => contentEl.appendChild(renderNotificationNode(item)));

    badgeEl.classList.remove('d-none');
    badgeEl.textContent = String(Math.min(items.length, 99));
  };

  const pushNotification = (rawItem) => {
    const item = toNotificationItem(rawItem);
    items = [item, ...items.filter((entry) => entry.id !== item.id)].slice(0, 25);
    persist();
    render();
  };

  const clearNotifications = () => {
    items = [];
    persist();
    render();
  };

  if (clearBtn) {
    clearBtn.addEventListener('click', clearNotifications);
  }

  const canUseCompanyChannel = window.companyId !== null && window.companyId !== undefined && String(window.companyId) !== '';
  const defaultChatLink = canUseCompanyChannel ? '/company/chat' : '/admin/chat';
  const defaultOperationsLink = canUseCompanyChannel ? '/company/buses' : '/admin/live-map';

  // Personal channel: direct user notifications and chat messages.
  window.Echo.private(`users.${window.authUser.id}`)
    .listen('.chat.message.received', (payload = {}) => {
      const sender = payload?.sender?.label || 'New message';
      pushNotification({
        id: `chat-${payload?.id || Date.now()}`,
        title: sender,
        message: payload?.body || 'You received a new chat message.',
        iconClass: 'ri-message-3-line',
        iconBgClass: 'bg-info',
        createdAt: payload?.created_at || new Date().toISOString(),
        link: payload?.thread_id ? `${defaultChatLink}?thread=${payload.thread_id}` : defaultChatLink,
      });
    })
    .listen('.NotificationCreated', (payload = {}) => {
      pushNotification({
        id: `user-${Date.now()}`,
        title: payload?.title || 'Notification',
        message: payload?.body || '',
        iconClass: 'ri-notification-3-line',
        iconBgClass: 'bg-primary',
        createdAt: payload?.createdAt || new Date().toISOString(),
        link: payload?.link || '#',
      });
    });

  // Fleet ops channels: trip start + incident.
  const opsChannel = canUseCompanyChannel
    ? window.Echo.private(`company.${window.companyId}`)
    : window.Echo.private('admin.buses');

  opsChannel
    .listen('.bus.trip.started', (payload = {}) => {
      const route = payload?.route ? ` on ${payload.route}` : '';
      const direction = payload?.direction ? ` (${payload.direction})` : '';
      pushNotification({
        id: `trip-start-${payload?.trip_id || Date.now()}`,
        title: `Trip started${direction}`,
        message: `Bus ${payload?.plate_number || '#' + (payload?.bus_id || '?')}${route}`,
        iconClass: 'ri-road-map-line',
        iconBgClass: 'bg-success',
        createdAt: payload?.started_at || new Date().toISOString(),
        link: defaultOperationsLink,
      });
    })
    .listen('.bus.incident.reported', (payload = {}) => {
      const type = payload?.incident_type ? `${payload.incident_type}` : 'incident';
      const reason = payload?.reason ? ` - ${payload.reason}` : '';
      pushNotification({
        id: `incident-${payload?.trip_id || Date.now()}`,
        title: `Bus ${type} reported`,
        message: `${payload?.plate_number || 'Bus'}${reason}`,
        iconClass: 'ri-alarm-warning-line',
        iconBgClass: 'bg-danger',
        createdAt: payload?.reported_at || new Date().toISOString(),
        link: defaultOperationsLink,
      });
    });

  render();
});
