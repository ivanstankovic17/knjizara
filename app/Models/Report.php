<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Izveštaj koji se pravi u pozadini: pending, done ili failed.
 */
#[Fillable(['type', 'status', 'result'])]
class Report extends Model
{
    use HasUuids;

    protected $attributes = [
        'status' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'result' => 'array',
        ];
    }
}
