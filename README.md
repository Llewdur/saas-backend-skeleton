# SaaS Backend Skeleton

> A startup-grade Laravel backend skeleton with multi-tenancy, RBAC, Sanctum auth, audit logging, outbound webhooks, and Stripe billing scaffolding — built monolith-first, modular internally.

![CI](https://github.com/Llewdur/saas-backend-skeleton/actions/workflows/ci.yml/badge.svg)
![PHP 8.4](https://img.shields.io/badge/php-8.4-777BB4)
![Laravel 13](https://img.shields.io/badge/laravel-13-FF2D20)
![License MIT](https://img.shields.io/badge/license-MIT-blue)

---

## What this is

A reference backend for a multi-tenant SaaS, built to read in five minutes and run in one. The goal is to demonstrate the *shape* of a production SaaS backend — module boundaries, request flow, multi-tenancy enforcement, async/event design — without the noise of any specific product.

If you're reviewing this for a hiring decision, the things worth a careful read are:

- **Multi-tenancy enforcement** — `app/Modules/Tenant/Infrastructure/Persistence/BelongsToTenant.php` (the trait), `ResolveTenant.php` (the middleware), `TenantContext.php` (the singleton), and `tests/Feature/ProjectsTest.php` (the isolation tests that prove cross-tenant access cannot be bypassed, including via a forged `tenant_id` in the payload).
- **Request flow** — controllers are thin, use cases own logic, DTOs cross boundaries. See `app/Modules/Auth/Application/UseCases/RegisterUser.php` for the canonical example (creates user + tenant + owner membership atomically inside a transaction).
- **Module boundaries** — each module has its own `Domain/`, `Application/`, `Http/`, `Infrastructure/`, `Database/`, and `Tests/`. Cross-module communication happens through events, not direct imports.

## What this isn't

Not a tutorial repo. Not a product clone. Not a Kubernetes / microservices demo. The full list of deliberate exclusions and the *why* for each lives in [`PLANNING.md`](PLANNING.md) §3.

---

## Architecture at a glance

```
┌──────────────────────────────────────────────────────────────┐
│  HTTP request                                                │
│  ↓                                                           │
│  api middleware: throttle → tenant.resolve → ...             │
│  ↓                                                           │
│  Substitute bindings (route-model binding — tenant-scoped)   │
│  ↓                                                           │
│  auth:sanctum → tenant.ensure (for tenant-scoped routes)     │
│  ↓                                                           │
│  Controller (thin glue, ≤ 15 lines per action)               │
│  ↓                                                           │
│  Use case (one class, one execute(), wraps a transaction)    │
│  ↓                                                           │
│  Domain model / Repository                                   │
│  ↓                                                           │
│  Eloquent (global-scoped by current tenant)                  │
└──────────────────────────────────────────────────────────────┘
```

```
app/
└── Modules/
    ├── Tenant/         ← multi-tenancy primitives (the centerpiece)
    ├── Auth/           ← register, login, logout, me — issues Sanctum tokens
    ├── Users/          ← profile, member list, role updates
    ├── Core/           ← Projects (placeholder business resource)
    ├── Audit/          ← spatie/activitylog wired to Tenant + Project
    ├── Integrations/   ← outbound webhooks with HMAC-SHA256 signatures
    └── Billing/        ← Cashier-ready subscription endpoint (stub)
```

Each module owns its layers internally:

```
Modules/<Name>/
├── Domain/              ← models, value objects, enums, events, exceptions
├── Application/         ← use cases, DTOs
├── Http/                ← controllers, requests, resources, routes, middleware
├── Infrastructure/      ← persistence, jobs, listeners, external adapters
├── Database/            ← migrations, factories
└── Tests/               ← module-local unit tests
```

---

## Key design decisions

| Decision | Choice | Why |
|---|---|---|
| Tenancy model | Single DB, `tenant_id` column | Schema-per-tenant is overkill for this scope; the right call for >95% of SaaS. |
| Tenancy enforcement | Trait global scope + middleware + base policy | Defense in depth. Three independent layers; the trait makes "forgot to scope" unreachable. |
| Auth | Sanctum (token-based) | Right tool for SPA + mobile + machine clients. No OAuth-provider features needed. |
| Static analysis | PHPStan level 8 (Larastan), targeting Mago | Mago is the goal (faster, modern); composer wrapper currently broken upstream. Rules in `CODING_STANDARDS.md` §19 apply to both. |
| Formatting | Laravel Pint with `declare(strict_types)` enforced | One opinionated config; CI fails on deviation. |
| Tests | Pest 4 | Reads better in PRs; same assertion library underneath. |
| DB (dev) | SQLite | Zero-setup `clone && run`. Reviewer doesn't need a database server. |
| DB (prod-target) | PostgreSQL | Migrations written compatibly; FKs, JSON, indexes all standard. |
| Queue | Database driver (dev), Redis-ready | Same reasoning — keep clone-and-run frictionless. |
| Audit | `spatie/laravel-activitylog` | Boring correct answer. Rolling your own is a negative signal. |
| Billing | Laravel Cashier (Stripe), stub endpoint | Wire Stripe keys to enable. Customer = User in the stub; production should be tenant-billable. |

The full rationale + exclusions list is in [`PLANNING.md`](PLANNING.md).

---

## Quickstart

> Requires PHP 8.4, Composer 2, and the standard PHP extensions: `xml`, `mbstring`, `curl`, `zip`, `sqlite3`, `bcmath`, `intl`.

```bash
git clone https://github.com/Llewdur/saas-backend-skeleton.git
cd saas-backend-skeleton

composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate

# Run the suite (29 tests, ~0.7s)
composer test

# Static analysis + formatting check
composer analyse
composer lint

# Start the dev server
php artisan serve
```

The API is at `http://127.0.0.1:8000/api/v1/...`.

### Sail (Docker)

A Sail dev environment is included as a dev dependency. If you'd rather develop in Docker:

```bash
./vendor/bin/sail up -d
./vendor/bin/sail composer install
./vendor/bin/sail artisan migrate
./vendor/bin/sail test
```

---

## API surface

All routes are prefixed `/api/v1`. Tenant-scoped routes accept tenant resolution via:
1. `X-Tenant: <slug>` header (preferred), or
2. The authenticated user's first membership.

| Method | Path | Scope | Notes |
|---|---|---|---|
| `POST` | `/auth/register` | public | Creates user + personal tenant + owner membership atomically. |
| `POST` | `/auth/login` | public | Returns a Sanctum token. |
| `POST` | `/auth/logout` | auth | Revokes the current token. |
| `GET`  | `/auth/me` | auth | Current user. |
| `PATCH`| `/users/me` | auth | Update own profile (sparse). |
| `GET`  | `/members` | auth + tenant | List members of the current tenant. |
| `PATCH`| `/members/{id}` | auth + tenant | Owner/Admin can change a member's role; last owner cannot be demoted. |
| `GET`  | `/projects` | auth + tenant | Paginated, tenant-scoped. |
| `POST` | `/projects` | auth + tenant | Create. Forged `tenant_id` in payload is ignored — the trait auto-fills from context. |
| `GET`  | `/projects/{id}` | auth + tenant | 404 if the project belongs to another tenant. |
| `PATCH`| `/projects/{id}` | auth + tenant | Sparse update — only fields present in the payload are touched. |
| `DELETE`| `/projects/{id}` | auth + tenant | Same isolation rules. |
| `GET`  | `/audit` | auth + tenant | Activity log scoped to current tenant. |
| `GET`  | `/billing/subscription` | auth | Returns `{status: 'none', plan: 'free'}` until Stripe is wired. |

---

## Multi-tenancy walkthrough

The single feature worth understanding is how an authenticated API request gets scoped to a tenant. Three pieces:

**1. `ResolveTenant` middleware** — `app/Modules/Tenant/Http/Middleware/ResolveTenant.php`

Runs *before* `SubstituteBindings` (registered with `prepend:` in `bootstrap/app.php`). Reads the `X-Tenant` header (or falls back to the authenticated user's first membership) and binds the tenant into a request-scoped `TenantContext` singleton.

**2. `BelongsToTenant` trait** — `app/Modules/Tenant/Infrastructure/Persistence/BelongsToTenant.php`

Two pieces of magic, both opt-in:

```php
// On every query against the model:
$query->where('<table>.tenant_id', $context->id());

// On every model create:
if ($model->getAttribute('tenant_id') === null) {
    $model->setAttribute('tenant_id', $context->id());
}
```

Any model that uses the trait is automatically tenant-scoped on read and tenant-stamped on write. **A forged `tenant_id` in the request payload is overwritten**, because the trait's `creating` hook only fills when `tenant_id` is null — but `Project::$fillable` doesn't include `tenant_id`, so it's stripped from the mass-assign anyway. Two independent reasons it can't be spoofed.

**3. `TenantPolicy` (defense in depth)** — base policy rejects any access where the resource's `tenant_id` doesn't match the context.

Proof that this holds: see `tests/Feature/ProjectsTest.php` — there are tests for cross-tenant view, delete, *and* the forged-tenant-id case.

---

## Testing & CI

- **29 tests, 72 assertions, ~0.7s.** Pest 4, SQLite in-memory.
- The cross-tenant isolation suite is `tests/Feature/ProjectsTest.php` — the most important test file in the repo.
- CI (`.github/workflows/ci.yml`) runs on every push and PR:
  - `composer lint` — Pint --test
  - `composer analyse` — PHPStan level 8
  - `composer test` — full Pest suite

```bash
composer ci   # runs all three locally
```

---

## What's intentionally not included

These are *deliberate* omissions, not gaps. The restraint is the signal.

- **No microservices.** Monolith first.
- **No Kubernetes / Terraform.** Out of scope.
- **No GraphQL.** REST with versioning is the right call here.
- **No frontend.** Backend signal project.
- **No event sourcing / CQRS.** Overengineering at this scale.
- **No custom DDD scaffolding.** Eloquent is a fine repository; pretending otherwise reads as cargo cult.
- **No password reset flow.** Use Laravel's built-in scaffolding when needed — not load-bearing for the demo.
- **No invitation flow.** Adding a member is by direct membership creation today; a signed-URL invite flow is a half-day add when needed.
- **No tenant-billable migration.** Cashier's `customer_columns` is on `users` in this skeleton; production should move it to `tenants` (small migration + trait swap).

The conventions that govern *all* decisions in this repo are in [`CODING_STANDARDS.md`](CODING_STANDARDS.md).

---

## Dependencies

Runtime:

| Package | Purpose |
|---|---|
| `laravel/framework` ^13 | Web framework |
| `laravel/sanctum` | Token auth |
| `laravel/cashier` | Stripe billing |
| `spatie/laravel-permission` | Role/permission scaffolding |
| `spatie/laravel-activitylog` | Audit log |

Dev:

| Package | Purpose |
|---|---|
| `pestphp/pest` + `-plugin-laravel` | Tests |
| `larastan/larastan` | Static analysis (Mago is the target — see §19 in standards) |
| `laravel/pint` | Code style |
| `laravel/sail` | Optional Docker dev environment |

---

## Repository structure

```
saas-backend-skeleton/
├── app/
│   ├── Models/User.php                # Auth user (Laravel-default location)
│   ├── Modules/                       # One folder per bounded context
│   │   ├── Tenant/                    # Multi-tenancy primitives
│   │   ├── Auth/                      # Register / login / logout / me
│   │   ├── Users/                     # Profile + members + role updates
│   │   ├── Core/                      # Projects (placeholder business)
│   │   ├── Audit/                     # Activity log read endpoint
│   │   ├── Integrations/              # Outbound webhooks
│   │   └── Billing/                   # Cashier subscription stub
│   └── Support/
│       └── ModuleServiceProvider.php  # Base provider each module extends
├── bootstrap/
│   ├── app.php                        # Middleware stack — tenant.resolve prepended
│   └── providers.php                  # Each module's provider registered
├── database/                          # Only framework migrations live here
├── tests/
│   ├── Feature/
│   │   ├── AuthTest.php
│   │   ├── UsersTest.php
│   │   ├── ProjectsTest.php           # ← cross-tenant isolation tests
│   │   ├── AuditTest.php
│   │   ├── WebhooksTest.php
│   │   ├── BillingTest.php
│   │   └── TenantIsolationTest.php
│   └── Pest.php
├── PLANNING.md                        # Internal planning doc (why)
├── CODING_STANDARDS.md                # 24 sections, every rule justified
└── README.md                          # This file
```

---

## License

MIT.

## Author

Built by [Llewellyn du Randt](https://github.com/Llewdur) as a public reference for SaaS backend architecture. Pull requests welcome; sharper opinions even more so.
