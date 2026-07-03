<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tax;
use App\Models\Tenant;
use App\Mail\TaxDueReminderMail;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class SendTaxDueReminders extends Command
{
    protected $signature = 'tax:send-reminders';
    protected $description = 'Send email notifications for unpaid taxes that are approaching due dates';

    public function handle()
    {
        $this->info('Starting tax due date checks...');

        $unpaidTaxes = Tax::where('status', 'terutang')->get();
        $dueTaxesByTenant = [];

        $today = Carbon::today();

        foreach ($unpaidTaxes as $tax) {
            // Parse masa_pajak (format: YYYY-MM)
            try {
                $parts = explode('-', $tax->masa_pajak);
                if (count($parts) !== 2) continue;
                
                $year = (int)$parts[0];
                $month = (int)$parts[1];

                // Next month is the tax deadline month
                $nextMonthDate = Carbon::create($year, $month, 1)->addMonth();
                $deadlineDate = null;

                if (in_array($tax->tipe_pajak, ['pph_21', 'pph_23'])) {
                    // PPh penyetoran deadline: 10th of next month
                    $deadlineDate = Carbon::create($nextMonthDate->year, $nextMonthDate->month, 10);
                } else {
                    // PPN penyetoran & pelaporan: end of next month
                    $deadlineDate = Carbon::create($nextMonthDate->year, $nextMonthDate->month, 1)->endOfMonth();
                }

                // Attach deadline date to the object for reference
                $tax->deadline_date = $deadlineDate->format('Y-m-d');

                // Diff in days
                $diffDays = $today->diffInDays($deadlineDate, false);

                // If due date is in 3 days, 1 day, or today
                if (in_array($diffDays, [0, 1, 3])) {
                    $dueTaxesByTenant[$tax->tenant_id][] = $tax;
                }
            } catch (\Exception $e) {
                $this->error("Error parsing tax ID {$tax->id}: " . $e->getMessage());
            }
        }

        foreach ($dueTaxesByTenant as $tenantId => $taxes) {
            $tenant = Tenant::find($tenantId);
            if (!$tenant) continue;

            $recipientEmail = $tenant->email;
            if (!$recipientEmail) {
                // Fallback to the first user with owner role
                $owner = $tenant->users()->where('role', 'owner')->first();
                if ($owner) {
                    $recipientEmail = $owner->email;
                } else {
                    $firstUser = $tenant->users()->first();
                    $recipientEmail = $firstUser ? $firstUser->email : null;
                }
            }

            if ($recipientEmail) {
                $this->info("Sending tax reminders to {$tenant->name} ({$recipientEmail})...");
                try {
                    Mail::to($recipientEmail)->send(new TaxDueReminderMail($tenant, $taxes));
                } catch (\Exception $e) {
                    $this->error("Failed to send mail to {$recipientEmail}: " . $e->getMessage());
                }
            } else {
                $this->warn("No email address found for Tenant {$tenant->name} (ID: {$tenantId})");
            }
        }

        $this->info('Checks completed!');
    }
}
