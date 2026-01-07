<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
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

    /**
     * Get list of unit types with optional filtering.
     */
    public function getUnitTypes(Request $request): JsonResponse
    {
        $path = public_path('codes/UnitTypes.json');
        if (!file_exists($path)) {
            return response()->json([], 500);
        }

        // Cache the full list for 24 hours to avoid reading file constantly
        $allUnits = \Illuminate\Support\Facades\Cache::remember('ref_unit_types', 60 * 60 * 24, function () use ($path) {
            return json_decode(file_get_contents($path), true);
        });

        $query = $request->input('q');

        if (empty($query)) {
            // Return only first 50 if no query to prevent massive payload
            return response()->json(array_slice($allUnits, 0, 50));
        }

        $query = strtolower($query);
        $filtered = array_filter($allUnits, function ($item) use ($query) {
            return str_contains(strtolower($item['Name']), $query) || 
                   str_contains(strtolower($item['Code']), $query);
        });

        // Limit results to 50
        return response()->json(array_slice(array_values($filtered), 0, 50));
    }
}
