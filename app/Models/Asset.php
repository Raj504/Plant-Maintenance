<?php

namespace App\Models;

use App\Models\Concerns\HasAttachments;
use App\Models\Concerns\HasHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use SoftDeletes, HasAttachments, HasHistory;

    protected $table = 'pm_assets';
    protected $guarded = ['id'];
    protected $casts = [
        'installation_date'     => 'date',
        'commissioning_date'    => 'date',
        'purchase_date'         => 'date',
        'warranty_expiry_date'  => 'date',
        'current_running_hours' => 'decimal:2',
    ];

    public const STATUSES = [
        'active'             => 'Active',
        'under_maintenance'  => 'Under Maintenance',
        'breakdown'          => 'Breakdown',
        'idle'               => 'Idle',
        'under_installation' => 'Under Installation',
        'decommissioned'     => 'Decommissioned',
    ];

    public const CRITICALITIES = [
        'critical' => 'Critical',
        'high'     => 'High',
        'medium'   => 'Medium',
        'low'      => 'Low',
    ];

    // ---------------------------------------------------------------- Relations

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Asset::class, 'parent_id')->orderBy('name');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function plant(): BelongsTo
    {
        return $this->belongsTo(Plant::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function readings(): HasMany
    {
        return $this->hasMany(AssetReading::class)->latest('reading_date')->latest('id');
    }

    public function capacityLogs(): HasMany
    {
        return $this->hasMany(CapacityLog::class)->latest('log_date');
    }

    public function requests(): HasMany
    {
        return $this->hasMany(MaintenanceRequest::class)->latest();
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class)->latest();
    }

    public function pmPlans(): HasMany
    {
        return $this->hasMany(PmPlan::class);
    }

    public function bomItems(): HasMany
    {
        return $this->hasMany(AssetBom::class);
    }

    public function compliances(): HasMany
    {
        return $this->hasMany(Compliance::class);
    }

    public function amcContracts(): BelongsToMany
    {
        return $this->belongsToMany(AmcContract::class, 'pm_amc_assets', 'asset_id', 'amc_id');
    }

    // ---------------------------------------------------------------- Helpers

    /** "AST-001 - Mixing Machine" used in dropdowns. */
    public function getLabelAttribute(): string
    {
        return $this->asset_code . ' - ' . $this->name;
    }

    /** Full path e.g. Factory > Production > Line 1 > Mixer */
    public function getPathAttribute(): string
    {
        $names = [$this->name];
        $node = $this->parent;
        $guard = 0;
        while ($node && $guard < 10) {
            array_unshift($names, $node->name);
            $node = $node->parent;
            $guard++;
        }
        return implode(' > ', $names);
    }

    /** Rated capacity per hour, used by the capacity report. */
    public function ratedPerHour(): ?float
    {
        if (!$this->rated_capacity || !$this->capacity_hours) {
            return null;
        }
        return round($this->rated_capacity / $this->capacity_hours, 4);
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? asset('storage/' . $this->image) : null;
    }
}
