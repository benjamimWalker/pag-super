<?php

namespace App\Operations;

use App\Models\PixTransaction;
use App\Models\User;
use App\Services\SubacquirerManager;

readonly class CreatePix
{
    public function __construct(private SubacquirerManager $manager)
    {
    }

    public function execute(array $data): PixTransaction
    {
        $user = User::select(['id', 'name', 'subacquirer_id'])
            ->with('subacquirer:id,slug')
            ->findOrFail($data['user_id']);

        $subacquirer = $user->subacquirer;

        $pix = PixTransaction::create([
            'user_id' => $user->id,
            'subacquirer_id' => $subacquirer->id,
            'amount' => $data['amount'],
            'status' => 'PENDING',
        ]);

        $adapter = $this->manager->forUser($user);
        $response = $adapter->createPix($user, [
            'amount' => $data['amount'],
            'order_id' => $data['order_id'] ?? null,
            'local_id' => $pix->id,
            'payer' => $data['payer'] ?? ['name' => $user->name, 'cpf_cnpj' => null],
            'expires_in' => $data['expires_in'] ?? 3600
        ]);

        $pix->update([
            'external_id' => $response['external_id'] ?? null,
            'payload' => $response['raw_response'] ?? $response
        ]);

        return $pix;
    }
}
