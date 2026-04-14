<?php

namespace App\Modules\FrontendModule\Http\Controllers;

use App\Modules\AuthModule\UseCases\LoginUser\LoginUser;
use App\Modules\AuthModule\UseCases\Register\RegisterUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AuthPageController extends Controller
{
    public function __construct(
        private LoginUser $loginUser,
        private RegisterUser $registerUser,
    ) {}

    public function loginForm(): Response|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Auth/Login');
    }

    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $result = $this->loginUser->execute(
            email:     $validated['email'],
            password:  $validated['password'],
            issueToken: false,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        if (!$result->success) {
            return back()->withErrors(['email' => 'These credentials do not match our records.'])->onlyInput('email');
        }

        Auth::login($result->user);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function registerForm(Request $request, string $token): Response
    {
        return Inertia::render('Auth/Register', [
            'token' => $token,
        ]);
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token'                 => ['required', 'string'],
            'name'                  => ['required', 'string', 'max:255'],
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
        ]);

        $result = $this->registerUser->execute(
            token:     $validated['token'],
            name:      $validated['name'],
            password:  $validated['password'],
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        if (!$result->success) {
            return back()->withErrors(['token' => match ($result->error) {
                'invitation_not_found'   => 'Invitation not found.',
                'invitation_already_used' => 'This invitation has already been used.',
                'invitation_expired'     => 'This invitation has expired.',
                default                  => 'Registration failed.',
            }]);
        }

        Auth::login($result->user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
