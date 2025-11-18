<?php

namespace App\Services;

use App\Contracts\SubacquirerInterface;
use App\Models\PixTransaction;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SubadqAService implements SubacquirerInterface
{
    protected string $base;

    protected string $merchant;

    public function __construct()
    {
        $config = config('subacquirers.defaults.subadqa', []);
        $this->base = $config['base_url'] ?? '';
        $this->merchant = $config['merchant_id'] ?? 'm123';
    }

    public function createPix(User $user, array $payload): array
    {
        $body = [
            'merchant_id' => $this->merchant,
            'amount' => (int) round(($payload['amount'] ?? 0) * 100),
            'currency' => 'BRL',
            'order_id' => $payload['order_id'] ?? 'order_'.$payload['local_id'] ?? uniqid(),
            'payer' => $payload['payer'] ?? ['name' => $user->name, 'cpf_cnpj' => null],
            'expires_in' => $payload['expires_in'] ?? 3600,
        ];

        $response = Http::withHeader('x-mock-response-name', 'SUCESSO_PIX')
            ->post(rtrim($this->base, '/').'/pix/create', $body);

        if (! $response->successful()) {
            Log::error("Error while communicating with subacquire $this->base", $response->json());
            throw new RuntimeException($response->body());
        }

        $json = $response->json();

        return [
            'external_id' => $json['transaction_id'] ?? null,
            'raw_response' => $json,
        ];
    }

    public function requestWithdraw(array $payload): array
    {
        $body = [
            'merchant_id' => $this->merchant,
            'account' => $payload['account'],
            'amount' => (int) round(($payload['amount'] ?? 0) * 100),
            'transaction_id' => $payload['transaction_id'] ?? uniqid('tx_'),
        ];

        $response = Http::withHeader('x-mock-response-name', 'SUCESSO_WD')
            ->post(rtrim($this->base, '/').'/withdraw', $body);

        if (! $response->successful()) {
            Log::error("Error while communicating with subacquire $this->base", $response->json());
            throw new RuntimeException($response->body());
        }

        $json = $response->json();

        return [
            'external_id' => $json['withdraw_id'] ?? null,
            'raw_response' => $json,
            'pix_id' => $json['pix_id'] ?? 'PIX',
        ];
    }

    public function normalizeWebhook(array $payload): array
    {
        if (isset($payload['event']) && str_contains($payload['event'], 'pix')) {
            return [
                'type' => 'pix',
                'status' => $payload['status'] ?? ($payload['event'] === 'pix_payment_confirmed' ? 'CONFIRMED' : 'PENDING'),
                'external_id' => $payload['transaction_id'] ?? null,
                'amount' => $payload['amount'] ?? null,
                'data' => $payload,
            ];
        }

        if (isset($payload['event']) && str_contains($payload['event'], 'withdraw')) {
            return [
                'type' => 'withdraw',
                'status' => $payload['status'] ?? null,
                'external_id' => $payload['withdraw_id'] ?? null,
                'amount' => $payload['amount'] ?? null,
                'data' => $payload,
            ];
        }

        throw new RuntimeException('Unknown webhook payload for SubadqA');
    }

    public function makeSimulatedWebhookForPix(PixTransaction $pix): array
    {
        return [
            [
                'event' => 'pix_created',
                'transaction_id' => $pix->payload['transaction_id'] ?? null,
                'pix_id' => 'PIX'.$pix->id,
                'status' => 'PENDING',
                'amount' => $pix->amount,
            ],
            [
                'event' => 'pix_payment_confirmed',
                'transaction_id' => $pix->payload['transaction_id'] ?? null,
                'pix_id' => 'PIX'.$pix->id,
                'status' => 'CONFIRMED',
                'amount' => $pix->amount,
                'payer_name' => 'Fulano',
                'payer_cpf' => '00000000000',
                'payment_date' => now()->toIso8601String(),
            ],
        ];
    }

    public function makeSimulatedWebhookForWithdraw(Withdrawal $withdraw): array
    {
        return [
            [
                'event' => 'withdraw_processing',
                'withdraw_id' => $withdraw->payload['withdraw_id'] ?? null,
                'status' => 'PROCESSING',
                'amount' => $withdraw->amount,
            ],
            [
                'event' => 'withdraw_completed',
                'withdraw_id' => $withdraw->payload['withdraw_id'] ?? null,
                'transaction_id' => 'T'.$withdraw->id,
                'status' => 'SUCCESS',
                'amount' => $withdraw->amount,
                'completed_at' => now()->toIso8601String(),
            ],
        ];
    }
}
