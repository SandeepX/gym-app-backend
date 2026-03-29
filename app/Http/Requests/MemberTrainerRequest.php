<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class MemberTrainerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'trainer_id' => ['required', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'trainer_id.required' => 'Trainer ID is required.',
            'trainer_id.exists' => 'Trainer not found.',
        ];
    }

    public function after(): array
    {
        return [
            function ($validator) {
                $user = User::find($this->trainer_id);

                if (! $user || ! $user->hasRole('trainer')) {
                    $validator->errors()->add(
                        'trainer_id',
                        'The selected user is not a trainer.'
                    );
                }
            },
        ];
    }
}
