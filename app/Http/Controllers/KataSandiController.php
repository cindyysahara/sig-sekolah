<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class KataSandiController extends Controller
{
    public function edit()
    {
        return view('kata-sandi');
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // Di-hash otomatis oleh cast 'hashed' pada model User
        $request->user()->update(['password' => $data['password']]);

        return back()->with('success', 'Kata sandi berhasil diubah.');
    }
}