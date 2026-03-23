<?php

namespace App\Traits;

trait GenerateSequenceNumberTrait
{
    /**
     * Generate a unique sequence number with optional year.
     *
     * @param  string  $prefix  e.g. 'GYM', 'INV', 'SUB'
     * @param  string  $column  column to check uniqueness against
     * @param  int  $padLength  zero padding length
     * @param  string  $separator  separator between parts
     * @param  bool  $withYear  include current year (2026 → 26)
     * @param  bool  $fullYear  use full year (2026) instead of short (26)
     */
    public static function generateSequenceNumber(
        string $prefix,
        string $column = 'membership_number',
        int $padLength = 6,
        string $separator = '-',
        bool $withYear = true,
        bool $fullYear = false,
    ): string {
        $year = $withYear ? ($fullYear ? now()->format('Y') : now()->format('y')) : null;

        $fullPrefix = $year
            ? $prefix.$separator.$year.$separator
            : $prefix.$separator;

        $latest = static::withTrashed()->where($column, 'like', $fullPrefix.'%')
            ->orderByDesc($column)
            ->value($column);

        if ($latest) {
            $lastNumber = (int) str_replace($fullPrefix, '', $latest);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        do {
            $generated = $fullPrefix.str_pad($nextNumber, $padLength, '0', STR_PAD_LEFT);
            $exists = static::withTrashed()->where($column, $generated)->exists();
            if ($exists) {
                $nextNumber++;
            }
        } while ($exists);

        return $generated;
    }
}
