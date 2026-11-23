@extends('layouts.app')
@section('title', 'Ubah Kata Sandi')

@section('content')
<h1 class="text-2xl font-bold mb-4">Ubah Kata Sandi</h1>

@php $cls = 'w-full border rounded px-3 py-2'; @endphp

<form method="POST" action="{{ route('akun.password.update') }}"
      class="bg-white rounded shadow p-6 max-w-md space-y-4">
    @csrf
    @method('PUT')

    <div>
        <label for="current_password" class="block font-medium">
            Kata Sandi Lama
        </label>
        <input type="password" id="current_password" name="current_password"
               class="{{ $cls }}" autocomplete="current-password">
        @error('current_password')
            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="password" class="block font-medium">
            Kata Sandi Baru
        </label>
        <input type="password" id="password" name="password"
               class="{{ $cls }}" autocomplete="new-password">
        @error('password')
            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="password_confirmation" class="block font-medium">
            Ulangi Kata Sandi Baru
        </label>
        <input type="password" id="password_confirmation"
               name="password_confirmation" class="{{ $cls }}"
               autocomplete="new-password">
    </div>

    <button class="px-5 py-2 bg-blue-700 text-white rounded">
        Simpan
    </button>
</form>
@endsection