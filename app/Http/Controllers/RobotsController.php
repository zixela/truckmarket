<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Response;

/** /robots.txt — admin → Settings → block_robots switches between "hide everything" and the normal rules. */
class RobotsController extends Controller
{
    public function __invoke(): Response
    {
        $lines = Setting::bool('block_robots')
            ? ['User-agent: *', 'Disallow: /']
            : [
                'User-agent: *',
                'Disallow: /admin',
                'Disallow: /*/account',
                'Disallow: /*/login',
                'Disallow: /*/register',
                'Disallow: /*/forgot-password',
                'Disallow: /*/reset-password',
                'Disallow: /*/verify-email',
                'Disallow: /*/verify-phone',
                'Disallow: /*/choose-role',
                'Disallow: /*/auth/',
                'Disallow: /livewire/',
                '',
                'Sitemap: '.rtrim((string) config('app.url'), '/').'/sitemap.xml',
            ];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
