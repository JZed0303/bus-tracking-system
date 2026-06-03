<?php

return [
    'api' => [
        // Generic API limiter for endpoints without a dedicated higher-throughput profile.
        'requests_per_minute' => (int) env('API_RATE_LIMIT_PER_MINUTE', 120),

        // Protected bus API default limiter (all authenticated bus endpoints).
        'bus_requests_per_minute' => (int) env('BUS_API_RATE_LIMIT_PER_MINUTE', 60),

        // Dedicated GPS ingest limiter (applies to /api/bus/gps only).
        'bus_gps_requests_per_minute' => (int) env('BUS_GPS_RATE_LIMIT_PER_MINUTE', 60),

        // High-throughput signaling limiter for WebRTC offer/answer/ICE traffic.
        'video_calls_signal_requests_per_minute' => (int) env('VIDEO_CALLS_SIGNAL_RATE_LIMIT_PER_MINUTE', 3000),

    ],

    'dashboard' => [
        'analytics_cache_enabled' => env('COMPANY_DASHBOARD_ANALYTICS_CACHE_ENABLED', true),
        'analytics_cache_ttl_seconds' => (int) env('COMPANY_DASHBOARD_ANALYTICS_CACHE_TTL_SECONDS', 60),
    ],

    'incident' => [
        // If no replacement trip is created within this window, incident is considered unresolved.
        'replacement_sla_minutes' => (int) env('TRANSPORT_INCIDENT_REPLACEMENT_SLA_MINUTES', 10),

        // Pending transfer confirmations older than this window are escalation candidates.
        'transfer_confirm_sla_minutes' => (int) env('TRANSPORT_TRANSFER_CONFIRM_SLA_MINUTES', 15),
    ],

    'telemetry' => [
        'pruning_enabled' => env('TRANSPORT_TELEMETRY_PRUNING_ENABLED', true),
        'prune_after_days' => (int) env('TRANSPORT_TELEMETRY_PRUNE_AFTER_DAYS', 14),
        'prune_chunk_size' => (int) env('TRANSPORT_TELEMETRY_PRUNE_CHUNK_SIZE', 5000),
        'prune_schedule' => env('TRANSPORT_TELEMETRY_PRUNE_SCHEDULE', '02:30'),
    ],
];
