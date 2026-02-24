<?php

namespace App\Console\Commands;

  use App\Models\User;

use Illuminate\Console\Command;
use App\Models\Bus;
use App\Models\ChatThread;

class BackfillBusSupportThreads extends Command
{
protected $signature = 'chat:backfill-bus-threads {--company_id=} {--user_id=}';

    protected $description = 'Create bus_support chat threads for existing buses if missing';

public function handle(): int
{
    $userId = $this->option('user_id');

    if (!$userId) {
        $this->error('Missing required option: --user_id=');
        return self::FAILURE;
    }

    $creator = User::find($userId);

    if (!$creator) {
        $this->error("User not found: {$userId}");
        return self::FAILURE;
    }

    $q = Bus::query();

    if ($this->option('company_id')) {
        $q->where('company_id', $this->option('company_id'));
    }

    $processed = 0;

    $q->orderBy('id')->chunk(200, function ($buses) use (&$processed, $creator) {
        foreach ($buses as $bus) {
            // Ensure bus has company_id; adjust if your column differs
            $companyId = $bus->company_id ?? null;

            ChatThread::firstOrCreate(
                [
                    'context_type' => 'bus_support',
                    'context_id'   => $bus->id,
                ],
                [
                    'company_id'       => $companyId,
                    'title'            => "Bus #{$bus->id} Support",
                    'created_by_type'  => User::class,
                    'created_by_id'    => $creator->id,
                ]
            );

            $processed++;
        }
    });

    $this->info("Processed buses: {$processed}");
    return self::SUCCESS;
}

}
