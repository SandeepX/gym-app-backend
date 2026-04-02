<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = [
        'member_id',
        'checked_in_by',
        'check_in',
        'check_out',
        'notes',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }

    public function durationMinutes(): ?int
    {
        if ($this->check_out) {
            return $this->check_in->diffInMinutes($this->check_out);
        }

        return null;
    }

    protected function casts(): array
    {
        return [
            'check_in' => 'datetime',
            'check_out' => 'datetime',
        ];
    }

    public function scopeFilter($query, array $filters)
    {
        return $query
            ->when($filters['member_id'] ?? null, fn ($q) => $q->where('member_id', $filters['member_id']))
            ->when($filters['date'] ?? null, fn ($q) => $q->whereDate('check_in', $filters['date']))
            ->when($filters['from_date'] ?? null, fn ($q) => $q->whereDate('check_in', '>=', $filters['from_date']))
            ->when($filters['to_date'] ?? null, fn ($q) => $q->whereDate('check_in', '<=', $filters['to_date']));
    }
}
