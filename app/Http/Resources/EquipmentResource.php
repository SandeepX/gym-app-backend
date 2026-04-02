<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EquipmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'serial_number' => $this->serial_number,
            'name' => $this->name,
            'category' => $this->category,
            'brand' => $this->brand,
            'description' => $this->description,
            'purchase_date' => optional($this->purchase_date)?->toDateTimeString(),
            'purchase_price' => (float) $this->purchase_price,
            'next_maintenance_date' => optional($this->next_maintenance_date)?->toDateTimeString(),
            'notes' => $this->notes,
            'maintenanceLog' => $this->maintenanceLogs,
        ];
    }
}
