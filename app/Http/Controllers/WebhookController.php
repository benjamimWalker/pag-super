<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessWebhookJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function receive(Request $request): JsonResponse
    {
        $slug = $request->header('X-Subacquirer-Slug') ?? $request->input('slug');

        ProcessWebhookJob::dispatch($slug, $request->all());

        return response()->json(['ok' => true]);
    }
}
