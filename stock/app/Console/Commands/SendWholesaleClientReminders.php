<?php

namespace App\Console\Commands;

use App\WholesaleShipment;
use App\Http\Controllers\WholesalePoController;
use Illuminate\Console\Command;

class SendWholesaleClientReminders extends Command
{
    protected $signature = 'wholesale:send-client-reminders';

    protected $description = 'Send wholesale client reminders (Harsh & Anu): first after 7 days, then every 3 days until delivery date';

    public function handle()
    {
        $today = now()->toDateString();
        $controller = app(WholesalePoController::class);

        $shipments = WholesaleShipment::whereNotIn('status', ['closed'])
            ->whereNotNull('next_reminder_at')
            ->whereDate('next_reminder_at', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('delivery_date')
                    ->orWhereDate('delivery_date', '>=', $today);
            })
            ->get();

        $sent = 0;
        foreach ($shipments as $shipment) {
            try {
                $controller->dispatchReminder($shipment, false);
                $sent++;
                $this->info('Reminded: ' . $shipment->buyer_orderno);
            } catch (\Throwable $e) {
                $this->error('Failed ' . $shipment->buyer_orderno . ': ' . $e->getMessage());
                \Log::error('wholesale reminder cron failed', [
                    'shipment_id' => $shipment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Done. Sent {$sent} reminder(s).");

        return 0;
    }
}
