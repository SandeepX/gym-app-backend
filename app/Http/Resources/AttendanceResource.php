<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'member' => $this->whenLoaded('member', fn () => [
                'id' => $this->member->id,
                'name' => $this->member->user->name,
                'membership_number' => $this->member->membership_number,
            ]),
            'checked_in_by' => $this->whenLoaded('checkedInBy', fn () => $this->checkedInBy?->name),
            'check_in' => $this->check_in?->toDateTimeString(),
            'check_out' => $this->check_out?->toDateTimeString(),
            'duration_minutes' => $this->durationMinutes(),
            'notes' => $this->notes,
        ];
    }
}
