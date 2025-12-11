<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class NrcController extends Controller
{
    /**
     * Get NRC codes/townships and citizenship options.
     */
    public function index(): JsonResponse
    {
        $citizenships = config('nrc_codes.citizenships', []);
        $entries = config('nrc_codes.entries', []);

        return response()->json([
            'message' => 'NRC codes retrieved successfully',
            'citizenships' => $citizenships,
            'data' => $entries,
        ]);
    }
}

