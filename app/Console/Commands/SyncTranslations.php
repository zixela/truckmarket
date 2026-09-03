<?php

namespace App\Console\Commands;

use App\Services\Translation\TranslationSync;
use Illuminate\Console\Command;

class SyncTranslations extends Command
{
    protected $signature = 'translations:sync';

    protected $description = 'Import new lang/*.php keys into the translations table (admin-edited texts are kept)';

    public function handle(TranslationSync $sync): int
    {
        $result = $sync->run();

        $this->components->info("Translations synced: {$result['created']} added, {$result['updated']} updated.");

        return self::SUCCESS;
    }
}
