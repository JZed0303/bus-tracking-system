<?php

namespace Tests\Unit\Http\Resources;

use App\Http\Resources\Api\V1\Bus\LiveBusMapResource;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class LiveBusMapResourceTest extends TestCase
{
    public function test_it_normalizes_full_payload(): void
    {
        $resource = new LiveBusMapResource([
            'trip_id' => '44',
            'status' => 'ongoing',
            'lat' => '14.329012',
            'lng' => '121.045234',
            'speed' => '35.4',
            'bus_id' => '8',
            'plate_number' => 'ABC-1234',
            'bus' => 'ABC-1234',
            'driver' => 'Juan Dela Cruz',
            'company' => 'ABC Manufacturing Corp',
            'route' => 'Morning Route',
            'employee_count' => '18',
            'updated_at' => '2026-02-13 10:20:00',
        ]);

        $data = $resource->toArray(new Request());

        $this->assertSame(44, $data['trip_id']);
        $this->assertSame('ongoing', $data['status']);
        $this->assertSame(14.329012, $data['lat']);
        $this->assertSame(121.045234, $data['lng']);
        $this->assertSame(35.4, $data['speed']);
        $this->assertSame(8, $data['bus_id']);
        $this->assertSame('ABC-1234', $data['plate_number']);
        $this->assertSame('ABC-1234', $data['bus']);
        $this->assertSame('Juan Dela Cruz', $data['driver']);
        $this->assertSame('ABC Manufacturing Corp', $data['company']);
        $this->assertSame('Morning Route', $data['route']);
        $this->assertSame(18, $data['employee_count']);
        $this->assertSame('2026-02-13 10:20:00', $data['updated_at']);
    }

    public function test_it_keeps_backward_compatible_bus_label_when_bus_field_missing(): void
    {
        $resource = new LiveBusMapResource([
            'bus_id' => 55,
            'plate_number' => 'XYZ-9876',
        ]);

        $data = $resource->toArray(new Request());

        $this->assertSame(55, $data['bus_id']);
        $this->assertSame('XYZ-9876', $data['plate_number']);
        $this->assertSame('XYZ-9876', $data['bus']);
    }

    public function test_it_uses_safe_defaults_for_missing_fields(): void
    {
        $resource = new LiveBusMapResource([]);

        $data = $resource->toArray(new Request());

        $this->assertNull($data['trip_id']);
        $this->assertSame('unknown', $data['status']);
        $this->assertNull($data['lat']);
        $this->assertNull($data['lng']);
        $this->assertSame(0.0, $data['speed']);
        $this->assertNull($data['bus_id']);
        $this->assertNull($data['plate_number']);
        $this->assertSame('N/A', $data['bus']);
        $this->assertSame('N/A', $data['driver']);
        $this->assertSame('N/A', $data['company']);
        $this->assertSame('N/A', $data['route']);
        $this->assertSame(0, $data['employee_count']);
        $this->assertNull($data['updated_at']);
    }

    public function test_it_exposes_stable_live_map_contract_keys(): void
    {
        $resource = new LiveBusMapResource([
            'bus_id' => 1,
            'plate_number' => 'TEST-100',
        ]);

        $data = $resource->toArray(new Request());

        $this->assertSame([
            'trip_id',
            'status',
            'lat',
            'lng',
            'speed',
            'bus_id',
            'plate_number',
            'bus',
            'driver',
            'company',
            'route',
            'employee_count',
            'updated_at',
        ], array_keys($data));
    }
}
