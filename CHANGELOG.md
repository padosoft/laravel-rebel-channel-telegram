# Changelog

All notable changes to `padosoft/laravel-rebel-channel-telegram` are documented here.
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and
[Semantic Versioning](https://semver.org/).

## [Unreleased]

## [0.1.0] - 2026-06-04

### Added
- **`TelegramDeliveryChannel`**: a Rebel Channels `MessageDeliveryChannel` (`key()='telegram'`,
  `supports(Channel::Telegram)`) that delivers OTP codes and security alerts to a Telegram chat.
  The recipient's `PhoneIdentifier` normalized value is reused as the Telegram `chat_id`. Any
  transport/API error becomes a clean `provider_error` so the router can fall back.
- **Gateway seam** (`TelegramGateway` + `HttpTelegramGateway`) over the Telegram Bot API
  (`https://api.telegram.org/bot<token>/sendMessage`, real HTTP via
  `Illuminate\Http\Client\Factory`), with a `FakeTelegramGateway` for offline tests. The bot
  token is never logged or surfaced in any error.
- **Telemetry:** every send records a Rebel audit event through the core `AuditLogger` —
  `channel.delivery.sent` on success / `channel.delivery.failed` on failure, with
  `channel: 'telegram'`, `provider: 'telegram'`, the chat_id stored only as a keyed HMAC, and
  metadata `{ message_status, error_code, message_id }` — so the panel's Channel Performance
  reflects Telegram delivery. The synchronous send IS the receipt (no separate webhook).
- **Auto-registration** into the Channels container when a bot token is present and
  `register_provider` is true (no unauthenticated gateway is ever constructed otherwise). The
  channel is bound under the `MessageDeliveryChannel` contract and tagged `rebel-channels.delivery`.
- **Live test suite** (`tests/Live`, opt-in via `REBEL_TELEGRAM_LIVE=1` + `TELEGRAM_BOT_TOKEN` +
  `TELEGRAM_TEST_CHAT_ID`) that hits the real Bot API; self-skips when credentials are absent.
- Config file (`bot_token`, `default_chat_id`, `register_provider`, `parse_mode`, `timeout`),
  `.env.example` with the BotFather + chat_id steps, CI matrix (PHP 8.3/8.4/8.5 × Laravel 12/13),
  Pest suite, PHPStan level max, Pint.

[Unreleased]: https://github.com/padosoft/laravel-rebel-channel-telegram/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/padosoft/laravel-rebel-channel-telegram/releases/tag/v0.1.0
