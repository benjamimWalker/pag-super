<?php

namespace App\Jobs;

use App\Models\PixTransaction;
use App\Models\Subacquirer;
use App\Models\Withdrawal;
use App\Services\SubacquirerManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SimulateWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly PixTransaction|Withdrawal $resource) {}

    public function handle(SubacquirerManager $manager): void
    {
        $subacquirer = Subacquirer::find($this->resource->subacquirer_id);
        $adapter = $manager->resolve($subacquirer->slug);

        if ($this->resource instanceof PixTransaction) {
            $payloads = $adapter->makeSimulatedWebhookForPix($this->resource);
        } else {
            $payloads = $adapter->makeSimulatedWebhookForWithdraw($this->resource);
        }

        foreach ($payloads as $payload) {
            ProcessWebhookJob::dispatch($subacquirer->slug, $payload);
        }
    }
}
