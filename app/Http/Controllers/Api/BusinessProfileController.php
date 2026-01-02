<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BusinessProfileRequest;
use App\Http\Resources\BusinessProfileResource;
use App\Models\BusinessProfile;
use Illuminate\Http\JsonResponse;

class BusinessProfileController extends Controller
{
    /**
     * Get user's business profile
     * 
     * @group Business Profile
     */
    public function show(): JsonResponse
    {
        $profile = auth()->user()->businessProfile;

        if (!$profile) {
            return response()->json([
                'message' => 'Business profile not found',
            ], 404);
        }

        return response()->json([
            'data' => new BusinessProfileResource($profile),
        ]);
    }

    /**
     * Create or update business profile
     * 
     * @group Business Profile
     */
    public function createOrUpdate(BusinessProfileRequest $request): JsonResponse
    {
        $profile = auth()->user()->businessProfile;

        if ($profile) {
            $profile->update($request->validated());
            $message = 'Business profile updated successfully';
        } else {
            $profile = auth()->user()->businessProfile()->create($request->validated());
            $message = 'Business profile created successfully';
        }

        return response()->json([
            'message' => $message,
            'data' => new BusinessProfileResource($profile),
        ]);
    }

    /**
     * Delete business profile
     * 
     * @group Business Profile
     */
    public function destroy(): JsonResponse
    {
        $profile = auth()->user()->businessProfile;

        if (!$profile) {
            return response()->json([
                'message' => 'Business profile not found',
            ], 404);
        }

        $profile->delete();

        return response()->json([
            'message' => 'Business profile deleted successfully',
        ]);
    }
}