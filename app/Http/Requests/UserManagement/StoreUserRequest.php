<?php

namespace App\Http\Requests\UserManagement;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('users.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'unit_id' => [
                Rule::requiredIf(fn () => (bool) $this->user()?->hasRole('super_admin')),
                'nullable',
                Rule::exists('units', 'id')->where('is_active', true),
            ],
            'is_active' => ['nullable', 'boolean'],
            'password' => [
                'required',
                'confirmed',
                Password::defaults(),
            ],
            'role' => ['nullable', Rule::exists('roles', 'name')],
        ];
    }
}
