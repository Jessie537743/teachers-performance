<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Records that the current user finished (or skipped) a guided tour. The
 * tour key is one of: admin | dean | faculty | student | hr.
 *
 * Used by the in-app Driver.js tour to avoid auto-showing the same flow on
 * every login. Users can still replay manually from the user menu — that
 * doesn't change the completed_tours list.
 */
class TourController extends Controller
{
    public function complete(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tour' => ['required', 'string', 'max:32', 'regex:/^[a-z0-9_-]+$/'],
        ]);

        $user = $request->user();
        if (!$user) {
            return response()->json(['ok' => false, 'message' => 'Not authenticated.'], 401);
        }

        $completed = (array) ($user->completed_tours ?? []);
        if (!in_array($data['tour'], $completed, true)) {
            $completed[] = $data['tour'];
            $user->completed_tours = array_values(array_unique($completed));
            $user->save();
        }

        return response()->json([
            'ok'              => true,
            'completed_tours' => $user->completed_tours,
        ]);
    }
}
