<?php

namespace App\Http\Controllers;

use App\Models\LogActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogActivityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = LogActivity::with('user');

        // Filter by activity type
        if ($request->has('activity_type') && $request->activity_type != '') {
            $query->where('activity_type', $request->activity_type);
        }

        // Filter by user
        if ($request->has('user_id') && $request->user_id != '') {
            $query->where('user_id', $request->user_id);
        }

        // Filter by date range
        if ($request->has('date_from') && $request->date_from != '') {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to != '') {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search
        if ($request->has('search') && $request->search != '') {
            $query->where('description', 'like', '%' . $request->search . '%');
        }

        $logs = $query->latest()->paginate(20);

        return view('auth.log-activity.index', compact('logs'));
    }
    public function store(Request $request)
    {
        $request->validate([
            'activity_type' => 'required|string|max:100',
            'description'   => 'nullable|string',
        ]);

        LogActivity::create([
            'user_id'       => Auth::id(),
            'activity_type' => $request->activity_type,
            'description'   => $request->description,
            'ip_address'    => $request->ip(),
            'user_agent'    => $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Log activity created successfully.'
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(LogActivity $logActivity)
    {
        $logActivity->load('user');
        return view('auth.log-activity.show', compact('logActivity'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(LogActivity $logActivity)
    {
        $logActivity->delete();

        return redirect()->route('log-activity.index')
            ->with('success', 'Log activity deleted successfully.');
    }

    /**
     * Delete old logs.
     */
    public function deleteOldLogs(Request $request)
    {
        $request->validate([
            'days' => 'required|integer|min:1'
        ]);

        $date = now()->subDays($request->days);
        $count = LogActivity::where('created_at', '<', $date)->delete();

        return redirect()->route('log-activity.index')
            ->with('success', "Successfully deleted {$count} old log(s).");
    }

    /**
     * Static method to create a log entry.
     */
    public static function log($activityType, $description = null)
    {
        LogActivity::create([
            'user_id' => Auth::id(),
            'activity_type' => $activityType,
            'description' => $description,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
