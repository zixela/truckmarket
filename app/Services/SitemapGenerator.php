<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ListingType;
use App\Http\Middleware\SetLocale;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Support\Carbon;
use XMLWriter;

/**
 * Writes sitemap.xml (index) + sitemaps/*.xml into the public directory:
 *   pages.xml       home + listing-type pages
 *   listings-N.xml  active listings (with cover image), LISTINGS_PER_FILE entries per file
 *   profiles.xml    public profiles of users that have an active listing
 * Every URL carries hreflang alternates for all locales plus x-default (fallback locale).
 * Files are written to a temp name and renamed, so a half-written sitemap is never served.
 */
class SitemapGenerator
{
    public const LISTINGS_PER_FILE = 10000; // protocol limit is 50 000 URLs / 50 MB per file

    private string $baseDir;

    private string $baseUrl;

    private ?XMLWriter $writer = null;

    private string $currentPath = '';

    private int $currentCount = 0;

    public function __construct(?string $baseDir = null)
    {
        $this->baseDir = rtrim($baseDir ?? public_path(), '/\\');
        $this->baseUrl = rtrim((string) config('app.url'), '/');
    }

    /** @return array{files: string[], urls: int} */
    public function generate(): array
    {
        $dir = $this->baseDir.'/sitemaps';

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $files = [];
        $urls = 0;

        // Static pages
        $this->open("{$dir}/pages.xml");
        $this->writeEntries($this->localized(fn (string $l) => route('home', ['locale' => $l]), $this->latestUpdate()));
        foreach (ListingType::cases() as $type) {
            $this->writeEntries($this->localized(
                fn (string $l) => route('listings.type', ['locale' => $l, 'typeSlug' => $type->slug()]),
                $this->latestUpdate($type),
            ));
        }
        $urls += $this->currentCount;
        $files[] = $this->close();

        // Active listings, split into files
        $chunk = 1;
        $this->open("{$dir}/listings-{$chunk}.xml");
        Listing::query()->active()->with('media')->orderBy('id')->chunkById(500, function ($listings) use (&$chunk, &$files, &$urls, $dir) {
            foreach ($listings as $listing) {
                if ($this->currentCount >= self::LISTINGS_PER_FILE) {
                    $urls += $this->currentCount;
                    $files[] = $this->close();
                    $this->open("{$dir}/listings-".(++$chunk).'.xml');
                }
                $this->writeEntries($this->localized(
                    fn (string $l) => $listing->seoUrl($l),
                    $listing->updated_at,
                    $listing->coverUrl(),
                ));
            }
        });
        $urls += $this->currentCount;
        $files[] = $this->close();

        // Public profiles
        $this->open("{$dir}/profiles.xml");
        User::query()
            ->whereHas('listings', fn ($query) => $query->active())
            ->orderBy('id')
            ->lazyById(500)
            ->each(fn (User $user) => $this->writeEntries($this->localized(
                fn (string $l) => route('profile.show', ['locale' => $l, 'user' => $user->id]),
                $user->updated_at,
            )));
        $urls += $this->currentCount;
        $files[] = $this->close();

        // Drop chunks left over from a previous, larger run
        foreach (glob("{$dir}/listings-*.xml") ?: [] as $old) {
            if (! in_array($old, $files, true)) {
                unlink($old);
            }
        }

        $this->writeIndex($files);

        return ['files' => $files, 'urls' => $urls];
    }

    /**
     * One entry per locale, each listing every locale as an alternate (Google requires them to be reciprocal).
     *
     * @return array<int, array{loc: string, lastmod: ?Carbon, alternates: array<string, string>, image: ?string}>
     */
    private function localized(callable $urlFor, ?Carbon $lastmod = null, ?string $image = null): array
    {
        $alternates = [];
        foreach (SetLocale::SUPPORTED as $locale) {
            $alternates[$locale] = $urlFor($locale);
        }

        return array_map(
            fn (string $loc) => ['loc' => $loc, 'lastmod' => $lastmod, 'alternates' => $alternates, 'image' => $image],
            array_values($alternates),
        );
    }

    private function latestUpdate(?ListingType $type = null): ?Carbon
    {
        $query = Listing::query()->active();
        if ($type) {
            $query->ofType($type);
        }
        $max = $query->max('updated_at');

        return $max ? Carbon::parse($max) : null;
    }

    private function open(string $path): void
    {
        $this->currentPath = $path;
        $this->currentCount = 0;
        $this->writer = new XMLWriter;
        $this->writer->openUri($path.'.tmp');
        $this->writer->startDocument('1.0', 'UTF-8');
        $this->writer->setIndent(true);
        $this->writer->startElement('urlset');
        $this->writer->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $this->writer->writeAttribute('xmlns:xhtml', 'http://www.w3.org/1999/xhtml');
        $this->writer->writeAttribute('xmlns:image', 'http://www.google.com/schemas/sitemap-image/1.1');
    }

    /** @param  array<int, array{loc: string, lastmod: ?Carbon, alternates: array<string, string>, image: ?string}>  $entries */
    private function writeEntries(array $entries): void
    {
        $fallback = (string) config('app.fallback_locale');

        foreach ($entries as $entry) {
            $this->writer->startElement('url');
            $this->writer->writeElement('loc', $entry['loc']);
            if ($entry['lastmod']) {
                $this->writer->writeElement('lastmod', $entry['lastmod']->toAtomString());
            }
            foreach ($entry['alternates'] as $locale => $href) {
                $this->writeAlternate($locale, $href);
            }
            if (isset($entry['alternates'][$fallback])) {
                $this->writeAlternate('x-default', $entry['alternates'][$fallback]);
            }
            if ($entry['image']) {
                $this->writer->startElement('image:image');
                $this->writer->writeElement('image:loc', $entry['image']);
                $this->writer->endElement();
            }
            $this->writer->endElement();
            $this->currentCount++;
        }
    }

    private function writeAlternate(string $hreflang, string $href): void
    {
        $this->writer->startElement('xhtml:link');
        $this->writer->writeAttribute('rel', 'alternate');
        $this->writer->writeAttribute('hreflang', $hreflang);
        $this->writer->writeAttribute('href', $href);
        $this->writer->endElement();
    }

    /** Finishes the current file and returns its path. */
    private function close(): string
    {
        $this->writer->endElement();
        $this->writer->endDocument();
        $this->writer->flush();
        $this->writer = null;
        rename($this->currentPath.'.tmp', $this->currentPath);

        return $this->currentPath;
    }

    /** @param  string[]  $files */
    private function writeIndex(array $files): void
    {
        $path = $this->baseDir.'/sitemap.xml';
        $writer = new XMLWriter;
        $writer->openUri($path.'.tmp');
        $writer->startDocument('1.0', 'UTF-8');
        $writer->setIndent(true);
        $writer->startElement('sitemapindex');
        $writer->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        foreach ($files as $file) {
            $writer->startElement('sitemap');
            $writer->writeElement('loc', $this->baseUrl.'/sitemaps/'.basename($file));
            $writer->writeElement('lastmod', Carbon::now()->toAtomString());
            $writer->endElement();
        }
        $writer->endElement();
        $writer->endDocument();
        $writer->flush();
        rename($path.'.tmp', $path);
    }
}
