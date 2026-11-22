<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/reset-password/{token}', function (Request $request, string $token) {
    $frontendUrl = rtrim((string) config('app.frontend_url', 'http://127.0.0.1:5173'), '/');
    $email = $request->query('email', '');

    return redirect()->to(
        $frontendUrl.'/reset-password?token='.urlencode($token).'&email='.urlencode((string) $email)
    );
})->name('password.reset');
