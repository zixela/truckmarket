<?php

namespace App\Console\Commands;

use App\Services\SitemapGenerator;
use Illuminate\Console\Command;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate';

    protected $description = 'Write public/sitemap.xml + public/sitemaps/*.xml (pages, active listings, profiles)';

    public function handle(SitemapGenerator $generator): int
    {
        $result = $generator->generate();

        $this->components->info("Sitemap written: {$result['urls']} URLs in ".count($result['files']).' files.');

        return self::SUCCESS;
    }
}
