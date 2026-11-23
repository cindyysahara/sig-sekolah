@extends('layouts.app')
@section('title', 'Ubah Kata Sandi')

@section('content')
<h1 class="text-2xl font-bold mb-4">Ubah Kata Sandi</h1>

<form method="POST" action="{{ route('kata-sandi.update') }}"
      class="bg-white rounded shadow p-6 max-w-md space-y-4">
    @csrf
    @method('PUT')

    @foreach ([
        'current_password' => 'Kata sandi saat ini',
        'password' => 'Kata sandi baru (minimal 8 karakter)',
        'password_confirmation' => 'Ulangi kata sandi baru',
    ] as $nama => $label)
        <div>
            <label for="{{ $nama }}" class="block font-medium">{{ $label }}</label>
            <input type="password" id="{{ $nama }}" name="{{ $nama }}"
                   autocomplete="{{ $nama === 'current_password' ? 'current-password' : 'new-password' }}"
                   class="w-full border rounded px-3 py-2">
            @error($nama)
                <p class="text-red-600 text-sm">{{ $message }}</p>
            @enderror
        </div>
    @endforeach

    <button class="px-5 py-2 bg-blue-700 text-white rounded">Simpan</button>
</form>
@endsection