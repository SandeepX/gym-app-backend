<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

class AttendanceCheckoutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(Request $request): array
    {
        return [
            'check_in' => [
                'required',
                'date',
                'before:check_out',
            ],
            'check_out' => [
                'required',
                'date',
                'after:check_in',
                function ($attribute, $value, $fail) use ($request) {
                    $checkIn = Carbon::parse($request->check_in);
                    $checkOut = Carbon::parse($value);

                    if (! $checkIn->isSameDay($checkOut)) {
                        $fail('Check-out must be on the same day as check-in.');
                    }
                },
            ],
            'notes' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'check_in.required' => 'Check-in time is required.',
            'check_in.date' => 'Check-in must be a valid datetime.',
            'check_in.before' => 'Check-in must be before check-out.',
            'check_out.required' => 'Check-out time is required.',
            'check_out.date' => 'Check-out must be a valid datetime.',
            'check_out.after' => 'Check-out must be after check-in.',
            'check_out.before_or_equal' => 'Check-out time cannot be in the future.',
        ];
    }
}
