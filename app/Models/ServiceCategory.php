<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name_en', 'name_ru', 'is_active', 'sort_order'])]
class ServiceCategory extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    public function serviceDetails(): HasMany
    {
        return $this->hasMany(ListingDetails\ServiceDetail::class);
    }

    /** Category name in the current (or given) locale. */
    public function name(?string $locale = null): string
    {
        return ($locale ?? app()->getLocale()) === 'ru' ? $this->name_ru : $this->name_en;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name_en');
    }
}
