# Coding Standards

> Opinionated conventions for this repo. Every rule has a *Why*. If the Why doesn't apply, the rule doesn't either.
> Reviewers should be able to read this once and predict what every file in the codebase looks like.

---

## 0. Meta-rules

1. **Boring beats clever.** If two solutions work, pick the one a junior engineer can read.
2. **Consistency beats correctness in style debates.** Match what's already in the file.
3. **No pattern without a problem.** Don't introduce a Repository, a DTO, or an interface until something concrete *demands* it.
4. **Delete > deprecate.** This is a fresh repo. There is no legacy. Don't write compatibility shims.
5. **Don't write defensive code for states the type system or DB schema already prevents.** A `NOT NULL` column doesn't need a null check at the call site. An `instanceof User` doesn't need an `else` for a model type the auth flow can't return. Dead branches absorb real bugs that should fail loudly.
6. **Don't add code before it's needed.** No config values, methods, services "for later." If it's not used in the current PR, leave it out.
7. `declare(strict_types=1);` at the top of *every* PHP file. Non-negotiable.

---

## 1. Naming

| Thing | Convention | Example |
|---|---|---|
| Classes | `PascalCase`, noun | `TenantResolver` |
| Interfaces | `PascalCase`, no `I` prefix | `WebhookDispatcher` (impl: `HttpWebhookDispatcher`) |
| Eloquent repository implementations | prefixed `Eloquent` | `EloquentTenantRepository` (impl of `TenantRepository`) |
| Methods | `camelCase`, verb | `resolveFromRequest()` |
| Use cases | `PascalCase`, verb-phrase | `RegisterTenant`, `InviteMember` |
| Events (past tense) | `<Subject><Verb>ed` | `TenantCreated`, `MembershipAccepted` |
| Jobs | `<Verb><Subject>` | `DispatchOutboundWebhook` |
| DB tables | `snake_case`, plural | `tenant_memberships` |
| DB columns | `snake_case` | `accepted_at` |
| Foreign keys | `<table_singular>_id` | `tenant_id`, `user_id` |
| Boolean columns | `is_*` or `has_*` or `*_at` (nullable timestamp preferred) | `accepted_at`, not `is_accepted` |
| Enum cases | `PascalCase` | `Role::Owner` |
| Config keys | `snake_case` | `services.stripe.webhook_secret` |
| Env keys | `SCREAMING_SNAKE_CASE` | `STRIPE_WEBHOOK_SECRET` |

**Forbidden suffixes:** `Manager`, `Helper`, `Util`, `Processor`. They mean "I couldn't think of a name." Pick something specific.

**Forbidden prefixes:** `I` on interfaces, `Abstract` on abstract classes. The keyword is right there.

**Route names:** module name is auto-prepended by the `ModuleServiceProvider`. Define the route as `show`, not `tenant.show` — the registered name will be `tenant.show`. Otherwise you end up with `tenant.tenant.show`. Verify with `php artisan route:list`.

---

## 2. File header

Every PHP file:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Tenant\Domain;
```

No file-level docblocks. No `@author`. Git knows.

**Always use `use` imports** — never inline fully-qualified names (e.g., `\App\Modules\Clients\Models\Client::findOrFail()`). Always add a `use` statement at the top.

**Use `use function` imports for PHP built-ins** — `use function in_array;`, `use function is_string;`. Place after class imports, separated by a blank line. Mago flags unqualified calls as ambiguous.

---

## 3. Types

- **Return types are mandatory.** Including `void` and `never`.
- **Parameter types are mandatory.**
- **Property types are mandatory.** Use `readonly` wherever the property is set in the constructor and never reassigned.
- **No `mixed`.** If you reach for `mixed`, you owe the reader a comment explaining why. Usually a DTO solves it.
- **Nullability is intentional.** `?string` means "the absence of a value is a valid domain state," not "I haven't decided yet."

**PHP 8.4 features to lean on:**
- `readonly class` for DTOs and value objects — enforces immutability at the class level.
- `public private(set)` asymmetric visibility for DTO/VO properties that should be publicly readable but only set internally.
- `catch (SomeException)` without a variable when the variable is unused.

**Why readonly everywhere it fits:** mutation is the source of most bugs. Domain objects, DTOs, value objects → all `readonly`. Eloquent models are the exception (framework constraint).

---

## 4. Enums

**Rule:** any field with a fixed set of values is a backed enum. No string constants, no class constants holding strings.

```php
<?php

declare(strict_types=1);

namespace App\Modules\Tenant\Domain\Enums;

enum Role: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Member = 'member';
    case Viewer = 'viewer';

    public function canManageBilling(): bool
    {
        return match ($this) {
            self::Owner, self::Admin => true,
            self::Member, self::Viewer => false,
        };
    }
}
```

**Conventions:**
- Always **backed** (`: string` or `: int`). Pure enums break serialization.
- **String-backed** for anything user-visible, persisted, or sent over the wire.
- **Int-backed** only for ordered states (priority, severity) where comparison matters.
- Behavior belongs *on* the enum — `match` expressions over a method body, not scattered `if` chains around the codebase.
- Cast on Eloquent models: `'role' => Role::class`.
- **No DB `enum` columns.** Use a `string` column with a PHP enum cast. DB enums are painful to change.
- Never reference the PHP enum class inside a migration (migrations must run without depending on app autoload state).

**Naming:** name cases for *what they represent*, not *the process that produced them*. `FlowAccount::Customer` (what the login method is), not `Registration::ViaWeb` (how the account was created). Especially important when the case appears in a setting or UI label.

**Reading multi-select enum settings:** use `->rawValue` (array of strings), not `->value` (display-joined string). Using `->value` in a membership check silently passes on single selections (substring match) and silently fails on multi-selections.

**When NOT to use an enum:**
- Values come from user input with an open set (tags, labels).
- Values come from a database table (use a model + FK).

---

## 5. DTOs (Data Transfer Objects)

**Rule:** every use case takes a DTO input and returns a DTO (or a domain model) output. Never an array. Never `Request`.

```php
<?php

declare(strict_types=1);

namespace App\Modules\Auth\Application\DTOs;

final readonly class RegisterUserInput
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public string $tenantName,
        public Role $role,
    ) {}

    public static function fromRequest(RegisterRequest $request): self
    {
        return new self(
            name: $request->string('name')->toString(),
            email: $request->string('email')->toString(),
            password: $request->string('password')->toString(),
            tenantName: $request->string('tenant_name')->toString(),
            role: Role::from($request->string('role')->toString()),
        );
    }
}
```

**Conventions:**
- `final readonly class` always.
- Public properties, no getters. (`readonly` makes them safe; getters would be ceremony.)
- Named constructor `fromRequest()` / `fromArray()` for boundary translation.
- Use cases never see `Request` objects. Mapping happens in the controller or the FormRequest.
- DTOs live in `<Module>/Application/DTOs/`.
- Output DTOs (`<UseCase>Result`) only when the return shape is non-trivial or shared.

**Typed enums, not scalars.** DTO properties holding enum values take the enum type (`Role $role`), not the backing scalar (`string $role`). Convert at the boundary (constructor / static factory). Serialise with `->value` *only* inside `toArray()`. This eliminates "is this the case or the value?" bugs.

**Storage-agnostic naming.** Serialise via `toArray()`/`fromArray()`, never `toSession()`/`fromCache()`. The DTO shouldn't know where it's being stored.

**Why DTOs over arrays:**
- Type safety + IDE autocomplete.
- Refactor-safe: adding a field is a compiler error at every call site.
- Tests are readable: `new RegisterUserInput(name: 'Ada', ...)` documents itself.

---

## 6. Value Objects

**Rule:** a value object exists when a primitive has *rules*. Use one if:
- It can be invalid (`Email`, `Slug`, `Money`) — validate in the constructor; an instance that exists is valid by definition.
- It has behavior (`Money::add()`, `Slug::matches()`).
- It crosses a module boundary as a *bare* value (not as a property of a model that's already passed).
- It appears in 3+ places where confusing it with a similar-typed value would be a real bug.

Do **not** wrap every primitive. `string $name` is fine. `Slug $slug` is not. Do **not** create a VO that no method or DTO actually receives as a typed parameter — that's decoration, and the architect agent will catch it (so will the next reviewer).

**Typed IDs — only where they cross a boundary as a bare value.** Most of this codebase passes the full Eloquent model across boundaries (`User`, `Tenant`, `Project`), so an `int $id` rarely travels alone. The exception is `TenantContext`, which exposes the current tenant's id to consumers that *don't* hold the `Tenant` model — multiple modules, multiple call sites. So:

- ✅ `TenantId` — used as the return type of `Tenant::tenantId()` and `TenantContext::tenantId()`, and as the parameter type on `CrossTenantAccessAttempted::with()`. Genuine boundary crossings.
- ❌ `UserId`, `MembershipId`, `ProjectId` — would be decoration in this codebase. Every place that would receive them already receives the full model. **Add one only when a new use case actually receives the bare id as a parameter.**

`TenantContext` exposes both methods deliberately:
- `tenantId(): TenantId` — for domain code and module-boundary calls (preferred).
- `id(): int` — for framework touchpoints (query builders, route binding) that need the raw int. Returns `$this->tenantId()->value`.

```php
<?php

declare(strict_types=1);

namespace App\Modules\Tenant\Domain\ValueObjects;

use InvalidArgumentException;
use Stringable;

final readonly class Slug implements Stringable
{
    public function __construct(public string $value)
    {
        if (! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value)) {
            throw new InvalidArgumentException("Invalid slug: {$value}");
        }
    }

    public static function fromName(string $name): self
    {
        return new self(Str::slug($name));
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
```

**Conventions:**
- `final readonly class`.
- Validate in the constructor. An instance that exists is valid by definition.
- Provide named constructors (`fromName`, `fromString`) when construction has variants.
- Live in `<Module>/Domain/ValueObjects/`.
- Cast on Eloquent: implement `Castable` or use an inline cast class.

---

## 7. Use Cases (Application layer)

**Rule:** one use case = one class = one public method named `execute()` (or `handle()` if it's queued). No "service" classes holding 10 methods.

```php
<?php

declare(strict_types=1);

namespace App\Modules\Auth\Application\UseCases;

final class RegisterUser
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly TenantRepository $tenants,
        private readonly Hasher $hasher,
        private readonly DatabaseManager $db,
        private readonly Dispatcher $events,
    ) {}

    public function execute(RegisterUserInput $input): User
    {
        return $this->db->transaction(function () use ($input) {
            $user = $this->users->create(/* ... */);
            $tenant = $this->tenants->create(/* ... */);
            $tenant->memberships()->create(['user_id' => $user->id, 'role' => Role::Owner]);

            $this->events->dispatch(new UserRegistered($user, $tenant));

            return $user;
        });
    }
}
```

**Conventions:**
- Named after the *action*, verb-first: `RegisterUser`, `InviteMember`, `CancelSubscription`.
- One public method. If you want two, write two classes.
- Transactions wrap *all* multi-write operations. No exceptions.
- Events dispatched *inside* the transaction; listeners that send email / call APIs must be queued so the transaction commits before they fire (use `ShouldQueueAfterCommit`).
- Live in `<Module>/Application/UseCases/`.

**What does NOT belong in a use case:**
- HTTP concerns (`request()`, `response()`, `redirect()`).
- View rendering.
- Direct Eloquent calls when a repository exists for that aggregate (see §9).

**Explicit state, not toggles.** Consequential actions are explicit: `BlockUser` / `UnblockUser`, not `ToggleUserBlock`. Two clear intents beat one ambiguous one.

**No proxy methods.** A use case that just forwards a single call to a repository or another service has no reason to exist. Call the dependency directly from the caller.

---

## 8. Controllers

**Rule:** controllers are dumb. Glue, not logic.

```php
<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Controllers;

final class RegisterController
{
    public function __invoke(RegisterRequest $request, RegisterUser $useCase): JsonResponse
    {
        $user = $useCase->execute(RegisterUserInput::fromRequest($request));

        return UserResource::make($user)->response()->setStatusCode(201);
    }
}
```

**Conventions:**
- **Single-action controllers** (`__invoke`) where it fits. Resource controllers OK for full CRUD.
- ≤ 15 lines per action. If you need more, the logic belongs in a use case.
- No `try/catch` for control flow — let exceptions bubble to the framework handler (see §13).
- No business logic. No DB queries. No `if` chains over roles.
- Inject use cases, not models or repositories.

---

## 9. Repository pattern — when and when not

**Default: don't.** Eloquent models are repositories. Wrapping them in `UserRepository` just to call `User::find()` is theater.

**Use a repository interface when at least one is true:**
1. The query is non-trivial and reused (complex joins, custom ordering).
2. You want to swap the implementation (e.g., search via Meilisearch instead of SQL).
3. You're testing a use case and want to fake reads without touching the DB.
4. The aggregate spans multiple tables and you want one consistent loading shape.

**If you use one, do it properly:**

```php
// Domain layer — interface
namespace App\Modules\Tenant\Domain\Repositories;

interface TenantRepository
{
    public function findBySlug(Slug $slug): ?Tenant;
    public function create(CreateTenantData $data): Tenant;
    public function softDelete(TenantId $id): void;
    public function restore(TenantId $id): void;
    public function isTrashed(TenantId $id): bool;
}

// Infrastructure layer — Eloquent implementation
namespace App\Modules\Tenant\Infrastructure\Persistence;

final class EloquentTenantRepository implements TenantRepository
{
    public function findBySlug(Slug $slug): ?Tenant
    {
        return Tenant::query()->where('slug', (string) $slug)->first();
    }

    public function create(CreateTenantData $data): Tenant
    {
        return Tenant::query()->create([...]);
    }

    public function softDelete(TenantId $id): void { /* ... */ }
    public function restore(TenantId $id): void { /* ... */ }
    public function isTrashed(TenantId $id): bool { /* ... */ }
}
```

**Conventions:**
- Interface in `Domain/Repositories/`. Implementation in `Infrastructure/Persistence/`, prefixed `Eloquent`.
- Bind in the module's `ServiceProvider`.
- Methods return domain models or `null`, not collections of arrays.
- Never expose `Builder` from a repository — that defeats the abstraction.
- Repositories are stateless. No caching inside them (cache at the use case if needed).
- **Soft-delete / restore / trashed-check belong in the repository**, not directly on the entity. Keeps the service from depending on Eloquent's `SoftDeletes` trait through the interface.
- **Test the implementation directly** (`EloquentTenantRepositoryTest`), not the interface.

**Anti-patterns to reject in review:**
- "Generic" repositories with `find($id)`, `save($model)`, `delete($model)`. That's just Eloquent with extra steps.
- Repositories that return query builders.
- A repository per model, mechanically. Repositories per *aggregate*, when justified.
- A service method that just proxies to a single repository method. Call the repo directly.

---

## 9a. Factories (creation pattern, not Laravel test factories)

**Distinct from §9 repositories** — repositories are about *retrieval and persistence of existing entities*; factories are about *creating new aggregates*. The two patterns coexist: a use case may call a factory to create and a repository to read.

**Use a factory interface when at least one is true:**
1. Creating a row requires multiple writes that must hold together (user + tenant + membership).
2. Creation has non-trivial logic (slug generation, password hashing, default-value derivation) that doesn't belong on the model.
3. You want to fake creation in a use-case test without booting Eloquent.
4. Multiple use cases create the same aggregate and you don't want the logic duplicated.

**Don't add a factory for a single `Model::create([...])` — that's exactly the §9 "Eloquent with extra steps" anti-pattern restated.**

**Naming and location:**
- Interface in `<Module>/Domain/Factories/<Name>Factory.php`.
- Implementation in `<Module>/Infrastructure/Factories/Eloquent<Name>Factory.php` (prefixed `Eloquent`, mirroring the repository convention).
- Factory name describes the *aggregate* it produces (`TenantOwnerFactory`, `InvitationFactory`), not the act of creating (`CreateTenantOwner` — that's the use case's job).
- Return type: a result DTO if it creates multiple entities (`TenantOwnerCreated`), or the single created model otherwise.
- Bind in the module's `ServiceProvider` (`public array $bindings`).

**Difference from use cases:**

| | Factory | Use Case |
|---|---|---|
| Owns | Writes | Orchestration (transactions, events, authorization checks, calling factories + repositories) |
| Touches Eloquent | Yes (the only place that does, in this convention) | No — calls the factory |
| Returns | The created aggregate (model or result DTO) | A domain model, often the same one the factory returned |
| Throws | Persistence errors only | Domain exceptions (`InsufficientRole`, `TenantSlugTaken`) |

```php
// Domain — the contract
interface TenantOwnerFactory
{
    public function create(RegisterUserInput $input): TenantOwnerCreated;
}

// Infrastructure — Eloquent implementation; the use case never sees these calls
final class EloquentTenantOwnerFactory implements TenantOwnerFactory
{
    public function create(RegisterUserInput $input): TenantOwnerCreated
    {
        $user = new User();
        $user->name = $input->name;
        // ...
        $user->save();

        $tenant = /* ... */;
        $membership = /* ... — attribute access, not mass-assign, because tenant_id is not fillable */;

        return new TenantOwnerCreated($user, $tenant, $membership);
    }
}

// Application — the use case orchestrates, never imports Eloquent
final class RegisterUser
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly Dispatcher $events,
        private readonly TenantOwnerFactory $factory,
    ) {}

    public function execute(RegisterUserInput $input): User
    {
        return $this->db->transaction(function () use ($input): User {
            $created = $this->factory->create($input);
            $this->events->dispatch(new TenantCreated($created->tenant));
            $this->events->dispatch(new UserRegistered($created->user, $created->tenant));

            return $created->user;
        });
    }
}
```

**Anti-patterns to reject:**
- A factory that wraps a single `Model::create(...)` with no other writes, no validation, no derivation. Use the model directly.
- A factory that holds a transaction. Transactions are the use case's responsibility (so the use case can orchestrate factory + events + other writes atomically).
- A factory that dispatches events. Same reason — events belong to the use case so it controls ordering and post-commit semantics.
- Using the term "factory" to mean a Laravel test factory (`Database/Factories/`) — those are unrelated. Reserve "factory" in domain code for the creation pattern.

---

## 10. Eloquent Models

**Rule:** models hold relations, casts, and *small* domain methods. They do not hold transactions, external calls, or cross-aggregate logic.

```php
<?php

declare(strict_types=1);

namespace App\Modules\Tenant\Domain\Models;

final class Tenant extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'plan'];

    protected $casts = [
        'plan' => Plan::class,
    ];

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function isOnPaidPlan(): bool
    {
        return $this->plan !== Plan::Free;
    }
}
```

**Conventions:**
- `final class` unless you genuinely need inheritance (you usually don't).
- `protected $fillable`, never `$guarded = []`.
- Casts for every non-string column. Especially enums, dates, JSON, booleans.
- Domain methods are fine *if* they touch only this model's own state. Cross-model logic → use case.
- Scopes: `scopeActive`, `scopeForTenant` — fine. Don't write scopes that hide business decisions (`scopeUsableByCurrentUser`).
- **Use `->is()` / `->isNot()`** for Eloquent comparisons. `$user->is($other)`, not `$user->id === $other->id`.
- **Eager load** relationships you'll use (`$tenant->load('memberships.user')`). Don't N+1 inside a foreach.

**Don't scope a `HasOne` / `HasMany` by a model property** (e.g. `->where('type', $this->type)` inside a relation). The relationship stops working in eager loads and `whereHas` contexts. If you need this, the relationship probably shouldn't exist — fix the schema.

---

## 11. Traits

Reserve traits for **cross-cutting framework-level behavior**, not domain logic.

Acceptable: `BelongsToTenant`, `HasUuid`, `LogsActivity`.
Not acceptable: `BillableTrait`, `WorkflowTrait` (those want to be objects).

Every trait gets a one-line PHPDoc explaining what it injects (boot logic, scopes, properties) — traits are invisible at the call site, so the docblock pays for itself.

---

## 12. Events & Listeners

**Conventions:**
- Events are **past tense**, named after what happened: `UserRegistered`, not `RegisterUser` or `OnUserRegister`.
- Event class is `final readonly`, holds only data, no behavior.
- Listeners are **single-purpose**. One listener = one side effect.
- Listeners that do I/O implement `ShouldQueueAfterCommit`.
- Naming: `<Verb><Subject>` for the listener — `SendInvitationEmail`, `LogAuditEntry`, `FanOutWebhook`.
- Subscribe via the module's `ServiceProvider` (`$listen` map), not via attribute discovery (explicit > magic).

---

## 13. Exceptions

**Three categories. No others.**

1. **Domain exceptions** — `extends \DomainException`. Thrown by domain/use cases. Live in `<Module>/Domain/Exceptions/`. Examples: `TenantSlugTaken`, `MembershipAlreadyExists`.
2. **Framework exceptions** — `ValidationException`, `AuthorizationException`, `ModelNotFoundException`. Let them bubble. The framework handler renders them.
3. **Infrastructure exceptions** — `extends \RuntimeException`. Thrown by adapters (HTTP client, Stripe). Wrap third-party exceptions; never let raw `Stripe\Exception\*` reach the controller.

**Conventions:**
- Every domain exception has a static named constructor: `TenantSlugTaken::forSlug($slug)`. Reads better in tests.
- Map domain exceptions → HTTP status in `bootstrap/app.php`'s exception handler (`Exceptions::render()`), never in controllers.
- Never catch `\Throwable` or `\Exception` to "log and continue." Log via framework handler.
- **Never expose exception detail in HTTP responses.** Log the detail; show users a generic message. Detailed messages leak schema, query shape, or internal state.

---

## 14. Validation

- **All** validation via `FormRequest`. No `validate()` in controllers.
- Authorization in `FormRequest::authorize()` for "can this user even attempt this action" checks. Policies for resource-level "can this user do X to this Y."
- **Entity-aware business rules go in the FormRequest, not the controller.** If route-model binding gives the FormRequest access to the entity, the rule belongs there so failures come back as structured 422s attached to the offending field — not a thrown exception or controller-level redirect.
- Rules use array syntax, not pipe strings:
  ```php
  'email' => ['required', 'email', 'max:255'],
  ```
- Custom rules → invokable rule classes in `<Module>/Http/Rules/`.
- **Don't null an unchanged field.** If a field is locked or not editable, omit it from the update payload — don't pass `null` and overwrite the stored value.

---

## 15. Resources (response shaping)

- All API responses go through a `JsonResource` or `ResourceCollection`. No `response()->json($model->toArray())`.
- Resources live in `<Module>/Http/Resources/`.
- Resources flatten the model into the API shape. **Renaming happens here, not on the model.**
- Never expose `id` of pivot tables or framework internals. Never expose `password`, `remember_token`, internal flags.

---

## 16. Jobs

**Conventions:**
- `final class` implements `ShouldQueue`.
- Constructor takes only **serializable** primitives or model references (use `SerializesModels`).
- `handle()` is the single public method.
- **Idempotency is mandatory.** A job that runs twice must produce the same outcome. Use unique constraints, locks, or `firstOrCreate` — not "hope it doesn't fire twice."
- Failure handling via `failed(Throwable $e): void`, not try/catch inside `handle()`.
- `tries`, `backoff`, `timeout` set explicitly. No defaults.

---

## 17. Database / migrations

- Migrations are **never edited** after merge. New change = new migration.
- **No `down()` methods.** A safe rollback can never be entirely predicted ahead of time. Fix forwards with new migrations.
- **No DB `enum` column type.** Use `string` with a PHP enum cast (see §4).
- **Default to no cascade deletes.** Handle deletion in app code so the order is explicit and listenable. Cascade is only acceptable when the relationship is *part of* the parent's lifecycle and there's no business logic to run on delete (e.g., `memberships` when a tenant is deleted — and even then, prefer a use case that fans out events first).
- Every tenant-owned table has `tenant_id` as the **second** column, after `id`. Indexed.
- Composite indexes ordered by selectivity. Document with a comment if non-obvious.
- Timestamps: `created_at`, `updated_at` always. `deleted_at` only when soft-deletes are deliberate.
- **No pivot tables for single-owner relationships.** Use a flag column or a typed FK instead.
- Use `uuid` PKs only when IDs leak externally (webhook IDs, public-share tokens). Bigints elsewhere.
- **Normalise existing data in the same migration** when changing identifier formats. Don't rely on app code to match both old and new formats indefinitely.
- **No data backfills inside schema migrations.** Run backfills as a separate `php artisan` command so schema and data changes can be deployed independently.
- **Migrations are exempt from the "`final class`" rule** in §10 / §16 / etc. Laravel's anonymous-class migration syntax (`return new class extends Migration { ... }`) is the canonical pattern, and PHP does not permit `final` on anonymous classes (the RFC for `new final class` was rejected). Don't try to make migration classes final; reviewers should not flag this.

---

## 18. Tests

- **Pest 4**, not PHPUnit syntax.
- Test file mirrors source path: `app/Modules/Tenant/Domain/Models/Tenant.php` → `app/Modules/Tenant/Tests/Domain/Models/TenantTest.php` (module-local) for unit; cross-module integration in `tests/Feature/`.
- Test names are sentences: `it('rejects cross-tenant resource access')`, not `test_user_cannot_access_other_tenant()`.
- One assertion *concept* per test. Multiple `expect()` calls fine if they prove the same point.
- **`assertSame` over `assertEquals`** for string/scalar comparisons. Strict type checking catches subtle bugs. Mago enforces this.
- **Always** use `RefreshDatabase` for feature tests. Never share state across tests.
- Factories for all models. No raw `Model::create([...])` in tests.
- **Assert specific values** — check redirect targets, session values, response status, payload contents. Not just "didn't throw."
- **Don't test DB constraints from PHP.** If a unique constraint prevents duplicates, testing for it in PHP is pointless — you're testing MySQL, not your code.
- **Test the implementation, not the interface** — `new EloquentTenantRepository()` in tests, name the file `EloquentTenantRepositoryTest`.
- **No `sleep()` / `wait()` in tests.** Test throttling and rate-limiting by checking error message content, not by waiting.
- The **cross-tenant isolation** suite is the most important test file in the repo. It must be obvious where to find it.

---

## 19. Static analysis & formatting

**Mago** (Carthage Software) is the analyzer + linter. Pint is still the auto-formatter (Mago's formatter may take over later).

- `mago analyse` is the static analyser — equivalent of PHPStan level 8 strictness.
- `mago lint` runs Mago's lint rule set (including the `cyclomatic-complexity` and `sensitive-parameter` rules we lean on).
- `composer lint` runs `pint --test` *and* `mago lint --fail-on-out-of-sync-baseline`.
- `composer analyse` runs `mago analyse --fail-on-out-of-sync-baseline`.

The binary lives at `tools/mago` (gitignored). Install via `tools/install-mago.sh` — CI runs this in its own step.

**Baseline discipline:**
- **Never add entries to `mago-lint-baseline.toml` or `mago-analyse-baseline.toml`.** New code must pass cleanly.
- Baselines exist *only* to grandfather pre-existing issues so Mago could be adopted incrementally. They are not a dumping ground for new problems.
- **Remove entries when you touch a baselined file.** Run `tools/mago lint --ignore-baseline <files>` and `tools/mago analyse --ignore-baseline <files>` against your changed files. Fix what's reported. Drop those entries from the baseline.
- Baselines must shrink, never grow. A PR that adds baseline entries has either introduced new issues (fix them) or regenerated the baseline to silence them (don't). CI runs with `--fail-on-out-of-sync-baseline` so a PR that fixes a baselined issue but forgets to remove the entry also fails.
- No `// @mago-ignore` without a same-line comment explaining *why*.

---

## 20. Module boundaries

Enforce via convention + review:

- A module's `Http/`, `Application/`, `Domain/`, `Infrastructure/` can use **its own** sibling layers freely.
- A module **may** depend on another module's `Domain/` (models, events, value objects).
- A module **must not** import another module's `Application/`, `Http/`, or `Infrastructure/`. If you need behavior from another module, **dispatch an event** or **inject an interface** the other module's service provider binds.
- `Support/` and `Providers/` are global.

Rule of thumb: if you're typing `use App\Modules\Other\Application\...`, stop. Use an event.

**Bindings go in the module's `ServiceProvider`**, not duplicated in `AppServiceProvider`. Repository → implementation, event → listener, contract → adapter — all in one place per module.

---

## 21. Comments

- **Class-level docblocks explain why the class exists**, non-obvious invariants, or trade-offs. They do *not* narrate dependency internals, restate per-method behaviour, or describe what's plain from reading the code.
- **Interface docblocks don't reference their implementations.** An interface is a contract — it shouldn't know about its concrete impls.
- **Don't strip context comments during refactors.** When extracting methods or moving code, preserve the `// why` comments that explain non-obvious behaviour. The `// what` is in the code; the `// why` is the gold.
- **No AI-style header docblocks** that restate what the code does in prose. They rot the moment behaviour changes.

---

## 22. Commits & PRs

- Conventional commits: `feat:`, `fix:`, `refactor:`, `test:`, `docs:`, `chore:`.
- Imperative mood: `add tenant resolver middleware`, not `added` or `adds`.
- PR title = top commit. PR description answers *why*, not *what* (the diff shows what).
- Each PR leaves CI green. No "fix CI" follow-ups on the same branch — squash.
- **Spec is authoritative; tickets summarise.** Before implementing a structural change (schema, unique constraint, type model, lock semantics), re-read the source design doc. If the ticket conflicts with the spec, the spec wins — and fix the ticket in the same PR.

---

## 23. Things that will fail review

- `array $data` parameter on a public method (use a DTO).
- `string $role` (use the `Role` enum).
- `int $tenantId` across a module boundary (use a `TenantId` VO).
- `Model::all()` in a controller.
- A new `*Manager` / `*Helper` / `*Util` class.
- A `try { } catch (\Exception)` that swallows + logs.
- A service method that just proxies to a single repository call.
- A `toggle*` method on a model or service for a consequential action.
- A migration that backfills data (use a separate command).
- A migration with a DB `enum` column.
- A migration with `cascadeOnDelete()` on a parent → child relationship that has business logic to run on delete.
- A test named `test_it_works`.
- A test using `assertEquals` for a scalar comparison.
- A class-level docblock that restates what the class does.
- An `else` branch for a state the type system prevents.
- Adding a package without a one-line entry in the README's "Dependencies" table.
- Adding an entry to a Mago baseline file.

---

## 24. Things that will pass review without comment

- A use case that's 20 lines, takes a DTO, returns a model, dispatches an event in a transaction.
- An enum with a `match` method capturing behavior that used to be a switch elsewhere.
- A value object that throws in its constructor and has no setters.
- A `TenantId` VO instead of `int $tenantId` across a boundary.
- A test that proves an isolation invariant by trying to violate it.
- A PR that *removes* entries from a Mago baseline.
- Deleting code.
