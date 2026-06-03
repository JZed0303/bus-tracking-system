<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\Bus;
use App\Models\Checkin;
use App\Models\Company;
use App\Models\Driver;
use App\Models\Employee;
use App\Models\EmployeeRouteStop;
use App\Models\EmployeeQrCode;
use App\Models\RouteStop;
use App\Models\TransportRoute;
use App\Models\Trip;
use App\Models\TripLocation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CarmonaNetlinkTripSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $company = $this->seedCompany();
            $driver = $this->seedDriver($company);
            $bus = $this->seedBus();
            [$route, $stops] = $this->seedRoute($company);
            $employees = $this->seedEmployees($company, $stops);
            $assignment = $this->seedAssignment($company, $driver, $bus, $route);
            [$completedTrip, $ongoingTrip] = $this->seedTrips($assignment);

            $this->seedTripLocations($completedTrip, $bus, $driver, $stops, false);
            $this->seedTripLocations($ongoingTrip, $bus, $driver, $stops, true);

            $this->seedCompletedTripTransactions($completedTrip, $employees, $stops);
            $this->seedRealtimeTransactions($ongoingTrip, $employees, $stops);
        });
    }

    private function seedCompany(): Company
    {
        $company = Company::query()->firstOrCreate(
            ['name' => 'Netlink Workforce Transport Services'],
            [
                'address' => 'Netlink Compound, Calamba City, Laguna',
                'contact_person' => 'Rochelle Mendoza',
                'contact_number' => '09171230011',
                'status' => 'active',
            ]
        );

        $admin = User::query()->firstOrCreate(
            ['email' => 'netlink.transport.admin@test.com'],
            [
                'company_id' => $company->id,
                'first_name' => 'Netlink',
                'middle_name' => null,
                'last_name' => 'Admin',
                'address' => 'Calamba City, Laguna',
                'password' => Hash::make('password'),
                'role' => 'company_admin',
                'status' => 'active',
            ]
        );

        if (method_exists($admin, 'assignRole')) {
            $admin->assignRole('company_admin');
        }

        return $company;
    }

    private function seedDriver(Company $company): Driver
    {
        $driverUser = User::query()->firstOrCreate(
            ['email' => 'roberto.santiago.driver@test.com'],
            [
                'company_id' => $company->id,
                'first_name' => 'Roberto',
                'middle_name' => 'Lopez',
                'last_name' => 'Santiago',
                'address' => 'Barangay Maduya, Carmona, Cavite',
                'password' => Hash::make('password'),
                'role' => 'driver',
                'status' => 'active',
            ]
        );

        if (method_exists($driverUser, 'assignRole')) {
            $driverUser->assignRole('driver');
        }

        return Driver::query()->firstOrCreate(
            ['user_id' => $driverUser->id],
            [
                'company_id' => $company->id,
                'license_number' => 'NCR-LAG-2401187',
                'phone' => '09179910021',
                'status' => 'active',
            ]
        );
    }

    private function seedBus(): Bus
    {
        return Bus::query()->updateOrCreate(
            ['plate_number' => 'DAB-4821'],
            [
                'bus_code' => 'NETLINK-07',
                'capacity' => 32,
                'brand_model' => 'Hino FB Shuttle',
                'status' => 'active',
                'last_seen_at' => now('Asia/Manila')->subMinute(),
            ]
        );
    }

    private function seedRoute(Company $company): array
    {
        $route = TransportRoute::query()->updateOrCreate(
            ['name' => 'Carmona City Hall to Netlink Calamba'],
            [
                'company_id' => $company->id,
                'start_location' => 'Carmona City Hall, Cavite',
                'end_location' => 'Netlink Company, Calamba City, Laguna',
                'start_lat' => 14.3135200,
                'start_lng' => 121.0578300,
                'end_lat' => 14.2118200,
                'end_lng' => 121.1656300,
                'description' => 'Employee shuttle route from Carmona City Hall to Netlink Company in Calamba.',
                'status' => 'active',
            ]
        );

        $stopRows = [
            ['order' => 1, 'address' => 'Carmona City Hall, Barangay 4, Carmona, Cavite', 'lat' => 14.3135200, 'lng' => 121.0578300],
            ['order' => 2, 'address' => 'WalterMart Carmona, Governor’s Drive, Carmona, Cavite', 'lat' => 14.3131500, 'lng' => 121.0446700],
            ['order' => 3, 'address' => 'Biñan Central Terminal, Biñan, Laguna', 'lat' => 14.3299000, 'lng' => 121.0804200],
            ['order' => 4, 'address' => 'Paseo de Santa Rosa, Santa Rosa, Laguna', 'lat' => 14.2716400, 'lng' => 121.0569600],
            ['order' => 5, 'address' => 'Balibago Complex, Santa Rosa, Laguna', 'lat' => 14.2954200, 'lng' => 121.1057600],
            ['order' => 6, 'address' => 'Turbina Terminal, Calamba, Laguna', 'lat' => 14.1846500, 'lng' => 121.1529500],
            ['order' => 7, 'address' => 'SM City Calamba, Real Road, Calamba, Laguna', 'lat' => 14.2113100, 'lng' => 121.1651400],
            ['order' => 8, 'address' => 'Netlink Company, Barangay Real, Calamba City, Laguna', 'lat' => 14.2118200, 'lng' => 121.1656300],
        ];

        $stops = [];

        foreach ($stopRows as $row) {
            $stops[] = RouteStop::query()->updateOrCreate(
                [
                    'route_id' => $route->id,
                    'stop_order' => $row['order'],
                ],
                [
                    'address' => $row['address'],
                    'latitude' => $row['lat'],
                    'longitude' => $row['lng'],
                ]
            );
        }

        return [$route, collect($stops)->keyBy('stop_order')];
    }

    private function seedEmployees(Company $company, $stops)
    {
        $employees = [
            [
                'email' => 'alvin.mercado@test.com',
                'first_name' => 'Alvin',
                'last_name' => 'Mercado',
                'middle_name' => 'Reyes',
                'address' => 'Maduya, Carmona, Cavite',
                'department' => 'IT Operations',
                'position' => 'Network Technician',
                'code' => 'NET-EMP-0001',
                'pickup_stop_order' => 1,
            ],
            [
                'email' => 'shiela.deguzman@test.com',
                'first_name' => 'Shiela',
                'last_name' => 'De Guzman',
                'middle_name' => 'Perez',
                'address' => 'Lantic, Carmona, Cavite',
                'department' => 'Customer Support',
                'position' => 'Support Specialist',
                'code' => 'NET-EMP-0002',
                'pickup_stop_order' => 1,
            ],
            [
                'email' => 'mark.javier@test.com',
                'first_name' => 'Mark',
                'last_name' => 'Javier',
                'middle_name' => 'Tolentino',
                'address' => 'Paseo, Carmona, Cavite',
                'department' => 'Warehouse',
                'position' => 'Inventory Clerk',
                'code' => 'NET-EMP-0003',
                'pickup_stop_order' => 2,
            ],
            [
                'email' => 'jessa.fernandez@test.com',
                'first_name' => 'Jessa',
                'last_name' => 'Fernandez',
                'middle_name' => 'Cruz',
                'address' => 'Biñan, Laguna',
                'department' => 'HR',
                'position' => 'HR Assistant',
                'code' => 'NET-EMP-0004',
                'pickup_stop_order' => 3,
            ],
            [
                'email' => 'leo.soriano@test.com',
                'first_name' => 'Leo',
                'last_name' => 'Soriano',
                'middle_name' => 'Mendoza',
                'address' => 'Santa Rosa, Laguna',
                'department' => 'Production',
                'position' => 'Line Leader',
                'code' => 'NET-EMP-0005',
                'pickup_stop_order' => 4,
            ],
            [
                'email' => 'trisha.ramos@test.com',
                'first_name' => 'Trisha',
                'last_name' => 'Ramos',
                'middle_name' => 'Flores',
                'address' => 'Balibago, Santa Rosa, Laguna',
                'department' => 'Finance',
                'position' => 'Accounting Staff',
                'code' => 'NET-EMP-0006',
                'pickup_stop_order' => 5,
            ],
            [
                'email' => 'noel.batac@test.com',
                'first_name' => 'Noel',
                'last_name' => 'Batac',
                'middle_name' => 'Garcia',
                'address' => 'Turbina, Calamba, Laguna',
                'department' => 'Security',
                'position' => 'Security Officer',
                'code' => 'NET-EMP-0007',
                'pickup_stop_order' => 6,
            ],
            [
                'email' => 'camille.villanueva@test.com',
                'first_name' => 'Camille',
                'last_name' => 'Villanueva',
                'middle_name' => 'Santos',
                'address' => 'Parian, Calamba, Laguna',
                'department' => 'Procurement',
                'position' => 'Purchasing Associate',
                'code' => 'NET-EMP-0008',
                'pickup_stop_order' => 7,
            ],
        ];

        return collect($employees)->map(function (array $employeeData, int $index) use ($company, $stops) {
            $user = User::query()->firstOrCreate(
                ['email' => $employeeData['email']],
                [
                    'company_id' => $company->id,
                    'first_name' => $employeeData['first_name'],
                    'middle_name' => $employeeData['middle_name'],
                    'last_name' => $employeeData['last_name'],
                    'address' => $employeeData['address'],
                    'password' => Hash::make('password'),
                    'role' => 'employee',
                    'status' => 'active',
                ]
            );

            if (method_exists($user, 'assignRole')) {
                $user->assignRole('employee');
            }

            $employee = Employee::query()->firstOrCreate(
                ['user_id' => $user->id],
                [
                    'company_id' => $company->id,
                    'employee_code' => $employeeData['code'],
                    'department' => $employeeData['department'],
                    'position' => $employeeData['position'],
                    'status' => 'active',
                ]
            );

            if (class_exists(EmployeeQrCode::class)) {
                EmployeeQrCode::query()->updateOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'is_active' => true,
                    ],
                    [
                        'qr_token' => (string) Str::uuid(),
                        'generated_at' => now('Asia/Manila'),
                    ]
                );
            }

            $pickupStop = $stops->get($employeeData['pickup_stop_order']);
            $dropoffStop = $stops->get(8);

            if ($pickupStop) {
                EmployeeRouteStop::query()->updateOrCreate(
                    ['employee_id' => $employee->id, 'type' => 'pickup'],
                    ['route_stop_id' => $pickupStop->id]
                );
            }

            if ($dropoffStop) {
                EmployeeRouteStop::query()->updateOrCreate(
                    ['employee_id' => $employee->id, 'type' => 'dropoff'],
                    ['route_stop_id' => $dropoffStop->id]
                );
            }

            return $employee;
        });
    }

    private function seedAssignment(Company $company, Driver $driver, Bus $bus, TransportRoute $route): Assignment
    {
        return Assignment::query()->updateOrCreate(
            [
                'driver_id' => $driver->id,
                'bus_id' => $bus->id,   
                'route_id' => $route->id,
                'effective_from' => now('Asia/Manila')->toDateString(),
            ],
            [
                'company_id' => $company->id,
                'effective_to' => now('Asia/Manila')->addDays(30)->toDateString(),
                'status' => 'active',
                'leg' => 'pickup',
            ]
        );
    }

    private function seedTrips(Assignment $assignment): array
    {
        $today = now('Asia/Manila');

        $completedTrip = Trip::query()->updateOrCreate(
            [
                'assignment_id' => $assignment->id,
                'trip_date' => $today->toDateString(),
                'scheduled_start_time' => '05:30:00',
                'direction' => 'pickup',
            ],
            [
                'scheduled_end_time' => '07:15:00',
                'actual_start_time' => $today->copy()->startOfDay()->addHours(5)->addMinutes(34),
                'actual_end_time' => $today->copy()->startOfDay()->addHours(7)->addMinutes(12),
                'status' => 'completed',
                'ended_reason' => 'completed route',
            ]
        );

        $ongoingTrip = Trip::query()->updateOrCreate(
            [
                'assignment_id' => $assignment->id,
                'trip_date' => $today->toDateString(),
                'scheduled_start_time' => '16:30:00',
                'direction' => 'pickup',
            ],
            [
                'scheduled_end_time' => '18:10:00',
                'actual_start_time' => $today->copy()->subMinutes(58),
                'actual_end_time' => null,
                'status' => 'ongoing',
            ]
        );

        return [$completedTrip, $ongoingTrip];
    }

    private function seedTripLocations(Trip $trip, Bus $bus, Driver $driver, $stops, bool $isRealtime): void
    {
        TripLocation::query()->where('trip_id', $trip->id)->delete();

        $baseTrackedAt = $isRealtime
            ? now('Asia/Manila')->subMinutes(56)
            : now('Asia/Manila')->startOfDay()->addHours(5)->addMinutes(36);

        $points = [
            ['lat' => 14.3135200, 'lng' => 121.0578300, 'speed' => 0],
            ['lat' => 14.3131800, 'lng' => 121.0448200, 'speed' => 18],
            ['lat' => 14.3218700, 'lng' => 121.0605100, 'speed' => 31],
            ['lat' => 14.3299000, 'lng' => 121.0804200, 'speed' => 24],
            ['lat' => 14.3027600, 'lng' => 121.0718100, 'speed' => 36],
            ['lat' => 14.2829700, 'lng' => 121.0613400, 'speed' => 34],
            ['lat' => 14.2716400, 'lng' => 121.0569600, 'speed' => 22],
            ['lat' => 14.2874200, 'lng' => 121.0914200, 'speed' => 28],
            ['lat' => 14.2954200, 'lng' => 121.1057600, 'speed' => 18],
            ['lat' => 14.2408700, 'lng' => 121.1376600, 'speed' => 39],
            ['lat' => 14.2109400, 'lng' => 121.1591300, 'speed' => 26],
            ['lat' => 14.2118200, 'lng' => 121.1656300, 'speed' => 0],
        ];

        if ($isRealtime) {
            $points = array_slice($points, 0, 11);
            $points[10]['speed'] = 14;
        }

        foreach ($points as $index => $point) {
            TripLocation::query()->create([
                'trip_id' => $trip->id,
                'bus_id' => $bus->id,
                'driver_id' => $driver->id,
                'latitude' => $point['lat'],
                'longitude' => $point['lng'],
                'speed' => $point['speed'],
                'tracked_at' => $baseTrackedAt->copy()->addMinutes($index * 5),
            ]);
        }

        if ($isRealtime) {
            $bus->forceFill(['last_seen_at' => now('Asia/Manila')->subSeconds(30)])->saveQuietly();
        }
    }

    private function seedCompletedTripTransactions(Trip $trip, $employees, $stops): void
    {
        Checkin::query()->where('trip_id', $trip->id)->delete();

        $startTime = Carbon::parse($trip->actual_start_time, 'Asia/Manila');
        $endTime = Carbon::parse($trip->actual_end_time, 'Asia/Manila');

        $completedEmployees = $employees->take(6)->values();

        foreach ($completedEmployees as $index => $employee) {
            $pickupStop = EmployeeRouteStop::query()
                ->where('employee_id', $employee->id)
                ->where('type', 'pickup')
                ->with('stop')
                ->first();

            $pickupLat = (float) ($pickupStop?->stop?->latitude ?? 14.3135200);
            $pickupLng = (float) ($pickupStop?->stop?->longitude ?? 121.0578300);

            Checkin::query()->create([
                'trip_id' => $trip->id,
                'employee_id' => $employee->id,
                'scan_type' => 'checkin',
                'scan_time' => $startTime->copy()->addMinutes($index * 6),
                'scan_lat' => $pickupLat,
                'scan_lng' => $pickupLng,
                'scanned_by_employee_id' => $employee->id,
                'client_scan_id' => (string) Str::uuid(),
                'is_offline' => false,
                'synced_at' => $startTime->copy()->addMinutes($index * 6)->addSeconds(20),
            ]);

            Checkin::query()->create([
                'trip_id' => $trip->id,
                'employee_id' => $employee->id,
                'scan_type' => 'checkout',
                'scan_time' => $endTime->copy()->subMinutes(8 - $index),
                'scan_lat' => 14.2118200,
                'scan_lng' => 121.1656300,
                'scanned_by_employee_id' => $employee->id,
                'client_scan_id' => (string) Str::uuid(),
                'is_offline' => false,
                'synced_at' => $endTime->copy()->subMinutes(8 - $index)->addSeconds(20),
            ]);
        }
    }

    private function seedRealtimeTransactions(Trip $trip, $employees, $stops): void
    {
        Checkin::query()->where('trip_id', $trip->id)->delete();

        $startTime = Carbon::parse($trip->actual_start_time, 'Asia/Manila');
        $activeEmployees = $employees->take(5)->values();

        foreach ($activeEmployees as $index => $employee) {
            $pickupStop = EmployeeRouteStop::query()
                ->where('employee_id', $employee->id)
                ->where('type', 'pickup')
                ->with('stop')
                ->first();

            $pickupLat = (float) ($pickupStop?->stop?->latitude ?? 14.3135200);
            $pickupLng = (float) ($pickupStop?->stop?->longitude ?? 121.0578300);

            Checkin::query()->create([
                'trip_id' => $trip->id,
                'employee_id' => $employee->id,
                'scan_type' => 'checkin',
                'scan_time' => $startTime->copy()->addMinutes($index * 7),
                'scan_lat' => $pickupLat,
                'scan_lng' => $pickupLng,
                'scanned_by_employee_id' => $employee->id,
                'client_scan_id' => (string) Str::uuid(),
                'is_offline' => false,
                'synced_at' => $startTime->copy()->addMinutes($index * 7)->addSeconds(15),
            ]);
        }
    }
}
