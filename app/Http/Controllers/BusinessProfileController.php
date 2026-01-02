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
        } else {
            $user->businessProfile()->create($validated);
        }

        return Redirect::route('dashboard')->with('status', 'Business profile saved successfully.');
    }
}
