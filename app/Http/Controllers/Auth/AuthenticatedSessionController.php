<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login', [
            'demoAccounts' => $this->demoAccounts(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Alamat surel wajib diisi.',
            'email.email' => 'Format alamat surel belum benar.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'Alamat surel atau kata sandi belum sesuai.',
            ]);
        }

        $request->session()->regenerate();

        /** @var User $user */
        $user = $request->user();

        if ($user->status->value !== 'active') {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'Akun ini sedang tidak aktif. Silakan hubungi petugas Posyandu.',
            ]);
        }

        return redirect()->intended(route($user->homeRoute()));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Anda telah keluar dengan aman.');
    }

    /**
     * @return array<int, array{name: string, email: string, role: string, description: string}>
     */
    private function demoAccounts(): array
    {
        return [
            ['name' => 'Kader Melati 01', 'email' => 'kader@nutrigrow.id', 'role' => 'Kader Posyandu', 'description' => 'Mencatat penimbangan dan mendampingi keluarga'],
            ['name' => 'Wali Balita Afiqah', 'email' => 'ortu@nutrigrow.id', 'role' => 'Orang Tua / Wali', 'description' => 'Melihat ringkasan pertumbuhan anak'],
            ['name' => 'dr. Rian Pratama', 'email' => 'nakes@nutrigrow.id', 'role' => 'Tenaga Kesehatan', 'description' => 'Meninjau tanda risiko dan memberi arahan'],
            ['name' => 'Petugas Gizi', 'email' => 'admin@nutrigrow.id', 'role' => 'Administrator Puskesmas', 'description' => 'Memantau cakupan wilayah'],
        ];
    }
}
