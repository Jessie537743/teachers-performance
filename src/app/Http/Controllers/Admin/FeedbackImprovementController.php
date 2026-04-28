<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\FeedbackImprovementSuggestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeedbackImprovementController extends Controller
{
    public function __construct(
        private readonly FeedbackImprovementSuggestionService $service,
    ) {}

    /**
     * Analyze (or regenerate) a single comment. Returns JSON for in-place swap.
     *
     *   POST /faculty-profiles/feedback-improvement/analyze
     *   body: { comment: string, source_kind?: string, regenerate?: bool }
     */
    public function analyze(Request $request): JsonResponse
    {
        $data = $request->validate([
            'comment'     => ['required', 'string', 'max:4000'],
            'source_kind' => ['nullable', 'string', 'in:student,dean,peer,self'],
            'regenerate'  => ['nullable', 'boolean'],
        ]);

        $result = $this->service->analyze(
            comment:         $data['comment'],
            sourceKind:      $data['source_kind'] ?? 'student',
            forceRegenerate: (bool) ($data['regenerate'] ?? false),
        );

        return response()->json($result);
    }
}
