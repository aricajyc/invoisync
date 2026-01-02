<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class ReferenceDataController extends Controller
{
    /**
     * Get list of countries.
     */
    /**
     * Get list of countries.
     */
    public function getCountries(): JsonResponse
    {
        $path = public_path('codes/CountryCodes.json');
        if (!file_exists($path)) {
            return response()->json([], 500);
        }
        $data = json_decode(file_get_contents($path), true);
        return response()->json($data);
    }

    /**
     * Get list of states.
     */
    public function getStates(): JsonResponse
    {
        $path = public_path('codes/StateCodes.json');
        if (!file_exists($path)) {
            return response()->json([], 500);
        }
        $data = json_decode(file_get_contents($path), true);
        return response()->json($data);
    }

    /**
     * Get list of MSIC codes.
     */
    public function getMsicCodes(): JsonResponse
    {
        $path = public_path('codes/MSICSubCategoryCodes.json');
        if (!file_exists($path)) {
            return response()->json([], 500);
        }
        $data = json_decode(file_get_contents($path), true);
        return response()->json($data);
    }
}
