# Transfer and Leg Guide

This guide points to the new backend changes for:
- Mid-trip maintenance/breakdown transfer
- Separate pickup vs dropoff bus assignment support

## 1) Assignment leg support
- `assignments.leg` values: `pickup`, `dropoff`, `both`
- Files:
  - `database/migrations/2026_02_27_120000_add_leg_to_assignments_table.php`
  - `app/Models/Assignment.php` (`supportsDirection()`)
  - `app/Http/Controllers/Admin/AssignmentController.php` (leg-aware conflict checks)

## 2) Incident + replacement trip flow
- Endpoint: `POST /api/bus/trip/incident`
- File: `app/Http/Controllers/Api/BusTripController.php` (`reportIncident`)
- Behavior:
  - Closes ongoing trip as `cancelled`
  - Sets `ended_reason` + `incident_reported_at`
  - Marks bus `maintenance` for maintenance/breakdown incidents
  - Creates linked replacement trip when `replacement_bus_id` is provided
  - Creates transfer rows in `trip_employee_transfers`

## 3) Transfer-aware QR scanning
- File: `app/Services/QrCheckinService.php`
- Behavior:
  - Prevents employee from being open on unrelated active trips
  - Allows scan on replacement trip when transfer row exists
  - Marks transfer as `confirmed` on first check-in in replacement trip

## 4) Transfer visibility in API responses
- `app/Http/Controllers/Api/BusQrController.php`
  - Scan response includes `trip.transfer_confirmed`
- `app/Http/Controllers/Api/Bus/BusOnboardEmployeeController.php`
  - Adds `transferred_pending` status for transferred employees not yet re-scanned
- `app/Http/Resources/Api/Bus/BusContextResource.php`
  - Exposes `trip.transfer_from_trip_id` and `assignment.leg`

## 5) New/updated schema objects
- `trips.transfer_from_trip_id`
- `trips.ended_reason`
- `trips.incident_reported_at`
- table `trip_employee_transfers`
- `assignments.leg`

## 6) Deploy step
Run:
- `php artisan migrate`
