<?php

namespace App\Modules\FrontendModule\Http\Controllers;

use App\Modules\AuthModule\UseCases\LoginUser\LoginUser;
use App\Modules\AuthModule\UseCases\Register\RegisterUser;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AuthPageController extends Controller
{
    public function __construct(
        private LoginUser $loginUser,
        private RegisterUser $registerUser,
    ) {}

    public function home(): Response|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Auth/Login');
    }

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
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $result = $this->loginUser->execute(
            email: $validated['email'],
            password: $validated['password'],
            issueToken: false,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        if (! $result->success) {
            return back()->withErrors(['email' => 'These credentials do not match our records.'])->onlyInput('email');
        }

        Auth::login($result->user);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function forgotPasswordForm(): Response|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Auth/ForgotPassword');
    }

    public function sendPasswordResetLink(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = PasswordBroker::sendResetLink($validated);

        if ($status !== PasswordBroker::RESET_LINK_SENT) {
            return back()->withErrors(['email' => __($status)])->onlyInput('email');
        }

        return back()->with('success', __($status));
    }

    public function resetPasswordForm(Request $request, string $token): Response|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Auth/ResetPassword', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
        ]);

        $status = PasswordBroker::reset(
            $validated,
            function ($user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            },
        );

        if ($status !== PasswordBroker::PASSWORD_RESET) {
            return back()->withErrors(['email' => __($status)])->onlyInput('email');
        }

        return redirect()->route('login')->with('success', __($status));
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
            'token' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
        ]);

        $result = $this->registerUser->execute(
            token: $validated['token'],
            name: $validated['name'],
            password: $validated['password'],
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        if (! $result->success) {
            return back()->withErrors(['token' => match ($result->error) {
                'invitation_not_found' => 'Invitation not found.',
                'invitation_already_used' => 'This invitation has already been used.',
                'invitation_expired' => 'This invitation has expired.',
                default => 'Registration failed.',
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
