<?php

namespace App\Contracts;

use App\Models\PixTransaction;
use App\Models\Withdrawal;
use App\Models\User;

interface SubacquirerInterface
{
    public function createPix(User $user, array $payload): array;

    public function requestWithdraw(array $payload): array;

    public function normalizeWebhook(array $payload): array;

    public function makeSimulatedWebhookForPix(PixTransaction $pix): array;

    public function makeSimulatedWebhookForWithdraw(Withdrawal $withdraw): array;
}
