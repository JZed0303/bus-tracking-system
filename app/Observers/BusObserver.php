<?php

namespace App\Observers;

use App\Models\Bus;
use App\Models\ChatThread;
use Illuminate\Support\Facades\Auth;

class BusObserver
{
    public function created(Bus $bus): void
    {
        $user = Auth::user(); // works in web/admin requests

        ChatThread::firstOrCreate(
            [
                'context_type' => 'bus_support',
                'context_id'   => $bus->id,
            ],
            [
                // company_id might be null in your buses table; handle safely:
                'company_id'       => $bus->company_id,

                'title'            => "Bus #{$bus->id} Support",

                // ✅ REQUIRED FIELDS (NOT NULL)
                'created_by_type'  => $user ? get_class($user) : Bus::class,
                'created_by_id'    => $user?->id ?? $bus->id,
            ]
        );
    }
}
