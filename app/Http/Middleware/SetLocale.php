<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public const SUPPORTED = ['en', 'ru'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->route('locale');

        if (! in_array($locale, self::SUPPORTED, true)) {
            $locale = config('app.locale');
        }

        App::setLocale($locale);
        URL::defaults(['locale' => $locale]);
        $request->session()->put('locale', $locale);

        // Keep {locale} out of controller signatures — it is resolved here.
        $request->route()->forgetParameter('locale');

        return $next($request);
    }
}
