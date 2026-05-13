<?php

declare(strict_types=1);

namespace App\Modules\Core\Http\Requests;

use App\Modules\Tenant\Domain\Enums\Role;
use App\Modules\Tenant\Domain\Models\Membership;
use App\Modules\Tenant\Domain\TenantContext;
use Illuminate\Foundation\Http\FormRequest;

final class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if ($user === null) {
            return false;
        }

        $context = app(TenantContext::class);
        if (! $context->has()) {
            return false;
        }

        // Membership must be in the *current* tenant — not any tenant the user
        // happens to belong to. Without this, a Member in tenant B could
        // pass authorization for a write in tenant A.
        $membership = Membership::query()
            ->where('user_id', $user->id)
            ->where('tenant_id', $context->id())
            ->first();

        if ($membership === null) {
            return false;
        }

        return $membership->role->canWrite();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
