<?php

use App\Enums\UserRole;
use App\Filament\Resources\Translations\Pages\ListTranslations;
use App\Models\Translation;
use App\Models\User;
use App\Services\Translation\DatabaseLoader;
use App\Services\Translation\TranslationSync;
use Database\Seeders\RoleSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Livewire\Livewire;

function translationRow(string $group, string $key): Translation
{
    return Translation::query()->where('group', $group)->where('key', $key)->firstOrFail();
}

function actingAsTranslationAdmin(): void
{
    test()->seed(RoleSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole(UserRole::Admin->value);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    test()->actingAs($admin);
}

it('serves translations through the database-backed loader', function () {
    expect(app('translator')->getLoader())->toBeInstanceOf(DatabaseLoader::class);
});

it('mirrors the language files into the translations table on migration', function () {
    $home = translationRow('common', 'home');

    expect($home->en)->toBe('Home')
        ->and($home->ru)->toBe('Главная')
        ->and($home->en_default)->toBe('Home')
        ->and(Translation::query()->where('group', 'listings')->where('key', 'types.load')->exists())->toBeTrue();
});

it('prefers admin-edited texts over the language files', function () {
    translationRow('common', 'home')->update(['en' => 'Start page', 'ru' => 'Стартовая']);

    expect(__('common.home'))->toBe('Start page');

    app()->setLocale('ru');
    expect(__('common.home'))->toBe('Стартовая')
        ->and(__('listings.types.load'))->toBe('Груз');
});

it('falls back to the language file when a text is emptied', function () {
    translationRow('common', 'home')->update(['ru' => null]);

    app()->setLocale('ru');
    expect(__('common.home'))->toBe('Главная');
});

it('keeps admin edits but follows file changes for untouched keys when syncing', function () {
    $home = translationRow('common', 'home');
    $home->update(['ru' => 'Стартовая']);
    translationRow('common', 'login')->delete();
    translationRow('common', 'search')->update(['en' => 'Old', 'en_default' => 'Old']);

    $result = app(TranslationSync::class)->run();

    expect($result)->toBe(['created' => 1, 'updated' => 1])
        ->and($home->refresh()->ru)->toBe('Стартовая')
        ->and(translationRow('common', 'login')->en)->toBe('Log in')
        ->and(translationRow('common', 'search')->en)->toBe('Search');
});

it('searches and filters translations in the panel', function () {
    actingAsTranslationAdmin();
    translationRow('common', 'home')->update(['ru' => null]);

    Livewire::test(ListTranslations::class)
        ->searchTable('verify_intro')
        ->assertCanSeeTableRecords([translationRow('auth', 'verify_intro')])
        ->assertCanNotSeeTableRecords([translationRow('common', 'home')])
        ->searchTable('')
        ->filterTable('group', 'common')
        ->filterTable('missing_ru')
        ->assertCountTableRecords(1)
        ->assertCanSeeTableRecords([translationRow('common', 'home')]);
});

it('lets an admin edit a translation from the panel and guards placeholders', function () {
    actingAsTranslationAdmin();

    $row = translationRow('auth', 'verify_intro');

    Livewire::test(ListTranslations::class)
        ->callAction(TestAction::make('edit')->table($row), ['ru' => 'Код отправлен. Введите его ниже.'])
        ->assertHasActionErrors(['ru']);

    Livewire::test(ListTranslations::class)
        ->callAction(TestAction::make('edit')->table($row), ['ru' => 'Мы отправили код на :email. Введите его ниже.'])
        ->assertHasNoActionErrors();

    app()->setLocale('ru');
    expect(__('auth.verify_intro', ['email' => 'a@b.c']))->toBe('Мы отправили код на a@b.c. Введите его ниже.');
});
