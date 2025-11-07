<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use NotificationChannels\WebPush\PushSubscription;

class PushNotificationController extends Controller
{
    /**
     * Subscribe user to push notifications
     */
    public function subscribe(Request $request)
    {
        // Try to get authenticated user from either web or api guard
        $user = Auth::guard('web')->user() ?? Auth::guard('api')->user();
        
        // Debug logging
        \Log::info('Push subscribe request received', [
            'has_session' => $request->hasSession(),
            'session_id' => $request->hasSession() ? $request->session()->getId() : null,
            'web_auth' => Auth::guard('web')->check(),
            'web_user' => Auth::guard('web')->id(),
            'api_auth' => Auth::guard('api')->check(),
            'api_user' => Auth::guard('api')->id(),
            'has_bearer' => $request->bearerToken() !== null,
            'cookies' => array_keys($request->cookies->all()),
            'user_found' => $user !== null,
        ]);
        
        if (!$user) {
            \Log::warning('Push subscribe failed - no authenticated user');
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated. Please log in.',
                'debug' => [
                    'web_auth' => Auth::guard('web')->check(),
                    'api_auth' => Auth::guard('api')->check(),
                ],
            ], 401);
        }
        
        $validated = $request->validate([
            'endpoint' => 'required|string',
            'keys' => 'required|array',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
        ]);

        // Check if subscription already exists
        $existingSubscription = $user->pushSubscriptions()
            ->where('endpoint', $validated['endpoint'])
            ->first();

        if ($existingSubscription) {
            // Update existing subscription
            $existingSubscription->update([
                'public_key' => $validated['keys']['p256dh'],
                'auth_token' => $validated['keys']['auth'],
                'content_encoding' => $request->input('contentEncoding', 'aesgcm'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Push notification subscription updated',
            ]);
        }

        // Create new subscription
        $user->updatePushSubscription(
            $validated['endpoint'],
            $validated['keys']['p256dh'],
            $validated['keys']['auth'],
            $request->input('contentEncoding', 'aesgcm')
        );

        return response()->json([
            'success' => true,
            'message' => 'Push notification subscription created',
        ]);
    }

    /**
     * Unsubscribe user from push notifications
     */
    public function unsubscribe(Request $request)
    {
        $validated = $request->validate([
            'endpoint' => 'required|string',
        ]);

        $user = Auth::guard('web')->user() ?? Auth::guard('api')->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $user->pushSubscriptions()
            ->where('endpoint', $validated['endpoint'])
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Push notification subscription removed',
        ]);
    }

    /**
     * Get VAPID public key for browser
     */
    public function getPublicKey()
    {
        return response()->json([
            'publicKey' => config('webpush.vapid.public_key'),
        ]);
    }

    /**
     * Send a test notification
     */
    public function sendTest(Request $request)
    {
        $user = Auth::guard('web')->user() ?? Auth::guard('api')->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        // Send test notification
        $user->notify(new \App\Notifications\TestPushNotification(
            $request->input('title', 'Test Notification'),
            $request->input('body', 'This is a test push notification from ElevateGS!'),
            $request->input('url', route('dashboard'))
        ));

        return response()->json([
            'success' => true,
            'message' => 'Test notification sent',
        ]);
    }
}
