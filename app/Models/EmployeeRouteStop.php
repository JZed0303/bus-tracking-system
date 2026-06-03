<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeRouteStop extends Model
{
    protected $fillable = [
        'employee_id',
        'route_stop_id',
        'type',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function stop()
    {
        return $this->belongsTo(RouteStop::class, 'route_stop_id');
    }

    
}
