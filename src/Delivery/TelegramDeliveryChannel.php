<?php

declare(strict_types=1);

namespace Padosoft\Rebel\Channel\Telegram\Delivery;

use Padosoft\Rebel\Channel\Telegram\Contracts\TelegramGateway;
use Padosoft\Rebel\Channels\Contracts\MessageDeliveryChannel;
use Padosoft\Rebel\Channels\Enums\Channel;
use Padosoft\Rebel\Channels\Results\DeliveryResult;
use Padosoft\Rebel\Core\Audit\AuditEvent;
use Padosoft\Rebel\Core\Context\SecurityContext;
use Padosoft\Rebel\Core\Contracts\AuditLogger;
use Padosoft\Rebel\Core\Contracts\KeyedHasher;
use Padosoft\Rebel\Core\Identifiers\PhoneIdentifier;

/**
 * Telegram implementation of the Rebel Channels {@see MessageDeliveryChannel}.
 *
 * Telegram has no phone identifier: the recipient's {@see PhoneIdentifier} normalized
 * value is reused as the Telegram **chat_id** (the message destination). The send is
 * synchronous, so the send IS the receipt — there is no separate status webhook.
 *
 * It never throws out: any transport/API error becomes a generic `provider_error`
 * failure so the router can fall back to another channel, and the bot token is never
 * logged.
 *
 * Telemetry: every attempt records a Rebel audit event through the core
 * {@see AuditLogger} (`channel.delivery.sent` on success / `channel.delivery.failed`
 * on failure) so the panel's Channel Performance reflects Telegram delivery. The
 * chat_id is stored only as a keyed HMAC, never in clear.
 */
final class TelegramDeliveryChannel implements MessageDeliveryChannel
{
    public function __construct(
        private readonly TelegramGateway $gateway,
        private readonly AuditLogger $audit,
        private readonly KeyedHasher $hasher,
        private readonly ?string $parseMode = null,
    ) {}

    public function key(): string
    {
        return 'telegram';
    }

    public function supports(Channel $channel): bool
    {
        return $channel === Channel::Telegram;
    }

    public function send(PhoneIdentifier $phone, string $message, Channel $channel, SecurityContext $context): DeliveryResult
    {
        // The PhoneIdentifier's normalized value doubles as the Telegram chat_id.
        $chatId = $phone->normalized();

        try {
            $result = $this->gateway->sendMessage($chatId, $message, $this->parseMode);
        } catch (\Throwable $e) {
            $this->record($chatId, $channel, 'failed', 'provider_error');

            return DeliveryResult::fail($channel, 'provider_error', 'telegram');
        }

        $this->record($chatId, $channel, 'sent', null, (string) $result['message_id']);

        return DeliveryResult::sent($channel, 'telegram', (string) $result['message_id']);
    }

    /**
     * Record a delivery audit event. The chat_id is hashed (keyed HMAC) before it is
     * stored — never in clear. The bot token is never part of the event.
     */
    private function record(string $chatId, Channel $channel, string $status, ?string $errorCode, ?string $reference = null): void
    {
        $hash = $this->hasher->hash($chatId);

        $this->audit->record(new AuditEvent(
            type: $status === 'sent' ? 'channel.delivery.sent' : 'channel.delivery.failed',
            identifierHmac: $hash->hash,
            keyVersion: $hash->keyVersion,
            channel: 'telegram',
            provider: 'telegram',
            metadata: [
                'message_status' => $status,
                'error_code' => $errorCode,
                'message_id' => $reference,
            ],
        ));
    }
}
