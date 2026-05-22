<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;

class Division extends Model
{
    use HasRecursiveRelationships;
    use LogsActivity;
    use SoftDeletes;

    // ── Types ────────────────────────────────────────────────────────────────

    public const TYPE_OGM = 'ogm';

    public const TYPE_OAGM = 'oagm';

    public const TYPE_ODM = 'odm';

    public const TYPE_DIVISION = 'division';

    public const TYPE_LABELS = [
        self::TYPE_OGM => 'Office of the General Manager',
        self::TYPE_OAGM => 'Office of the AGM',
        self::TYPE_ODM => 'Office of the Department Manager',
        self::TYPE_DIVISION => 'Division',
    ];

    /**
     * Types that a given type may be placed under (parent type constraints).
     * Empty array = must be root (no parent allowed).
     */
    public const ALLOWED_PARENT_TYPES = [
        self::TYPE_OGM => [],
        self::TYPE_OAGM => [self::TYPE_OGM],
        self::TYPE_ODM => [self::TYPE_OGM, self::TYPE_OAGM],
        self::TYPE_DIVISION => [self::TYPE_OGM, self::TYPE_OAGM, self::TYPE_ODM],
    ];

    // ── Eloquent config ──────────────────────────────────────────────────────

    protected $fillable = [
        'parent_id',
        'name',
        'abbreviation',
        'type',
        'sort_order',
        'head_profile_id',
        'oic_active',
        'oic_profile_id',
    ];

    protected function casts(): array
    {
        return [
            'oic_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────────────

    /** The permanently assigned head of this org unit. */
    public function head(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'head_profile_id');
    }

    /** The Officer-In-Charge (active only when oic_active = true). */
    public function oic(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'oic_profile_id');
    }

    /** Profiles assigned to this org unit. */
    public function profiles(): HasMany
    {
        return $this->hasMany(Profile::class, 'division_id');
    }

    // ── Signatory helpers ────────────────────────────────────────────────────

    /**
     * Returns the active signatory for this org unit.
     * When oic_active is true and an OIC is assigned, the OIC signs.
     * Otherwise the head signs.
     */
    public function getActiveSignatory(): ?Profile
    {
        if ($this->oic_active && $this->oic_profile_id !== null) {
            return $this->oic;
        }

        return $this->head;
    }

    /**
     * Returns the ordered signature chain from this unit up to the root.
     * Each entry is the active signatory (OIC or head) of that level.
     * Nulls are filtered — units with no signatory are skipped.
     *
     * Usage: $profile->division->signatureChain()
     *
     * @return Collection<int, Profile>
     */
    public function signatureChain(): Collection
    {
        return $this->ancestorsAndSelf()
            ->with(['head', 'oic'])
            ->orderByDepth('desc')   // deepest (this unit) first, root last
            ->get()
            ->map(fn (self $unit): ?Profile => $unit->getActiveSignatory())
            ->filter()
            ->values();
    }

    // ── Drag-and-drop validation ─────────────────────────────────────────────

    /**
     * Whether this node can be placed under the given parent type.
     */
    public function canBeChildOf(?string $parentType): bool
    {
        $allowed = self::ALLOWED_PARENT_TYPES[$this->type] ?? [];

        if ($parentType === null) {
            return empty($allowed);   // root placement allowed only for OGM
        }

        return in_array($parentType, $allowed, true);
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    /** Root nodes only (no parent). */
    public function scopeRoots(\Illuminate\Database\Eloquent\Builder $query): void
    {
        $query->whereNull('parent_id')->orderBy('sort_order');
    }

    // ── Activity log ─────────────────────────────────────────────────────────

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }
}
