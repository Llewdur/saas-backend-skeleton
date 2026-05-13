# SaaS Backend Skeleton — Planning Doc

> Internal planning document. Not the public README.
> Captures *why* before *what* so the codebase reads coherently.

---

## 1. Positioning

**Goal:** a public GitHub project that signals "founding engineer / senior SaaS backend" to anyone scanning the repo in 60 seconds.

**Not goals:**
- A feature factory or product clone.
- A tutorial repo.
- A microservices showcase.
- A frontend project.

**Audience for the repo (in order):**
1. Hiring managers / founders skimming on GitHub.
2. Senior engineers doing a 10-minute code review.
3. Future me, using it as a real starter.

**What "signal" means concretely:**
- Module boundaries are obvious from the folder tree.
- Multi-tenancy is enforced *by the framework*, not by developer discipline.
- Tests exist and run in CI on first push.
- Static analysis (PHPStan level 8) is green.
- README answers "what is this and why is it built this way?" before "how do I run it?"

---

## 2. Core principle

> **Monolith first, modular internally, scalable by design.**

A single Laravel app, partitioned into modules with explicit internal layering. No package boundaries (`composer.json` per module) — that's overkill at this scale and screams cargo-cult DDD. Just convention + static analysis to enforce boundaries.

---

## 3. Stack decisions (with rationale)

| Choice | Pick | Why |
|---|---|---|
| Language | PHP 8.4 | Latest stable, available locally, modern syntax (readonly, enums, property hooks). |
| Framework | Laravel 11 | Mainstream, batteries-included, what SaaS shops actually use. |
| Auth | Sanctum | Token-based API auth, no OAuth-provider complexity needed. |
| Database (dev) | SQLite | Zero-setup `clone && run`. Reviewers should not need a database server. |
| Database (prod-target) | PostgreSQL | Migrations written to work on Postgres; JSON columns, proper FKs. |
| Queue | Database driver (dev), Redis-ready | Same reasoning — clone-and-run beats infra setup for a demo repo. |
| Billing | Laravel Cashier (Stripe) | Boring correct answer. Raw Stripe SDK reads junior. |
| Audit log | `spatie/laravel-activitylog` | Same reasoning. Custom audit = negative signal here. |
| RBAC | `spatie/laravel-permission` | Same reasoning. |
| Static analysis | PHPStan (Larastan) level 8 | Highest practical level for Laravel. |
| Formatting | Laravel Pint | Default. |
| Tests | Pest 3 | Modern, reads better in PRs. |
| CI | GitHub Actions | Pint + PHPStan + Pest, every push. |

**Things deliberately NOT included** (and why, so reviewers see the restraint):
- Docker / Sail — adds setup friction for the demo.
- Kubernetes manifests — out of scope.
- Microservices — explicitly anti-goal.
- GraphQL — REST is the right call here.
- Frontend — backend signal project.
- Event sourcing / CQRS — overengineering for the goal.
- Custom DDD scaffolding (Aggregates, Repositories with interfaces, etc.) — Eloquent is fine; pretending otherwise reads as cargo cult.

---

## 4. Folder structure

```
app/
  Modules/
    Tenant/
      Domain/              # Models, Events, ValueObjects scoped to Tenant
      Application/         # UseCases / Services
      Http/                # Controllers, Requests, Resources, Routes, Middleware
      Infrastructure/      # External integrations, repository implementations
      Database/            # Migrations, Factories, Seeders specific to module
      Tests/               # Feature + unit tests for this module
    Auth/
    Users/
    Billing/
    Audit/
    Integrations/
    Core/
  Support/                 # Cross-cutting helpers (response macros, base classes)
  Providers/

bootstrap/
config/
database/                  # Only global migrations live here (e.g. queue, cache tables)
routes/                    # Thin — each module registers its own routes
tests/                     # Cross-module integration tests only
.github/workflows/         # CI
```

**Why module-internal layering and not global `Domain/`, `Application/`, `Infrastructure/`:**

- Avoids "where does `UserCreated` live — `Modules/Auth/` or `Domain/Events/`?" ambiguity.
- Makes each module deletable / extractable as one folder if it ever needs to leave the monolith.
- Tree depth is the same; the cognitive model is simpler.

**Routing convention:** each module exposes `Http/Routes/api.php`. A `RouteServiceProvider` in each module's `ModuleServiceProvider` registers them under `/api/v1`. Module names never leak into URLs (`/api/v1/tenants`, not `/api/v1/tenant-module/tenants`).

---

## 5. Modules

### 5.1 Tenant *(the centerpiece — wired end-to-end)*

The thing reviewers will look at first. Must be correct.

**Domain:**
- `Tenant` model (`id`, `name`, `slug`, `plan`, timestamps).
- `Membership` model (pivot: `tenant_id`, `user_id`, `role`).
- `BelongsToTenant` trait → adds global scope filtering by `app('tenant')->id`.
- `TenantCreated` event.

**Enforcement:**
- `ResolveTenant` middleware reads tenant from:
  1. `X-Tenant` header (slug or ID), or
  2. authenticated user's default membership.
- Binds resolved tenant into the container as `app('tenant')`.
- Models using `BelongsToTenant` auto-scope all queries.
- Inserts auto-fill `tenant_id` via the trait's `creating` hook.
- A base `TenantPolicy` rejects cross-tenant access at the policy layer as defense-in-depth.

**Tests must prove:**
- Tenant A cannot read Tenant B's resources.
- Tenant A cannot create resources for Tenant B even with a forged `tenant_id` in the payload.
- Switching tenant context mid-request is rejected.

### 5.2 Auth

- Register: creates user + a personal tenant + owner membership in one transaction.
- Login: issues Sanctum token.
- Logout: revokes current token.
- Password reset: standard Laravel flow, but emails routed through queue.

### 5.3 Users

- Profile read/update.
- Role assignment within current tenant (via `spatie/laravel-permission` scoped per tenant).
- Invitation flow: invite by email → email contains signed URL → accept creates membership.

### 5.4 Core *(placeholder business module)*

A single resource — **Projects** — so reviewers can see the multi-tenant request flow end-to-end without distraction. Deliberately generic.

- `Project` model with `BelongsToTenant`.
- Full CRUD via `/api/v1/projects`.
- Policy enforces tenant scoping + role-based create/update/delete.

### 5.5 Billing

Structured but not fully wired (Stripe sandbox setup is reviewer-side friction).

- Cashier installed.
- `subscriptions`, `subscription_items` tables migrated.
- `/api/v1/billing/subscription` endpoint to view current subscription.
- Webhook controller stub with signature verification.
- README clearly marks "wire your Stripe keys to enable."

### 5.6 Audit

- `spatie/laravel-activitylog` installed.
- Trait applied to `Tenant`, `User`, `Project`.
- `/api/v1/audit` endpoint to list activity for current tenant.

### 5.7 Integrations

- Outbound webhook dispatcher (queued job).
- `Webhook` model: tenant_id, url, events[], secret.
- Listeners on key domain events (`TenantCreated`, `ProjectCreated`) fan out to registered webhooks.
- HMAC-SHA256 signature in `X-Signature` header on outbound calls.

---

## 6. Request flow (canonical)

```
Route
  ↓
FormRequest (validation)
  ↓
Controller (thin — calls one use case)
  ↓
UseCase / Service (orchestration, transactions)
  ↓
Domain Model (business rules)
  ↓
Eloquent / external API
```

**Rules:**
- Controllers ≤ 15 lines per action.
- No Eloquent in controllers.
- No HTTP concerns in use cases (no `request()`, no `response()`).
- Use cases return domain objects or DTOs, never `JsonResponse`.
- `Resource` classes shape the response.

---

## 7. API conventions

- REST. Versioned: `/api/v1/...`.
- Response envelope:
  ```json
  { "data": {...}, "meta": {...} }
  ```
- Error envelope:
  ```json
  { "errors": [{ "code": "...", "title": "...", "detail": "...", "source": {...} }] }
  ```
- 422 for validation, 403 for authz, 401 for unauth, 404 for not found, 409 for conflict.
- All list endpoints paginated, cursor-based where order matters.

---

## 8. Multi-tenancy strategy

**Single database, `tenant_id` column on every tenant-owned table.**

Not chosen: schema-per-tenant, database-per-tenant. Both overkill for the goal and would actively hurt the "founding engineer pragmatism" signal.

**Enforcement layers (defense in depth):**
1. `BelongsToTenant` trait → global scope on every query.
2. Trait's `creating` event → auto-fills `tenant_id`.
3. Base policy → rejects cross-tenant access.
4. `EnsureTenantContext` middleware → 400 if no tenant resolvable.
5. Foreign keys at DB level with `ON DELETE CASCADE`.

---

## 9. Async / queue design

- All emails queued.
- All outbound webhooks queued.
- All audit log writes for high-volume models queued (configurable).

Jobs to ship:
- `SendInvitationEmail`
- `DispatchOutboundWebhook`
- `ProcessIncomingWebhook` (Stripe)

---

## 10. Events

| Event | Listeners |
|---|---|
| `UserRegistered` | Log audit entry |
| `TenantCreated` | Log audit entry, fan out webhook |
| `MembershipInvited` | Send invitation email |
| `ProjectCreated` | Log audit entry, fan out webhook |
| `SubscriptionUpdated` | Log audit entry, refresh cached plan limits |

---

## 11. Implementation phases

Built in this order so each phase produces a runnable, testable state.

1. **Scaffold** — fresh Laravel install, base config, CI workflow, PHPStan + Pint + Pest green on empty project.
2. **Tenant module** — model, trait, middleware, tests proving isolation.
3. **Auth module** — register / login / logout, register creates tenant atomically.
4. **Users module** — profile, invitations, roles within tenant.
5. **Core module** — Projects resource, full CRUD, tenant-scoped.
6. **Audit module** — activitylog wired to models, list endpoint.
7. **Integrations module** — webhook fan-out on domain events.
8. **Billing module** — Cashier installed, subscription view endpoint, webhook stub.
9. **README polish** — architecture diagram, quickstart, decision log.

Each phase ends with: tests green, PHPStan green, Pint clean, commit.

---

## 12. README structure (public-facing)

The README is the product. Order matters:

1. **One-line description** + badges (CI status, PHP version, license).
2. **What this is and isn't** (the positioning bit, ~3 sentences).
3. **Architecture at a glance** — ASCII diagram of modules + request flow.
4. **Key design decisions** — short table with rationale links.
5. **Quickstart** — clone, install, run, test. Must work in <2 minutes.
6. **Module guide** — one paragraph per module + link to its folder.
7. **Multi-tenancy walkthrough** — code snippet showing how a request is scoped.
8. **Testing & CI** — what runs where.
9. **What's intentionally not included** — the restraint signal.
10. **License + author.**

---

## 13. Definition of done

- [ ] `composer install && php artisan migrate --seed && php artisan serve` works on a fresh clone.
- [ ] `php artisan test` is green.
- [ ] `vendor/bin/phpstan analyse` is green at level 8.
- [ ] `vendor/bin/pint --test` is clean.
- [ ] GitHub Actions CI is green on `main`.
- [ ] README renders correctly on GitHub and tells a coherent story top to bottom.
- [ ] Repository has a license (MIT).
- [ ] At least one test proves cross-tenant isolation cannot be bypassed.
