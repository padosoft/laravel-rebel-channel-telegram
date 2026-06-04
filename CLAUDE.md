# CLAUDE.md — AI working guide for `padosoft/laravel-rebel-channel-telegram`

> Working on this package with an AI agent (Claude Code, Cursor, Copilot, Codex)? Read this first.
> It's the "batteries" that make vibe-coding here land on the first try. Plain Markdown — every
> tool can read it.

## What this package is
A **Telegram bot** delivery channel for Laravel Rebel Channels: it delivers OTP codes and security
alerts to a Telegram chat via the Telegram Bot API. It implements the Channels
`MessageDeliveryChannel` contract (`key()`, `supports(Channel)`, `send(...)`).

Part of the **Laravel Rebel** suite — an enterprise authentication control plane over Laravel
Fortify. The shared language (value objects, contracts, the audit trail) lives in
`padosoft/laravel-rebel-core`; this package builds on it. It implements the contracts defined in
`padosoft/laravel-rebel-channels`.

> **Telegram has no phone identifier.** The recipient's `PhoneIdentifier` normalized value is reused
> as the Telegram **chat_id** (a user / group / channel identifier). The send is synchronous, so the
> send IS the receipt — there is no separate delivery-status webhook.

## Non-negotiable conventions
- `declare(strict_types=1);` in every PHP file; `final` classes; constructor property promotion.
- **PHPStan level max** must stay green. Do NOT add `@phpstan-ignore`, baseline entries, or
  `assert()`/inline `@var` to silence errors — fix the root cause. Common recipes:
  - narrow `mixed` before casting: `is_scalar($x) ? (string) $x : null`;
  - `json_decode($s, true)` / `$response->json()` is `array<array-key, mixed>`;
  - the container's `make('request')` is already typed `Illuminate\Http\Request`.
- **Tests:** Pest, Testbench. Cover happy path, fail-closed (provider error), channel support,
  registration gating.
- **Style:** Pint (`composer pint`). **Docs/comments in English.**
- Package wiring uses `spatie/laravel-package-tools` (`configurePackage`).

## Security & telemetry rules (suite-wide)
- Never store PII in cleartext: identifiers (here the chat_id) are **keyed HMACs** (core
  `KeyedHasher`). **Never log the bot token** — it lives only inside `HttpTelegramGateway` and the
  request URL it builds, and is excluded from every error message.
- **Telemetry completeness:** record through the core `AuditLogger` on every send —
  `channel.delivery.sent` on success / `channel.delivery.failed` on failure, with
  `channel: 'telegram'`, `provider: 'telegram'`, `identifierHmac` = keyed-HMAC of the chat_id, and
  metadata `{ message_status, error_code, message_id }`. This fills the panel's Channel Performance.

## How to extend it
- **The Telegram gateway:** `Contracts\TelegramGateway` is the seam over the Bot API. Production
  calls go through `Gateway\HttpTelegramGateway` (real HTTP via `Illuminate\Http\Client\Factory` to
  `https://api.telegram.org/bot<token>/sendMessage`). Tests bind `Testing\FakeTelegramGateway`
  instead of hitting the network.
- **The delivery channel:** `Delivery\TelegramDeliveryChannel` implements the Channels
  `MessageDeliveryChannel` contract — extend it to support new Telegram features (e.g. silent
  notifications, reply markup). Any transport/API error becomes a clean `provider_error` so the
  router can fall back.
- **Registration:** `RebelTelegramServiceProvider::packageBooted()` registers the channel only when
  `register_provider` is true AND a bot token is present, binding the real gateway unless one is
  already bound (so a fake wins in tests). The channel is bound under the `MessageDeliveryChannel`
  contract and tagged `rebel-channels.delivery`.

## Definition of Done (per change)
1. Red→green with Pest; `composer phpstan` (max) + `composer pint -- --test` clean.
2. One feature branch, one PR to `main`. CI matrix **PHP 8.3/8.4/8.5 × Laravel 12/13** must be green.
3. Update `README.md` + `CHANGELOG.md`. Squash-merge.
4. **Release:** `git tag vX.Y.Z && git push origin vX.Y.Z` + `gh release create`. Stay in `0.1.x`
   (Composer `^0.1` excludes `0.2.0` and would break dependents).

## Skills
This repo ships invocable skills under `.claude/skills/` — at least `rebel-package-dev` (the dev
loop + PHPStan-max recipes). Invoke it before non-trivial work.

---

> **Operational rules (Italian):** see **`AGENTS.md`** for the full workflow contract (branching,
> Definition of Done, local loop + GitHub gates, guardrails, didactic READMEs, design-lock).
