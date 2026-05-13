<?php

declare(strict_types=1);

namespace App\Modules\Core\Http\Requests;

use App\Modules\Tenant\Domain\Enums\Role;
use App\Modules\Tenant\Domain\Models\Membership;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if ($user === null) {
            return false;
        }

        $membership = Membership::query()->where('user_id', $user->id)->first();
        if ($membership === null) {
            return false;
        }

        return in_array($membership->role, [Role::Owner, Role::Admin, Role::Member], strict: true);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
