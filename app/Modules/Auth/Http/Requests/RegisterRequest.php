<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * NOTE on email enumeration: `unique:users,email` produces a 422 with a
 * field-specific error if the email is already registered. That leaks
 * account existence to anyone who can hit this endpoint. The throttle:auth
 * limiter (5/min/IP) curbs the rate at which an attacker can enumerate, but
 * the *cleanest* fix is async creation:
 *
 *   - drop the unique rule from validation
 *   - in the use case, attempt insert; on unique-constraint violation, no-op
 *     and dispatch a "someone tried to register your address" notification
 *     to the existing account instead
 *   - the endpoint always returns 202 Accepted "If your address is new,
 *     you'll receive a confirmation"
 *
 * That's beyond the scope of this skeleton (needs a queued mailer + a
 * verify-email flow). README #7 calls this out explicitly.
 */
final class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'tenant_name' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'Registration is not available with that address.',
        ];
    }
}
