<?php

declare(strict_types=1);

namespace Padosoft\Rebel\Channel\Telegram\Gateway;

use Illuminate\Http\Client\Factory as HttpFactory;
use Padosoft\Rebel\Channel\Telegram\Contracts\TelegramGateway;
use RuntimeException;

/**
 * Real {@see TelegramGateway} backed by the Telegram Bot API over HTTP.
 *
 * It POSTs to https://api.telegram.org/bot<token>/sendMessage. The bot token lives
 * only in this object (and the request URL it builds); it is NEVER logged or returned
 * in any error surfaced to the caller.
 */
final class HttpTelegramGateway implements TelegramGateway
{
    private const BASE_URL = 'https://api.telegram.org';

    public function __construct(
        private readonly HttpFactory $http,
        private readonly string $botToken,
        private readonly int $timeout = 10,
    ) {}

    public function sendMessage(string $chatId, string $message, ?string $parseMode = null): array
    {
        /** @var array<string, scalar> $payload */
        $payload = [
            'chat_id' => $chatId,
            'text' => $message,
        ];

        if ($parseMode !== null && $parseMode !== '') {
            $payload['parse_mode'] = $parseMode;
        }

        $response = $this->http
            ->timeout($this->timeout)
            ->asJson()
            ->acceptJson()
            ->post(self::endpoint($this->botToken), $payload);

        // A non-2xx (or a Telegram { ok: false }) is a failure. We deliberately do NOT
        // include the response body or token in the exception message: the delivery
        // channel converts any throw into a generic `provider_error`.
        if (! $response->successful()) {
            throw new RuntimeException('Telegram API request failed with status '.$response->status().'.');
        }

        /** @var array<array-key, mixed> $body */
        $body = $response->json();

        if (($body['ok'] ?? false) !== true) {
            throw new RuntimeException('Telegram API returned ok=false.');
        }

        $result = $body['result'] ?? null;
        if (! is_array($result)) {
            throw new RuntimeException('Telegram API returned an unexpected payload.');
        }

        return [
            'message_id' => $this->toInt($result['message_id'] ?? null),
            'chat_id' => $chatId,
        ];
    }

    /**
     * Build the per-token endpoint. Kept private so the token never escapes this class.
     */
    private static function endpoint(string $token): string
    {
        return self::BASE_URL.'/bot'.$token.'/sendMessage';
    }

    private function toInt(mixed $value): int
    {
        // Telegram returns message_id as a JSON number; be defensive against a string.
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && $value !== '' && ctype_digit(ltrim($value, '-'))) {
            return (int) $value;
        }

        throw new RuntimeException('Telegram API returned a non-integer message_id.');
    }
}
