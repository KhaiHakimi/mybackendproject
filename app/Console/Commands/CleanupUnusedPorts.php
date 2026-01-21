<?php

namespace App\Console\Commands;

use App\Models\Port;
use Illuminate\Console\Command;

class CleanupUnusedPorts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'port:cleanup {--force : Force the operation to run without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove ports that have no associated ferry services (schedules)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Scanning for unused ports...');

        $ports = Port::doesntHave('departures')
            ->doesntHave('arrivals')
            ->get();

        if ($ports->isEmpty()) {
            $this->info('No unused ports found.');

            return;
        }

        $this->table(
            ['ID', 'Name', 'Location'],
            $ports->map(fn ($p) => [$p->id, $p->name, $p->location])->toArray()
        );

        $this->info("Found {$ports->count()} unused ports.");

        if ($this->option('force') || $this->confirm('Do you wish to delete them?')) {
            $count = 0;
            foreach ($ports as $port) {
                $port->delete();
                $count++;
            }
            $this->info("Cleanup completed. {$count} ports deleted.");
        } else {
            $this->info('Operation cancelled.');
        }
    }
}
