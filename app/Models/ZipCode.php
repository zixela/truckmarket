<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['zip', 'city', 'state', 'latitude', 'longitude'])]
class ZipCode extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $primaryKey = 'zip';

    protected $keyType = 'string';

    protected function casts(): array
    {
        return ['latitude' => 'float', 'longitude' => 'float'];
    }
}
