<?php

declare(strict_types=1);

namespace Padosoft\Rebel\Channel\Telegram\Testing;

use Padosoft\Rebel\Channel\Telegram\Contracts\TelegramGateway;
use RuntimeException;

/**
 * Deterministic {@see TelegramGateway} for tests: records sent messages, returns an
 * incrementing message id, and can simulate an API outage.
 */
final class FakeTelegramGateway implements TelegramGateway
{
    /** @var list<array{chat_id: string, message: string, parse_mode: string|null}> */
    public array $sent = [];

    public function __construct(
        private readonly bool $healthy = true,
    ) {}

    public function sendMessage(string $chatId, string $message, ?string $parseMode = null): array
    {
        if (! $this->healthy) {
            throw new RuntimeException('telegram unavailable');
        }

        $this->sent[] = ['chat_id' => $chatId, 'message' => $message, 'parse_mode' => $parseMode];

        return ['message_id' => count($this->sent), 'chat_id' => $chatId];
    }
}
