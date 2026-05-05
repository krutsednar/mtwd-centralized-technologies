<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveInclusiveDates extends Model
{
    public const STATUS_SELECT = [
        'pending'   => 'Pending',
        'used'      => 'Used',
        'cancelled' => 'Cancelled',
        'recalled'  => 'Recalled',
    ];

    protected $fillable = [
        'leave_application_id',
        'date',
        'duration',
        'status',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'date'     => 'date',
            'duration' => 'decimal:1',
        ];
    }

    public function leaveApplication(): BelongsTo
    {
        return $this->belongsTo(LeaveApplication::class);
    }
}
