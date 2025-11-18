<?php

namespace App\Operations;

use App\Models\PixTransaction;
use App\Models\User;
use App\Models\Withdrawal;
use App\Services\SubacquirerManager;

readonly class CreateWithdrawal
{
    public function __construct(private SubacquirerManager $manager)
    {
    }

    public function execute(array $data): Withdrawal
    {
        $user = User::select(['id', 'name', 'subacquirer_id'])
            ->with('subacquirer:id,slug')
            ->findOrFail($data['user_id']);

        $subacquirer = $user->subacquirer;

        $withdrawal = Withdrawal::create([
            'user_id' => $user->id,
            'subacquirer_id' => $subacquirer->id,
            'amount' => $data['amount'],
            'status' => 'PENDING',
        ]);

        $adapter = $this->manager->forUser($user);

        $response = $adapter->requestWithdraw([
            'amount' => $data['amount'],
            'account' => $data['account'],
            'transaction_id' => $data['transaction_id'] ?? null,
            'mock_response' => $data['mock_response'] ?? null,
        ]);

        $withdrawal->update([
            'external_id' => $response['external_id'] ?? null,
            'payload' => $response['raw_response'] ?? $response,
        ]);

        return $withdrawal;
    }
}
