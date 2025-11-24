<?php

namespace App\Http\Requests;

use App\Enums\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Add your authorization logic here
        // For example: return $this->user()->can('update-users');
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->route('user'); // Get user ID from route parameter

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'email',
                Rule::unique('users', 'email')->ignore($userId, 'ulid')
            ],
            'password' => ['sometimes', 'nullable', 'string', 'min:8'],
            'status' => ['sometimes', Rule::enum(UserStatus::class)],
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['exists:roles,name'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.max' => 'The user name cannot exceed 255 characters.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email address is already taken by another user.',
            'password.min' => 'The password must be at least 8 characters.',
            'status.enum' => 'The selected status is invalid.',
            'roles.array' => 'Roles must be provided as an array.',
            'roles.*.exists' => 'One or more selected roles do not exist.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'user name',
            'email' => 'email address',
            'roles.*' => 'role',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Remove password from request if it's empty
        if ($this->has('password') && empty($this->password)) {
            $this->request->remove('password');
        }
    }
}
