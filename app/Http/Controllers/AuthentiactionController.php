<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Models\User;

class AuthentiactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $title = "Login BBM Patuha";
        return view('authentication.login', compact('title'));
    }

    /**
     * Proses login user dengan validasi dinamis
     * Dilindungi dari XSS dan SQL Injection
     */
    public function authenticate(Request $request)
    {

        // dd($request->all());
        
        // Rate limiting untuk mencegah brute force attack
        $throttleKey = Str::lower($request->input('username')) . '|' . $request->ip();
        
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withErrors([
                'username' => "Terlalu banyak percobaan login. Silakan coba lagi dalam {$seconds} detik."
            ])->withInput($request->only('username'));
        }

        // Validasi input dengan Laravel Validator (mencegah XSS & SQL Injection)
        $validated = $request->validate([
            'username' => 'required|string|max:255',
            'password' => 'required|string|min:6|max:255'
        ], [
            'username.required' => 'Username harus diisi.',
            'password.required' => 'Password harus diisi.',
            'password.min' => 'Password minimal 6 karakter.'
        ]);

        // Sanitasi input (Laravel sudah melakukan escape otomatis di Eloquent)
        $username = strip_tags($validated['username']);
        $password = $validated['password'];

        // Cek apakah user dengan username tersebut ada di database
        // Menggunakan Eloquent ORM (aman dari SQL Injection)
        $user = User::where('username', $username)->first();

        // Jika user tidak ditemukan
        if (!$user) {
            RateLimiter::hit($throttleKey, 60); // Tambah hit counter
            
            return back()->withErrors([
                'username' => 'Username tidak terdaftar dalam sistem.'
            ])->withInput($request->only('username'));
        }

        // Jika user ditemukan, cek password
        // Hash::check aman dari timing attack
        if (!Hash::check($password, $user->password)) {
            RateLimiter::hit($throttleKey, 60); // Tambah hit counter
            
            return back()->withErrors([
                'password' => 'Password yang Anda masukkan salah.'
            ])->withInput($request->only('username'));
        }

        // Cek status aktif user (opsional)
        if (isset($user->is_active) && !$user->is_active) {
            return back()->withErrors([
                'username' => 'Akun Anda tidak aktif. Hubungi administrator.'
            ])->withInput($request->only('username'));
        }

        // Clear rate limiter jika login berhasil
        RateLimiter::clear($throttleKey);

        // Login user menggunakan Auth facade
        Auth::login($user, $request->filled('remember'));

        // Regenerate session untuk mencegah session fixation attack
        $request->session()->regenerate();

        // Redirect berdasarkan role (sesuaikan dengan sistem Anda)
        return $this->redirectBasedOnRole($user);
    }

    /**
     * Redirect user berdasarkan role
     */
    protected function redirectBasedOnRole($user)
    {
        // Sesuaikan dengan role di sistem Anda
        if ($user->role === 'requester') {
            return "piw dashboard requester";
        } elseif ($user->role === 'admin') {
            // return redirect()->intended('/dashboard')
            //     ->with('success', 'Selamat datang, ' . $user->name . '!');
            return "piw dashboard admin";
        } elseif ($user->role === 'superadmin') {
            // return redirect()->intended('/dashboard')
            //     ->with('success', 'Selamat datang, ' . $user->name . '!');
            return "piw dashboard superadmin";
        } elseif ($user->role === 'manager') {
            // return redirect()->intended('/dashboard')
            //     ->with('success', 'Selamat datang, ' . $user->name . '!');
            return "piw dashboard manager";
        } else {
            return redirect()->intended('/home')
                ->with('success', 'Selamat datang, ' . $user->name . '!');
        }
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        Auth::logout();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('login')
            ->with('success', 'Anda telah berhasil logout.');
    }
}


