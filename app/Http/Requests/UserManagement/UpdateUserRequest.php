<?php

namespace App\Http\Requests\UserManagement;

use App\Http\Requests\BaseRequest;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('users.edit') ?? false;
    }

    public function rules(): array
    {
        /** @var User $target */
        $target = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($target?->id),
            ],
            'unit_id' => [
                Rule::requiredIf(fn () => (bool) $this->user()?->hasRole('super_admin')),
                'nullable',
                Rule::exists('units', 'id')->where('is_active', true),
            ],
            'is_active' => ['nullable', 'boolean'],
            'password' => [
                'nullable',
                'confirmed',
                Password::min(12)->letters()->mixedCase()->numbers()->symbols(),
            ],
            'role' => ['nullable', Rule::exists('roles', 'name')],
        ];
    }
}
