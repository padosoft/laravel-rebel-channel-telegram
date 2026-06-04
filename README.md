# Laravel Rebel — Telegram Channel

> **Deliver OTP codes and security alerts straight to Telegram, the Rebel way.** This package plugs the [Telegram Bot API](https://core.telegram.org/bots/api) into [`laravel-rebel-channels`](https://github.com/padosoft/laravel-rebel-channels) as a `MessageDeliveryChannel` — so a Telegram chat becomes a first-class, **free**, self-hosted delivery target for verification codes and alerts, *plus* Rebel's HMAC'd audit trail and graceful fallback on top. Part of the `padosoft/laravel-rebel-*` suite.

<p align="center">
  <img src="resources/screenshoots/Laravel-Rebel-banner.png" alt="Laravel Rebel" width="100%">
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-12%20%7C%2013-FF2D20?style=flat-square&logo=laravel&logoColor=white" alt="Laravel 12|13">
  <img src="https://img.shields.io/badge/PHP-8.3%20%7C%208.4%20%7C%208.5-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP 8.3+">
  <img src="https://img.shields.io/badge/PHPStan-max-2A6FDB?style=flat-square" alt="PHPStan max">
  <img src="https://img.shields.io/badge/tests-Pest%204-22C55E?style=flat-square" alt="Pest 4">
  <img src="https://img.shields.io/badge/Telegram-Bot%20API-26A5E4?style=flat-square&logo=telegram&logoColor=white" alt="Telegram Bot API">
  <img src="https://img.shields.io/badge/license-MIT-blue?style=flat-square" alt="MIT">
</p>

---

## Table of contents

- [What it is](#what-it-is)
- [Quick glossary](#quick-glossary)
- [Why this package](#why-this-package)
- [Rebel + Telegram vs the alternatives](#rebel--telegram-vs-the-alternatives)
- [Telegram bot setup (step by step)](#telegram-bot-setup-step-by-step)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Telemetry (audit events)](#telemetry-audit-events)
- [Live tests against the real API](#live-tests-against-the-real-api)
- [`.env.example`](#envexample)
- [Security notes](#security-notes)
- [Testing & License](#testing--license)

---

## What it is

A thin, well-tested **Telegram bot** delivery channel for Rebel Channels. It implements the Channels
`MessageDeliveryChannel` contract, so once registered it can deliver any message — an OTP code, a
"new login from a new device" alert, a step-up prompt — to a Telegram chat.

A small **gateway seam** (`TelegramGateway`) wraps the Telegram Bot API over plain HTTP, so the whole
thing is unit-testable offline and has a real **live** test-suite for the actual API.

> **No phone number on Telegram.** Telegram identifies a destination by **chat_id** (a user, group or
> channel), not a phone. This package reuses the recipient's `PhoneIdentifier` normalized value as the
> chat_id — so the same Channels API works unchanged. See [Telegram bot setup](#telegram-bot-setup-step-by-step)
> for how to get a chat_id.

Depends on [`padosoft/laravel-rebel-core`](https://github.com/padosoft/laravel-rebel-core)
and [`padosoft/laravel-rebel-channels`](https://github.com/padosoft/laravel-rebel-channels).

---

## Quick glossary

| Term | In plain words |
|---|---|
| **Bot** | A Telegram account driven by your code, created via [@BotFather](https://t.me/BotFather). |
| **Bot token** | The secret that authenticates your bot (e.g. `123456789:AAE...`). Keep it out of logs. |
| **chat_id** | Where a message goes: a user, a group (negative id) or a channel (`@name` or id). The "recipient". |
| **parse_mode** | Optional message formatting: `MarkdownV2`, `HTML`, or plain text (default). |
| **Delivery channel** | A Rebel `MessageDeliveryChannel`: it `send()`s a message and reports success/failure. |

---

## Why this package

| ★ | What | In short |
|---|---|---|
| ★★★ | **OTP + alerts over Telegram** | Deliver verification codes and security alerts to any Telegram chat via a bot. |
| ★★★ | **Free + self-hosted** | No per-message cost, no third-party SaaS — your bot, your token, the public Bot API. |
| ★★★ | **Rebel guarantees for free** | Inherits the Channels routing/fallback and a full HMAC'd audit trail. |
| ★★ | **Never throws out** | Any transport/API error becomes a clean `provider_error`, so the router can fall back. |
| ★★ | **Offline-testable** | A gateway seam + fake means your tests don't hit Telegram; a separate live suite does. |
| ★★ | **Safe by default** | No bot token → nothing registers, and no unauthenticated gateway is ever built. |
| ★ | **Token never leaks** | The bot token lives only inside the gateway and is excluded from every error message. |

---

## Rebel + Telegram vs the alternatives

Delivering an OTP / security alert to Telegram, four ways:

| Capability | **Rebel + this package** | Shopify | `telegram-bot/api` SDK (direct) | Raw `curl` to the Bot API |
|---|:---:|:---:|:---:|:---:|
| Self-hosted Telegram OTP / alert channel | ✅ | ❌ | ➖ (you wire it yourself) | ➖ (you wire it yourself) |
| Free (no per-message SaaS cost) | ✅ | ❌ | ✅ | ✅ |
| Implements a unified delivery contract | ✅ | ❌ | ❌ | ❌ |
| **Provider fallback** to another channel | ✅ | ❌ | ❌ | ❌ |
| Unified audit trail (chat_id HMAC'd) | ✅ | ❌ | ❌ | ❌ |
| Telemetry into a Channel-Performance panel | ✅ | ➖ | ❌ | ❌ |
| Graceful failure → router fallback | ✅ | ❌ | ❌ | ❌ |
| Bot token kept out of logs by design | ✅ | ➖ | ❌ | ❌ |

> Legend: ✅ built-in · ➖ partial / hosted-only / DIY · ❌ not available.
> **Shopify** is a closed, hosted commerce platform: it sends its own customer OTPs over SMS/email and
> gives you **no** self-hosted Telegram channel, no way to deliver your app's alerts to a Telegram chat,
> no provider fallback, and no developer-facing audit of delivery — a black box, not a delivery library.
> The raw SDK / `curl` options can talk to Telegram, but you build the contract, fallback, audit and
> token-hygiene yourself — which is exactly what this package gives you for free.

---

## Telegram bot setup (step by step)

1. **Create a bot.** Open Telegram, start a chat with [@BotFather](https://t.me/BotFather), send
   `/newbot`, pick a display name and a username ending in `bot`. BotFather replies with your
   **bot token** (e.g. `123456789:AAExxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx`). Keep it secret.
2. **Get a chat_id.** Telegram won't let a bot message a user who hasn't contacted it first.
   - **1:1 chat:** have the user send `/start` to your bot, then call
     `https://api.telegram.org/bot<token>/getUpdates` and read `result[].message.chat.id`.
   - **Group:** add the bot to the group, post any message, then read the (negative) `chat.id`
     from `getUpdates`.
   - **Channel:** add the bot as an admin and use `@channelusername` (or the numeric id).
3. Put the token in your `.env` (see below). Done — the channel auto-registers.

> **Tip:** a dedicated "security alerts" group with your bot as a member is a great place to fan out
> alerts; set its id as `TELEGRAM_DEFAULT_CHAT_ID` for your host app to reuse.

---

## Installation

```bash
composer require padosoft/laravel-rebel-channel-telegram
php artisan vendor:publish --tag="rebel-channel-telegram-config"
```

Add your bot token to `.env`:

```dotenv
TELEGRAM_BOT_TOKEN=123456789:AAExxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

That's it — the delivery channel registers itself under the key `telegram` (bound to the
`MessageDeliveryChannel` contract and tagged `rebel-channels.delivery`).

---

## Configuration

File `config/rebel-channel-telegram.php`:

| Key | Default | What it does |
|---|---|---|
| `bot_token` | `env(TELEGRAM_BOT_TOKEN)` | The @BotFather token. Required for the channel to register. |
| `default_chat_id` | `env(TELEGRAM_DEFAULT_CHAT_ID)` | Optional fallback chat_id (e.g. an alerts group) for the host app. |
| `register_provider` | `true` | Auto-register the channel (only when a bot token is also present). |
| `parse_mode` | `null` | Telegram formatting: `null` (plain), `MarkdownV2` or `HTML`. |
| `timeout` | `10` | HTTP timeout (seconds) for the Bot API before a send fails gracefully. |

---

## Usage

The channel is resolvable from the container under the `MessageDeliveryChannel` contract (or the
`rebel-channels.delivery` tag, so multiple delivery channels can coexist):

```php
use Padosoft\Rebel\Channels\Contracts\MessageDeliveryChannel;
use Padosoft\Rebel\Channels\Enums\Channel;
use Padosoft\Rebel\Core\Context\SecurityContext;
use Padosoft\Rebel\Core\Identifiers\PhoneIdentifier;

$telegram = app(MessageDeliveryChannel::class);

// The "recipient" is the Telegram chat_id, carried by a PhoneIdentifier.
$chat = PhoneIdentifier::from('123456789');           // a user/group/channel id

$result = $telegram->send(
    $chat,
    "Your login code is 123456. It expires in 5 minutes.",
    Channel::Telegram,
    new SecurityContext('req-1'),                      // or SecurityContext::fromRequest($request, $hasher)
);

if ($result->accepted()) {
    // delivered — $result->reference is the Telegram message_id
}
```

To resolve **all** registered delivery channels (e.g. to pick by `supports()`):

```php
use Padosoft\Rebel\Channel\Telegram\RebelTelegramServiceProvider;

foreach (app()->tagged(RebelTelegramServiceProvider::DELIVERY_TAG) as $channel) {
    if ($channel->supports(Channel::Telegram)) {
        $channel->send($chat, $message, Channel::Telegram, $context);
    }
}
```

> The send is **synchronous**: when `send()` returns `accepted()`, Telegram has accepted the message —
> the send *is* the receipt, so there is no separate status webhook to wire.

---

## Telemetry (audit events)

Every send records exactly one Rebel audit event through the core `AuditLogger`, so the admin panel's
**Channel Performance** reflects real Telegram delivery. The chat_id is stored **only as a keyed HMAC**,
never in clear, and the bot token never appears anywhere.

| Outcome | Audit `event_type` |
|---|---|
| Message accepted by Telegram | `channel.delivery.sent` |
| Transport / API error (graceful failure) | `channel.delivery.failed` |

Each event carries `channel: 'telegram'`, `provider: 'telegram'`, the HMAC'd chat_id, and a `metadata`
object:

```json
{
  "message_status": "sent",
  "error_code": null,
  "message_id": "4242"
}
```

On failure, `message_status` is `"failed"`, `error_code` is `"provider_error"`, and `message_id` is
`null`.

---

## Live tests against the real API

The offline suite uses a fake gateway. To exercise the **real** Telegram Bot API (`tests/Live`), opt in
explicitly — it **sends a real message**:

```bash
# .env (or shell env)
REBEL_TELEGRAM_LIVE=1
TELEGRAM_BOT_TOKEN=123456789:AAE...
TELEGRAM_TEST_CHAT_ID=123456789     # a chat that has /start-ed your bot

vendor/bin/pest --group=live
```

Without `REBEL_TELEGRAM_LIVE=1` or with any value missing, the live tests **self-skip**, so
`composer test` and external PRs never trigger a send. In CI, supply the values as **secrets** and set
`REBEL_TELEGRAM_LIVE=1` on a dedicated job.

---

## `.env.example`

```dotenv
TELEGRAM_BOT_TOKEN=123456789:AAExxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TELEGRAM_DEFAULT_CHAT_ID=
REBEL_TELEGRAM_REGISTER=true
TELEGRAM_PARSE_MODE=
TELEGRAM_TIMEOUT=10

# Live tests (opt-in: SENDS A REAL MESSAGE)
REBEL_TELEGRAM_LIVE=0
TELEGRAM_TEST_CHAT_ID=
```

---

## Security notes

- **No unauthenticated gateway**: the Telegram gateway is only constructed when a bot token is present.
- **Token never logged**: the bot token lives only inside `HttpTelegramGateway` (and the request URL it
  builds) and is excluded from every error message surfaced to your app.
- **No exception leakage**: transport/API errors are caught and returned as a generic `provider_error`,
  so the router can fall back to another channel.
- **chat_id is HMAC'd**: the recipient is stored in the audit trail only as a keyed HMAC, never in clear.
- **Plain text by default**: `MarkdownV2` / `HTML` parse modes require escaping special characters in the
  message body — that escaping is the caller's responsibility.

---

## 🔋 Vibe coding with batteries included

This package ships **AI batteries** — so you (and your AI agent) can extend it correctly on the
first try:

- **`CLAUDE.md`** — a concise AI working guide (purpose, conventions, architecture, how to extend,
  Definition of Done). Plain Markdown, so Claude Code, Cursor, Copilot and Codex all read it.
- **`AGENTS.md`** — the agent/workflow contract (branch → PR → CI → tag/release, the gates).
- **`.claude/skills/`** — invocable skills (at least `rebel-package-dev`) encoding the suite's
  TDD loop, the **PHPStan-level-max** recipes, the security/telemetry rules, and the release
  discipline.

Open the repo in your AI editor and just start — the rules, guardrails and extension recipes come
with it. PRs that follow the shipped `CLAUDE.md` pass CI (PHPStan max + Pest + Pint) and review the
first time around.

## Testing & License

```bash
composer test      # Pest (delivery channel + gateway + registration; live suite self-skips)
composer phpstan   # static analysis, level max
composer pint      # code style
```

**License:** MIT — see [LICENSE](LICENSE). Part of the [`padosoft/laravel-rebel`](https://github.com/padosoft) suite.
