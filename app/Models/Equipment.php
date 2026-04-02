<?php

namespace App\Models;

use App\Traits\GenerateSequenceNumberTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Equipment extends Model
{
    use GenerateSequenceNumberTrait, SoftDeletes;

    protected $fillable = [
        'name',
        'serial_number',
        'category',
        'brand',
        'description',
        'status',
        'purchase_date',
        'purchase_price',
        'last_maintenance_date',
        'next_maintenance_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'last_maintenance_date' => 'date',
            'next_maintenance_date' => 'date',
            'purchase_price' => 'decimal:2',
        ];
    }

    public function maintenanceLogs(): HasMany
    {
        return $this->hasMany(EquipmentMaintenanceLog::class)->latest('performed_at');
    }

    public function isDueForMaintenance(): bool
    {
        return $this->next_maintenance_date && $this->next_maintenance_date <= now();
    }

    public function scopeFilter($query, array $filters)
    {
        return $query
            ->when($filters['status'] ?? null, fn ($q) => $q->where('status', $filters['status']))
            ->when($filters['category'] ?? null, fn ($q) => $q->where('category', $filters['category']))
            ->when($filters['search'] ?? null, fn ($q) => $q->where('name', 'like', "%{$filters['search']}%"));
    }
}
