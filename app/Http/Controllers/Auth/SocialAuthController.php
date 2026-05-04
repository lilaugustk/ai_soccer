<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            // Bypass SSL verification for local development (fixes cURL error 60)
            $httpClient = new Client([
                'verify' => false,
            ]);

            /** @var \Laravel\Socialite\Two\GoogleProvider $provider */
            $provider = Socialite::driver('google');

            $googleUser = $provider
                ->setHttpClient($httpClient)
                ->stateless()
                ->user();
            
            $user = User::where('google_id', $googleUser->id)
                        ->orWhere('email', $googleUser->email)
                        ->first();

            if ($user) {
                // Update google_id if not set
                if (!$user->google_id) {
                    $user->update([
                        'google_id' => $googleUser->id,
                        'avatar' => $googleUser->avatar
                    ]);
                }
            } else {
                // Create new user
                $user = User::create([
                    'name' => $googleUser->name,
                    'email' => $googleUser->email,
                    'google_id' => $googleUser->id,
                    'avatar' => $googleUser->avatar,
                    'password' => null, // No password for social logins
                ]);
            }

            Auth::login($user);

            return redirect()->intended('/');
        } catch (\Exception $e) {
            Log::error('Google Login Error: ' . $e->getMessage());
            return redirect('/login')->withErrors(['email' => 'Không thể đăng nhập bằng Google. Lỗi: ' . $e->getMessage()]);
        }
    }
}
