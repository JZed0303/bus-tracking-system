<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetTransportData extends Command
{
    protected $signature = 'transport:reset-data {--force : Actually run the reset}';
    protected $description = 'Wipe operational transport data: trips, assignments, locations, checkins, etc.';

    public function handle(): int
    {
        if (!$this->option('force')) {
            $this->error('Dry-run only. Re-run with --force to execute.');
            $this->line('Example: php artisan transport:reset-data --force');
            return self::SUCCESS;
        }

        DB::transaction(function () {
            // Adjust these table names to your schema
            $tables = [
                'trip_locations',
                'checkins',
                // pivot tables (only if they exist in your DB)
                'employee_trip',
                'employee_assignment',
                'assignment_employee',
                'trip_employee',

                'trips',
                'assignments',
            ];

            foreach ($tables as $table) {
                if ($this->tableExists($table)) {
                    DB::table($table)->delete();
                }
            }
        });

        $this->info('Transport operational data reset completed.');
        return self::SUCCESS;
    }

    private function tableExists(string $table): bool
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            return DB::selectOne("SELECT to_regclass(?) AS t", [$table])->t !== null;
        }

        // mysql/sqlite
        try {
            DB::table($table)->limit(1)->get();
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
