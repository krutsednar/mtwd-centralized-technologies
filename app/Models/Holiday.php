<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    use HasFactory;

    public const TYPE_SELECT = [
        'regular'             => 'Regular Holiday',
        'special_non_working' => 'Special Non-Working Holiday',
        'special_working'     => 'Special Working Holiday',
    ];

    protected $fillable = [
        'name',
        'date',
        'type',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    /** Returns date strings (Y-m-d) for holidays that are non-working (regular + special non-working). */
    public static function nonWorkingDates(): array
    {
        return static::whereIn('type', ['regular', 'special_non_working'])
            ->pluck('date')
            ->map(fn ($d) => \Carbon\Carbon::parse($d)->toDateString())
            ->toArray();
    }
}
