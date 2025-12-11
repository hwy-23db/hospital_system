<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ward;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WardController extends Controller
{
    /**
     * Get hospital wards and their room numbers for admission forms.
     * Available to all authenticated users for admission forms.
     */
    public function getWards(Request $request): JsonResponse
    {
        $wards = Ward::getWardsWithRooms();

        return response()->json([
            'message' => 'Hospital wards retrieved successfully',
            'data' => $wards,
        ]);
    }

    /**
     * Get rooms for a specific ward.
     * Useful for dynamic room loading based on ward selection.
     */
    public function getRoomsForWard(Request $request, string $wardKey): JsonResponse
    {
        if (!Ward::wardExists($wardKey)) {
            return response()->json([
                'message' => 'Ward not found',
                'data' => [],
            ], 404);
        }

        $rooms = Ward::getRoomsForWard($wardKey);

        return response()->json([
            'message' => 'Rooms retrieved successfully',
            'data' => $rooms,
        ]);
    }
}
