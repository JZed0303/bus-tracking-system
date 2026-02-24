<?php

namespace App\Console\Commands;

use Closure;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PruneTelemetryData extends Command
{
    protected $signature = 'transport:prune-telemetry
                            {--days= : Override retention window in days}
                            {--dry-run : Show what would be deleted without deleting}';

    protected $description = 'Delete old telemetry records from bus_gps and trip_locations.';

    public function handle(): int
    {
        try {
            $days = (int) ($this->option('days') ?? config('transport.telemetry.prune_after_days', 14));
            $chunkSize = max(100, (int) config('transport.telemetry.prune_chunk_size', 5000));
            $dryRun = (bool) $this->option('dry-run');

            if ($days < 1) {
                $this->error('Retention days must be at least 1.');
                return self::FAILURE;
            }

            $cutoff = now()->subDays($days);

            $this->line('Pruning telemetry older than '.$days.' day(s).');
            $this->line('Cutoff: '.$cutoff->toDateTimeString());
            $this->line('Mode: '.($dryRun ? 'DRY-RUN' : 'EXECUTE'));

            $targets = [
                'bus_gps' => function ($query) use ($cutoff) {
                    $query->where(function ($inner) use ($cutoff) {
                        $inner->where('tracked_at', '<', $cutoff)
                            ->orWhere(function ($fallback) use ($cutoff) {
                                $fallback->whereNull('tracked_at')
                                    ->where('created_at', '<', $cutoff);
                            });
                    });
                },
                'trip_locations' => function ($query) use ($cutoff) {
                    $query->where('tracked_at', '<', $cutoff);
                },
            ];

            $totalDeleted = 0;

            foreach ($targets as $table => $constraint) {
                if (!Schema::hasTable($table)) {
                    $this->warn("Skipping {$table}: table not found.");
                    continue;
                }

                $candidateCount = $this->countCandidates($table, $constraint);
                if ($candidateCount === 0) {
                    $this->info("{$table}: nothing to prune.");
                    continue;
                }

                if ($dryRun) {
                    $this->info("{$table}: {$candidateCount} row(s) would be deleted.");
                    continue;
                }

                $deleted = $this->deleteInChunks($table, $constraint, $chunkSize);
                $totalDeleted += $deleted;

                $this->info("{$table}: deleted {$deleted} row(s).");
            }

            if (!$dryRun) {
                $this->line("Total deleted rows: {$totalDeleted}");
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Telemetry prune failed: '.$e->getMessage());
            return self::FAILURE;
        }
    }

    private function countCandidates(string $table, Closure $constraint): int
    {
        $query = DB::table($table);
        $constraint($query);

        return (int) $query->count();
    }

    private function deleteInChunks(string $table, Closure $constraint, int $chunkSize): int
    {
        $deleted = 0;

        while (true) {
            $idQuery = DB::table($table)->select('id');
            $constraint($idQuery);

            $ids = $idQuery->orderBy('id')->limit($chunkSize)->pluck('id');
            if ($ids->isEmpty()) {
                break;
            }

            $affected = DB::table($table)->whereIn('id', $ids)->delete();
            $deleted += $affected;

            if ($affected === 0) {
                break;
            }
        }

        return $deleted;
    }
}
