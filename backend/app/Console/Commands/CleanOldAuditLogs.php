<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AuditLog;
use App\Models\Scopes\TenantScope;
use Carbon\Carbon;

class CleanOldAuditLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audit:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up audit logs that are older than 1 year';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Starting audit log cleanup...');

        $cutoff = Carbon::now()->subYear();

        // Remove TenantScope global filter to clean up all logs across all tenants
        $deletedCount = AuditLog::withoutGlobalScope(TenantScope::class)
            ->where('created_at', '<', $cutoff)
            ->delete();

        $this->info("Audit log cleanup completed. Deleted {$deletedCount} records older than {$cutoff->toDateTimeString()}.");
    }
}
