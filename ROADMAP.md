# Roadmap

> Forward-looking. For the *historical* plan that shipped the initial scaffold, see [`PLANNING.md`](PLANNING.md).

The skeleton is complete and shippable as-is. This document tracks the items that *would* be needed to take it from "demonstrates the shape of a SaaS backend" to "ready to host a real product." Each row links to a tracking issue.

---

## Carried over from the multi-agent review

Items the four expert reviewers flagged that were deliberately scoped out of the initial PRs.

| # | Item | Why deferred | Issue |
|---|---|---|---|
| 1 | Move `User` model from `app/Models/` into `Modules/Users/Domain/Models/` | Ripple effects through Sanctum + Cashier glue | [#4](https://github.com/Llewdur/saas-backend-skeleton/issues/4) |
| 2 | Async-create register flow to close the email-enumeration leak | Needs queued mailer + verify-email flow; its own feature | [#5](https://github.com/Llewdur/saas-backend-skeleton/issues/5) |
| 3 | Move Cashier billing from `User` to `Tenant` | Production-readiness; needs Stripe credentials regardless | [#6](https://github.com/Llewdur/saas-backend-skeleton/issues/6) |

## Features

Real product surfaces the skeleton stubs out.

| # | Item | Issue |
|---|---|---|
| 4 | Invitation flow for tenant members (signed URL, expiry, replay protection) | [#7](https://github.com/Llewdur/saas-backend-skeleton/issues/7) |
| 5 | Password reset flow (Laravel built-in wired to API) | [#8](https://github.com/Llewdur/saas-backend-skeleton/issues/8) |

## Tooling

| # | Item | Issue |
|---|---|---|
| 6 | Raise test coverage and re-enable the gate at 80% (currently ungated) | [#10](https://github.com/Llewdur/saas-backend-skeleton/issues/10) |

### Done

- Swap PHPStan/Larastan for Mago — closed by #13. Binary install via `tools/install-mago.sh`; baselines hold pre-existing findings, new code must pass cleanly.

---

## Anti-roadmap

Items that *would* go on a real product roadmap but are deliberately out of scope for the skeleton:

- Frontend (SPA, web, mobile).
- Production observability stack (Sentry, Datadog, OpenTelemetry). The skeleton's `dontReport()` filter on control-flow exceptions is the only nod in that direction.
- Real Stripe credentials, real outbound mail, real queue backend (Redis). The skeleton uses the synchronous / array drivers so it runs without infrastructure.
- Multi-region. Single-DB, single-region — see `PLANNING.md` §8 for why.
- GraphQL / event sourcing / CQRS — explicitly excluded; see `PLANNING.md` §3 bottom.

If any of these became real product requirements, the skeleton's module boundaries make them a *change*, not a *rewrite*. That's the whole point.

---

## How to use this doc

- Each item links to a GitHub issue. Pick one off the top, branch from `main`, follow the PR / review flow established in PRs #1–#3.
- New items go in as GitHub issues with appropriate labels (`carried-over` / `feature` / `architecture` / `security` / `billing` / `tooling` / `ci`), then surface here.
- Closed issues stay in the GitHub history; this doc is curated, not auto-generated.
