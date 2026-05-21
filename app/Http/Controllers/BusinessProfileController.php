<?php

namespace App\Http\Controllers;

use App\Http\Requests\BusinessProfileRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class BusinessProfileController extends Controller
{
    /**
     * Display the business profile form.
     */
    public function show(Request $request): Response
    {
        return Inertia::render('BusinessProfile/Form', [
            'mustVerifyEmail' => $request->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail,
            'status' => session('status'),
            'profile' => $request->user()->businessProfile,
        ]);
    }

    /**
     * Create or update the business profile.
     */
    public function store(BusinessProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        if ($user->businessProfile) {
            $user->businessProfile->update($validated);
            return Redirect::back();
        } else {
            $user->businessProfile()->create($validated);
            return Redirect::route('dashboard')->with('status', 'Business profile saved successfully.');
        }
    }
    /**
     * Validate TIN using MyInvois API.
     */
    public function validateTin(Request $request)
    {
        $request->validate([
            'tin' => 'required|string',
            'registration_number' => 'required|string',
            'client_id' => 'required|string',
            'client_secret' => 'required|string',
        ]);

        try {
            $myInvois = new \Laraditz\MyInvois\MyInvois(
                is_sandbox: env('MYINVOIS_SANDBOX', true), 
                client_id: $request->client_id, 
                client_secret: $request->client_secret
            );
            
            $myInvois->auth()->token(
                client_id: $request->client_id, 
                client_secret: $request->client_secret, 
                grant_type: 'client_credentials', 
                scope: 'InvoicingAPI'
            );
            
            $idType = str_starts_with($request->tin, 'IG') ? 'NRIC' : 'BRN';
            
            $result = $myInvois->taxpayer()->validateTin(
                payload: [
                    'tin' => $request->tin, 
                    'idType' => $idType, 
                    'idValue' => $request->registration_number
                ]
            );

            if ($result['success'] ?? false) {
                return response()->json(['valid' => true, 'message' => 'TIN is valid and active!']);
            }
            
            return response()->json(['valid' => false, 'message' => 'TIN validation failed. Please check your TIN and Registration Number.']);
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            preg_match('/\{.*\}/s', $msg, $matches);
            if ($matches) {
                $error = json_decode($matches[0], true);
                if (isset($error['error']['message'])) {
                    return response()->json(['valid' => false, 'message' => $error['error']['message']]);
                }
            }
            return response()->json(['valid' => false, 'message' => 'Error: ' . $msg]);
        }
    }
}
