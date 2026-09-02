<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class RatingService
{
    private const TTL = 3600;

    /** @return array{average: float, count: int, positive: int, negative: int} */
    public function summary(User|int $user): array
    {
        $id = $user instanceof User ? $user->id : $user;

        return Cache::remember($this->key($id), self::TTL, function () use ($id) {
            $row = Review::query()
                ->visible()
                ->where('subject_id', $id)
                ->selectRaw('COUNT(*) as total, COALESCE(AVG(score), 0) as average, SUM(is_positive = 1) as positive, SUM(is_positive = 0) as negative')
                ->first();

            return [
                'average' => round((float) $row->average, 1),
                'count' => (int) $row->total,
                'positive' => (int) $row->positive,
                'negative' => (int) $row->negative,
            ];
        });
    }

    public function forget(User|int $user): void
    {
        Cache::forget($this->key($user instanceof User ? $user->id : $user));
    }

    private function key(int $id): string
    {
        return "user:{$id}:rating";
    }
}
