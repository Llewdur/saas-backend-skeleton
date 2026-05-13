<?php

declare(strict_types=1);

namespace App\Modules\Users\Http\Requests;

use App\Modules\Tenant\Domain\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateMemberRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'role' => ['required', 'string', Rule::enum(Role::class)],
        ];
    }
}
