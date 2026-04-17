<?php

namespace App\Http\Controllers;

use App\Models\UserActivity;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserActivityController extends Controller
{
    /**
     * Display a listing of user activities.
     */
    public function index(Request $request): Response
    {
        // For admin users we might show all, but here we show activities of the logged-in user
        $activities = UserActivity::where('user_id', $request->user()->id)
            ->latest()
            ->paginate(15);

        return Inertia::render('ActivityLogs/Index', [
            'activities' => $activities,
        ]);
    }
}
