<?php

namespace App\Jobs;

use App\Models\PixTransaction;
use App\Models\Withdrawal;
use App\Services\SubacquirerManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessWebhookJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels, Dispatchable;


    public function __construct(private readonly string $subacquirerSlug, private readonly array $payload)
    {
    }

    public function handle(SubacquirerManager $manager): void
    {
        $adapter = $manager->resolve($this->subacquirerSlug);

        try {
            $normalized = $adapter->normalizeWebhook($this->payload);
        } catch (Throwable $e) {
            Log::error('Webhook normalization failed: ' . $e->getMessage(), ['payload' => $this->payload]);
            return;
        }

        if ($normalized['type'] === 'pix') {
            $pix = PixTransaction::select(['id', 'payload'])
                ->where('external_id', $normalized['external_id'])
                ->first();

            if (!$pix) {
                Log::warning('PIX not found for webhook', ['external_id' => $normalized['external_id']]);
                return;
            }

            $pix->updateOrFail([
                'status' => $normalized['status'],
                'payload' => array_merge($pix->payload ?? [], ['last_webhook' => $this->payload]),
            ]);
        } else if ($normalized['type'] === 'withdraw') {
            $withdrawal = Withdrawal::select(['id', 'payload'])
                ->where('external_id', $normalized['external_id'])
                ->first();

            if (!$withdrawal) {
                Log::warning('Withdrawal not found for webhook', ['external_id' => $normalized['external_id']]);
                return;
            }

            $withdrawal->update([
                'status' => $normalized['status'],
                'payload' => array_merge($withdrawal->payload ?? [], ['last_webhook' => $this->payload]),
            ]);
        }
    }

    public function fail($exception = null)
    {
        Log::error('Failed to process a webhook of payload: ' . json_encode($this->payload), $exception);
    }
}
