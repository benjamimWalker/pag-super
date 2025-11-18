<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreatePixRequest;
use App\Jobs\SimulateWebhookJob;
use App\Operations\CreatePix;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class PixController extends Controller
{
    public function store(CreatePixRequest $request, CreatePix $createPix): JsonResponse
    {
        return DB::transaction(function () use ($request, $createPix): JsonResponse {
            $pix = $createPix->execute($request->validated());
            SimulateWebhookJob::dispatch($pix);

            return response()->json($pix->fresh(), Response::HTTP_CREATED);
        });
    }
}
