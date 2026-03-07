<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PermanentDeleteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PermanentDeleteController extends Controller
{
    public function preview(Request $request, PermanentDeleteService $permanentDeleteService): JsonResponse
    {
        $validated = $request->validate([
            'entity' => ['required', 'string'],
            'id' => ['required', 'integer', 'min:1'],
        ]);

        $actor = $request->user();
        if (!$actor || !$permanentDeleteService->isAllowedFor($actor)) {
            return response()->json([
                'ok' => false,
                'reason' => __('message.permanent_delete_not_allowed'),
            ], 403);
        }

        if (!in_array($validated['entity'], $permanentDeleteService->supportedEntities(), true)) {
            return response()->json([
                'ok' => false,
                'reason' => 'Unsupported entity.',
            ], 422);
        }

        $model = $permanentDeleteService->resolveEntityModel($validated['entity'], (int) $validated['id']);
        if (!$model) {
            return response()->json([
                'ok' => false,
                'reason' => 'Data not found in current scope.',
            ], 404);
        }

        $counts = $permanentDeleteService->dependencyCounts($validated['entity'], (int) $validated['id']);
        $blocking = $permanentDeleteService->onlyBlockingDependencies($counts);
        $blocked = !empty($blocking);

        return response()->json([
            'ok' => true,
            'blocked' => $blocked,
            'dependencies' => $blocking,
            'dependency_text' => $permanentDeleteService->formatDependencies($blocking),
        ]);
    }
}
