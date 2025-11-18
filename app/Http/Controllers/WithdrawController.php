<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateWithdrawalRequest;
use App\Jobs\SimulateWebhookJob;
use App\Operations\CreateWithdrawal;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class WithdrawController extends Controller
{
    public function store(CreateWithdrawalRequest $request, CreateWithdrawal $createWithdrawal): JsonResponse
    {
        return DB::transaction(function () use ($request, $createWithdrawal): JsonResponse {
            $withdrawal = $createWithdrawal->execute($request->validated());
            SimulateWebhookJob::dispatch($withdrawal);

            return response()->json($withdrawal->fresh(), Response::HTTP_CREATED);
        });
    }
}
