<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(string $role): View
    {
        abort_unless(in_array($role, ['mitra', 'konsumen'], true), 404);

        return view('auth.login', compact('role'));
    }

    public function login(Request $request, string $role): RedirectResponse
    {
        abort_unless(in_array($role, ['mitra', 'konsumen'], true), 404);

        $credentials = $request->validate([
            'identity' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()
            ->where(function ($query) use ($credentials): void {
                $query->where('email', $credentials['identity'])
                    ->orWhere('username', $credentials['identity']);
            })
            ->where('role', $role)
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return back()->withErrors(['identity' => 'Email/username atau kata sandi tidak sesuai.'])
                ->onlyInput('identity');
        }

        if ($user->account_status !== 'active') {
            return back()->withErrors([
                'identity' => 'Akun ini masih menunggu aktivasi. Silakan hubungi pengelola Antarasa.',
            ])->onlyInput('identity');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended($user->must_change_password
            ? route('account.password.edit')
            : route('dashboard'));
    }

    public function showRegister(string $role): View
    {
        abort_unless(in_array($role, ['mitra', 'konsumen'], true), 404);

        return view('auth.register', compact('role'));
    }

    public function register(Request $request, string $role): RedirectResponse
    {
        abort_unless(in_array($role, ['mitra', 'konsumen'], true), 404);

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'username' => ['nullable', 'alpha_dash', 'max:100', 'unique:users,username'],
            'phone' => ['required', 'string', 'regex:/^[0-9+()\\-\\s]{8,30}$/', 'unique:users,phone'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ];

        if ($role === 'mitra') {
            $rules += [
                'store_name' => ['required', 'string', 'max:255'],
                'address' => ['required', 'string', 'max:2000'],
            ];
        }

        $data = $request->validate($rules);

        $user = DB::transaction(function () use ($data, $role): User {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'username' => $data['username'] ?: null,
                'phone' => $data['phone'],
                'password' => Hash::make($data['password']),
                'role' => $role,
                'account_status' => $role === 'mitra' ? 'pending' : 'active',
            ]);

            if ($role === 'mitra') {
                Store::create([
                    'user_id' => $user->id,
                    'store_name' => $data['store_name'],
                    'phone' => $data['phone'],
                    'address' => $data['address'],
                    'store_status' => 'pending',
                    'is_open' => false,
                ]);
            }

            return $user;
        });

        if ($role === 'mitra') {
            return redirect()->route('login', 'mitra')
                ->with('status', 'Pengajuan tersimpan. Akun dapat digunakan setelah aktivasi.');
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('status', 'Selamat datang di Antarasa.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
