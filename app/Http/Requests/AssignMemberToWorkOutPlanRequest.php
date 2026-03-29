<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignMemberToWorkOutPlanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (empty($this->start_date)) {
            $this->merge([
                'start_date' => now()->toDateString(),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'member_id' => ['required', 'integer', 'exists:members,id'],
            'workout_plan_id' => ['required', 'integer', 'exists:workout_plans,id'],
            'start_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
