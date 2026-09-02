<?php

use App\Http\Controllers\Account;
use App\Http\Controllers\Auth;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ListingController;
use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\ProfileController;
use App\Http\Middleware\SetLocale;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('home', [
    'locale' => session('locale', config('app.locale')),
]));

Route::prefix('{locale}')
    ->whereIn('locale', SetLocale::SUPPORTED)
    ->middleware(SetLocale::class)
    ->group(function () {
        Route::get('/', HomeController::class)->name('home');
        Route::get('/users/{user}', [ProfileController::class, 'show'])->name('profile.show');

        // Legacy URLs → permanent redirects to SEO URLs
        Route::get('/marketplace', [MarketplaceController::class, 'legacy'])->name('marketplace');
        Route::get('/listings/{listing}', [ListingController::class, 'legacy'])->name('listings.legacy');

        // SEO listing pages: /en/trucks, /en/trucks/freightliner-cascadia-15
        Route::get('/{typeSlug}', [MarketplaceController::class, 'type'])
            ->whereIn('typeSlug', \App\Enums\ListingType::slugs())
            ->name('listings.type');
        Route::get('/{typeSlug}/{slugId}', [ListingController::class, 'show'])
            ->whereIn('typeSlug', \App\Enums\ListingType::slugs())
            ->where('slugId', '[a-z0-9\-]+')
            ->name('listings.show');

        // Guest auth
        Route::middleware('guest')->group(function () {
            Route::get('/login', [Auth\AuthenticatedSessionController::class, 'create'])->name('login');
            Route::post('/login', [Auth\AuthenticatedSessionController::class, 'store']);
            Route::get('/register', [Auth\RegisteredUserController::class, 'create'])->name('register');
            Route::post('/register', [Auth\RegisteredUserController::class, 'store']);

            Route::get('/forgot-password', [Auth\PasswordResetController::class, 'requestForm'])->name('password.request');
            Route::post('/forgot-password', [Auth\PasswordResetController::class, 'sendLink'])->name('password.email');
            Route::get('/reset-password/{token}', [Auth\PasswordResetController::class, 'resetForm'])->name('password.reset');
            Route::post('/reset-password', [Auth\PasswordResetController::class, 'reset'])->name('password.update');

            Route::get('/auth/google', [Auth\GoogleAuthController::class, 'redirect'])->name('auth.google');
        });

        Route::get('/auth/google/callback', [Auth\GoogleAuthController::class, 'callback'])->name('auth.google.callback');

        // Authenticated
        Route::middleware('auth')->group(function () {
            Route::post('/logout', [Auth\AuthenticatedSessionController::class, 'destroy'])->name('logout');

            Route::get('/verify-email', [Auth\VerificationCodeController::class, 'notice'])->name('verification.notice');
            Route::post('/verify-email', [Auth\VerificationCodeController::class, 'verify'])->name('verification.verify');
            Route::post('/verify-email/resend', [Auth\VerificationCodeController::class, 'resend'])->name('verification.resend');

            Route::get('/verify-phone', [Auth\PhoneVerificationController::class, 'notice'])->name('verification.phone.notice');
            Route::post('/verify-phone', [Auth\PhoneVerificationController::class, 'verify'])->name('verification.phone.verify');
            Route::post('/verify-phone/resend', [Auth\PhoneVerificationController::class, 'resend'])->name('verification.phone.resend');

            Route::get('/choose-role', [Auth\GoogleAuthController::class, 'createRole'])->name('auth.role.create');
            Route::post('/choose-role', [Auth\GoogleAuthController::class, 'storeRole'])->name('auth.role.store');

            Route::prefix('account')
                ->name('account.')
                ->middleware('verified.code')
                ->group(function () {
                    Route::get('/', fn () => redirect()->route('account.listings.index'))->name('index');

                    Route::get('/listings', [Account\ListingController::class, 'index'])->name('listings.index');
                    Route::get('/listings/create', [Account\ListingController::class, 'create'])->name('listings.create');
                    Route::post('/listings', [Account\ListingController::class, 'store'])->name('listings.store');
                    Route::get('/listings/{listing}/edit', [Account\ListingController::class, 'edit'])->name('listings.edit');
                    Route::put('/listings/{listing}', [Account\ListingController::class, 'update'])->name('listings.update');
                    Route::delete('/listings/{listing}', [Account\ListingController::class, 'destroy'])->name('listings.destroy');

                    Route::get('/orders', [Account\OrderController::class, 'index'])->name('orders.index');
                    Route::post('/listings/{listing}/orders', [Account\OrderController::class, 'store'])->name('orders.store');
                    Route::post('/orders/{order}/confirm', [Account\OrderController::class, 'confirm'])->name('orders.confirm');
                    Route::post('/orders/{order}/decline', [Account\OrderController::class, 'decline'])->name('orders.decline');
                    Route::post('/orders/{order}/complete', [Account\OrderController::class, 'complete'])->name('orders.complete');
                    Route::post('/orders/{order}/cancel', [Account\OrderController::class, 'cancel'])->name('orders.cancel');

                    Route::get('/reviews', [Account\ReviewController::class, 'index'])->name('reviews.index');
                    Route::get('/orders/{order}/review', [Account\ReviewController::class, 'create'])->name('reviews.create');
                    Route::post('/orders/{order}/review', [Account\ReviewController::class, 'store'])->name('reviews.store');
                    Route::post('/reviews/{review}/reply', [Account\ReviewController::class, 'reply'])->name('reviews.reply');

                    Route::get('/blacklist', [Account\BlacklistController::class, 'index'])->name('blacklist.index');
                    Route::post('/blacklist', [Account\BlacklistController::class, 'store'])->name('blacklist.store');
                    Route::delete('/blacklist/{blacklist}', [Account\BlacklistController::class, 'destroy'])->name('blacklist.destroy');

                    Route::get('/settings', [Account\SettingsController::class, 'edit'])->name('settings.edit');
                    Route::put('/settings', [Account\SettingsController::class, 'update'])->name('settings.update');
                    Route::post('/settings/avatar', [Account\SettingsController::class, 'updateAvatar'])->name('settings.avatar');
                });
        });
    });
