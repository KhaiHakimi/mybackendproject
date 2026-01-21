<?php

namespace App\Jobs;

use App\Models\Port;
use App\Services\WeatherService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class FetchWeatherForPort implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Port $port
    ) {}

    /**
     * Execute the job.
     */
    public function handle(WeatherService $service): void
    {
        $service->updateWeatherForPort($this->port);
    }
}
