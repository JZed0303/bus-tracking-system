import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const broadcastDriver = import.meta.env.VITE_BROADCAST_DRIVER ?? 'null';
const pusherKey = import.meta.env.VITE_PUSHER_APP_KEY;

window.Echo = broadcastDriver === 'pusher' && pusherKey
    ? new Echo({
        broadcaster: 'pusher',
        key: pusherKey,
        cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
        encrypted: true,
    })
    : null;
