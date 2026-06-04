<?php

declare(strict_types=1);

namespace Padosoft\Rebel\Channel\Telegram\Contracts;

/**
 * Thin seam over the Telegram Bot API so the delivery channel stays fully
 * unit-testable offline. The real implementation talks HTTP to
 * https://api.telegram.org; a fake ships for tests, and the live test-suite uses
 * the real one against the actual Bot API.
 */
interface TelegramGateway
{
    /**
     * Send a text message to a chat. Returns the Telegram message id and the chat id
     * it was delivered to.
     *
     * Implementations MUST throw on any transport/API error (the delivery channel
     * wraps this in a try/catch and turns it into a clean `provider_error`).
     *
     * @param  string  $chatId  The Telegram chat_id (a user/group/channel identifier).
     * @param  string|null  $parseMode  Telegram parse mode ('MarkdownV2' | 'HTML' | null for plain text).
     * @return array{message_id: int, chat_id: string}
     */
    public function sendMessage(string $chatId, string $message, ?string $parseMode = null): array;
}
