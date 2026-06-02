<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class VerifyEmailController extends Controller
{
    /**
     * Mark the user's email address as verified.
     * Handles both authenticated and unauthenticated users.
     */
    public function __invoke(Request $request)
    {
        // Get user from ID in the URL
        $user = User::findOrFail($request->route('id'));

        // Verify the hash matches the user's email
        if (!hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification()))) {
            return view('auth.verify-email-invalid');
        }

        // If already verified, show success
        if ($user->hasVerifiedEmail()) {
            return view('auth.verify-email-success');
        }

        // Mark email as verified
        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return view('auth.verify-email-success');
    }
}
