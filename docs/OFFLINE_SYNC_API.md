# Offline-First QR Sync API

## Goal
Allow bus device to continue scanning while offline, then upload scans in batches when online.

## Endpoint
`POST /api/bus/scan/sync-offline`

Auth/middleware:
- `auth:sanctum`
- `abilities:bus:scan`

## Request body
```json
{
  "scans": [
    {
      "client_scan_id": "5c4ebf27-b1c0-4d63-b8db-5c88f019d6f2",
      "qr_token": "EMP-QR-TOKEN",
      "scan_time": "2026-02-27T11:20:05+08:00",
      "latitude": 14.5995,
      "longitude": 120.9842,
      "trip_id": 40
    }
  ]
}
```

## Idempotency
- `client_scan_id` is required and unique in `checkins`.
- Re-sending the same scan (or full batch) returns `status=duplicate` for already-synced items.
- Race-safe behavior is handled by unique key + conflict catch.

## Response
```json
{
  "status": "success",
  "message": "Offline sync processed.",
  "summary": {
    "received": 10,
    "accepted": 8,
    "duplicates": 2,
    "failed": 0
  },
  "items": [
    {
      "index": 0,
      "client_scan_id": "...",
      "status": "accepted",
      "checkin_id": 123,
      "trip_id": 40,
      "scan_type": "checkin"
    }
  ]
}
```

## Local app behavior recommendation
1. Save every offline scan locally with generated `client_scan_id` (UUID).
2. Keep FIFO order by `scan_time` when sending batch.
3. Retry same batch on network failure; duplicates are safe.
4. Mark local item as synced only when API returns `accepted` or `duplicate`.
5. Keep `failed` items for operator review/retry.
