<?php

namespace App\Services;

use App\Contracts\SubacquirerInterface;
use App\Models\PixTransaction;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SubadqBService implements SubacquirerInterface
{
    protected string $base;

    protected string $merchant;

    public function __construct()
    {
        $config = config('subacquirers.defaults.subadqb', []);
        $this->base = $config['base_url'] ?? '';
        $this->merchant = $config['merchant_id'] ?? 'm123';
    }

    public function createPix(User $user, array $payload): array
    {
        $body = [
            'seller_id' => $this->merchant,
            'amount' => (int) round(($payload['amount'] ?? 0) * 100),
            'order' => $payload['order_id'] ?? 'order_'.$payload['local_id'] ?? uniqid(),
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
        ];
    }

    public function normalizeWebhook(array $payload): array
    {
        if (isset($payload['type']) && str_contains($payload['type'], 'pix')) {
            $data = $payload['data'] ?? [];

            return [
                'type' => 'pix',
                'status' => $data['status'] ?? null,
                'external_id' => $data['id'] ?? null,
                'amount' => $data['value'] ?? null,
                'data' => $payload,
            ];
        }

        if (isset($payload['type']) && str_contains($payload['type'], 'withdraw')) {
            $data = $payload['data'] ?? [];

            return [
                'type' => 'withdraw',
                'status' => $data['status'] ?? null,
                'external_id' => $data['id'] ?? null,
                'amount' => $data['amount'] ?? null,
                'data' => $payload,
            ];
        }

        throw new RuntimeException('Unknown webhook payload for SubadqB');
    }

    public function makeSimulatedWebhookForPix(PixTransaction $pix): array
    {
        return [
            [
                'type' => 'pix.status_update',
                'data' => [
                    'id' => $pix->payload['transaction_id'] ?? null,
                    'status' => 'PROCESSING',
                    'value' => $pix->amount,
                ],
                'signature' => 'sig1',
            ],
            [
                'type' => 'pix.status_update',
                'data' => [
                    'id' => $pix->payload['transaction_id'] ?? null,
                    'status' => 'PAID',
                    'value' => $pix->amount,
                    'confirmed_at' => now()->toIso8601String(),
                    'payer' => ['name' => 'Maria Oliveira', 'document' => '98765432100'],
                ],
                'signature' => 'sig2',
            ],
        ];
    }

    public function makeSimulatedWebhookForWithdraw(Withdrawal $withdraw): array
    {
        return [
            [
                'type' => 'withdraw.status_update',
                'data' => [
                    'id' => $withdraw->payload['withdraw_id'] ?? null,
                    'status' => 'PROCESSING',
                    'amount' => $withdraw->amount,
                ],
                'signature' => 's1',
            ],
            [
                'type' => 'withdraw.status_update',
                'data' => [
                    'id' => $withdraw->payload['withdraw_id'] ?? null,
                    'status' => 'DONE',
                    'amount' => $withdraw->amount,
                    'processed_at' => now()->toIso8601String(),
                ],
                'signature' => 's2',
            ],
        ];
    }
}
