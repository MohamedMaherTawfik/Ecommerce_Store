<?php

use App\Http\Controllers\api\auth\GoogleAuthController;
use App\Http\Controllers\SeoController;
use Illuminate\Support\Facades\Route;

Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');
Route::get('/api/v1/users/google-login', [GoogleAuthController::class, 'googleLogin'])
    ->name('google.login');
Route::get('/api/v1/users/google-callback', [GoogleAuthController::class, 'googleCallback'])
    ->name('google.callback');
Route::get('/', [SeoController::class, 'app']);
Route::get('/{any}', [SeoController::class, 'app'])->where('any', '^(?!api|sitemap\.xml|robots\.txt).*$');
