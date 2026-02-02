<?php

namespace App\Jobs;

use App\Models\RouteSubscription;
use App\Services\TelegramService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckRouteSafetyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(TelegramService $telegram): void
    {
        Log::info("Running CheckRouteSafetyJob...");

        // Fetch active subscriptions
        $subscriptions = RouteSubscription::where('is_active', true)->get();

        foreach ($subscriptions as $sub) {
            $origin = $sub->originPort;
            $destination = $sub->destinationPort;

            if (!$origin || !$destination) {
                // Invalid data, deactivate
                $sub->update(['is_active' => false]);
                continue;
            }

            // Check Weather
            $originWeather = $origin->weatherData()->latest()->first();
            $destWeather = $destination->weatherData()->latest()->first();

            $isOriginSafe = $originWeather && $originWeather->risk_status !== 'High Risk';
            $isDestSafe = $destWeather && $destWeather->risk_status !== 'High Risk';

            if ($isOriginSafe && $isDestSafe) {
                // IT IS SAFE! Notify User
                $msg = "🔔 <b>Good News! Safe to Travel</b>\n\n".
                       "The weather conditions for your subscribed route:\n".
                       "⚓ <b>{$origin->name}</b> ➡️ <b>{$destination->name}</b>\n\n".
                       "Current status is now acceptable for sailing.\n".
                       "✅ <b>Origin Risk:</b> " . ($originWeather->risk_status ?? 'Unknown') . "\n".
                       "✅ <b>Dest Risk:</b> " . ($destWeather->risk_status ?? 'Unknown') . "\n\n".
                       "<i>This subscription is now fulfilled and has been turned off.</i>";

                $telegram->sendMessage($sub->chat_id, $msg);

                // Deactivate subscription
                $sub->update(['is_active' => false]);
                
                Log::info("Notified {$sub->chat_id} about safe route {$origin->name}->{$destination->name}");
            }
        }
    }
}
