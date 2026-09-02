<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['order_id', 'author_id', 'subject_id', 'score', 'is_positive', 'body', 'reply'])]
class Review extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'is_positive' => 'boolean',
            'is_hidden' => 'boolean',
            'replied_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subject_id');
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_hidden', false);
    }
}
