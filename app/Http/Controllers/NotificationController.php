<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\NotificationDispatch;
use App\Models\NotificationPreference;
use App\Services\Notification\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Display a listing of in-app notifications for the authenticated user.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $notifications = Notification::where('user_id', $user->id)
            ->latest('id')
            ->paginate(15);

        return view('notifications.index', compact('notifications'));
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(int $id): JsonResponse|RedirectResponse
    {
        $notification = Notification::where('user_id', Auth::id())->findOrFail($id);
        $notification->markAsRead();

        if (request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        $targetUrl = $notification->data['url'] ?? null;
        if ($targetUrl) {
            return redirect($targetUrl);
        }

        return back()->with('success', 'Notification marked as read.');
    }

    /**
     * Mark all notifications as read for current user.
     */
    public function markAllAsRead(): RedirectResponse
    {
        $this->notificationService->markAllAsRead(Auth::user());

        return back()->with('success', 'All notifications marked as read.');
    }

    /**
     * T263: Display Notification Preferences Matrix.
     */
    public function preferences(): View
    {
        $user = Auth::user();
        $categories = NotificationPreference::supportedCategories();
        $channels = NotificationPreference::supportedChannels();

        // Load existing user preferences
        $userPreferences = NotificationPreference::where('user_id', $user->id)->get();
        $matrix = [];

        foreach ($categories as $catKey => $catInfo) {
            foreach ($channels as $chanKey => $chanName) {
                if ($catInfo['mandatory']) {
                    $matrix[$catKey][$chanKey] = true;
                } else {
                    $pref = $userPreferences->first(fn ($p) => $p->category === $catKey && $p->channel === $chanKey);
                    $matrix[$catKey][$chanKey] = $pref ? (bool) $pref->is_enabled : true;
                }
            }
        }

        return view('notifications.preferences', compact('categories', 'channels', 'matrix'));
    }

    /**
     * T263: Update Notification Preferences.
     */
    public function updatePreferences(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $categories = NotificationPreference::supportedCategories();
        $channels = NotificationPreference::supportedChannels();
        $inputs = $request->input('preferences', []);

        foreach ($categories as $catKey => $catInfo) {
            // Mandatory categories cannot be updated/disabled
            if ($catInfo['mandatory']) {
                continue;
            }

            foreach (array_keys($channels) as $chanKey) {
                $isEnabled = isset($inputs[$catKey][$chanKey]) && $inputs[$catKey][$chanKey] == '1';

                NotificationPreference::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'category' => $catKey,
                        'channel' => $chanKey,
                    ],
                    [
                        'is_enabled' => $isEnabled,
                    ]
                );
            }
        }

        return back()->with('success', 'Notification preferences updated successfully.');
    }

    /**
     * T266: Display Notification Dispatches Audit Log.
     */
    public function dispatches(Request $request): View
    {
        $user = Auth::user();

        $query = NotificationDispatch::with(['user']);

        // Non-super-admins only see their own dispatches
        if (!$user->isSuperAdmin()) {
            $query->where('user_id', $user->id);
        }

        if ($request->filled('channel')) {
            $query->where('channel', $request->input('channel'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        $dispatches = $query->latest('id')->paginate(20)->withQueryString();

        return view('notifications.dispatches', compact('dispatches'));
    }
}
