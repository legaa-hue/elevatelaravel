<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Notifications\EmailVerificationLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Inertia\Inertia;

class EmailVerificationController extends Controller
{
    /**
     * Send verification link to email
     */
    public function send(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'first_name' => 'required|string',
            ]);

            // Generate unique token
            $token = Str::random(64);

            // Delete any existing tokens for this email
            DB::table('email_verification_codes')
                ->where('email', $request->email)
                ->delete();

            // Store verification token
            DB::table('email_verification_codes')->insert([
                'email' => $request->email,
                'code' => $token, // Using 'code' column to store token
                'expires_at' => Carbon::now()->addMinutes(30),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            // Generate verification URL
            $verificationUrl = route('email.verify-link', ['token' => $token]);

            // Send email with link
            Notification::route('mail', $request->email)
                ->notify(new EmailVerificationLink($verificationUrl, $request->first_name));

            return response()->json([
                'success' => true,
                'message' => 'Verification link sent to your email.',
            ]);
        } catch (\Exception $e) {
            \Log::error('Email verification link send failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send verification link: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verify email via link
     */
    public function verifyLink(Request $request, string $token)
    {
        $verification = DB::table('email_verification_codes')
            ->where('code', $token)
            ->first();

        if (!$verification) {
            return Inertia::render('Auth/EmailVerificationResult', [
                'success' => false,
                'message' => 'Verification link is invalid or has already been used.',
            ]);
        }

        // Check if expired
        if (Carbon::parse($verification->expires_at)->isPast()) {
            DB::table('email_verification_codes')
                ->where('code', $token)
                ->delete();

            return Inertia::render('Auth/EmailVerificationResult', [
                'success' => false,
                'message' => 'Verification link has expired. Please request a new one.',
                'email' => $verification->email,
            ]);
        }

        // Mark email as verified
        DB::table('email_verification_codes')
            ->where('code', $token)
            ->update(['verified' => true]);

        return Inertia::render('Auth/EmailVerificationResult', [
            'success' => true,
            'message' => 'Email verified successfully! You can now complete your registration.',
            'email' => $verification->email,
        ]);
    }

    /**
     * Check if email is verified
     */
    public function checkVerified(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $verification = DB::table('email_verification_codes')
            ->where('email', $request->email)
            ->where('verified', true)
            ->first();

        return response()->json([
            'verified' => $verification !== null,
        ]);
    }
}
