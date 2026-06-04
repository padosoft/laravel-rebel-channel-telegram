<?php

declare(strict_types=1);

use Padosoft\Rebel\Channel\Telegram\Delivery\TelegramDeliveryChannel;
use Padosoft\Rebel\Channel\Telegram\Testing\FakeTelegramGateway;
use Padosoft\Rebel\Channels\Enums\Channel;
use Padosoft\Rebel\Core\Audit\AuditEvent;
use Padosoft\Rebel\Core\Context\SecurityContext;
use Padosoft\Rebel\Core\Contracts\AuditLogger;
use Padosoft\Rebel\Core\Contracts\KeyedHasher;
use Padosoft\Rebel\Core\Identifiers\PhoneIdentifier;

/**
 * In-memory AuditLogger so we can assert exactly what the channel records
 * without depending on the DB layer.
 */
function recordingAudit(): AuditLogger
{
    return new class implements AuditLogger
    {
        /** @var list<AuditEvent> */
        public array $events = [];

        public function record(AuditEvent $event): void
        {
            $this->events[] = $event;
        }
    };
}

function hasher(): KeyedHasher
{
    return app(KeyedHasher::class);
}

function ctx(): SecurityContext
{
    return new SecurityContext('req-1');
}

it('sends a message to the chat_id and returns a sent result', function (): void {
    $gateway = new FakeTelegramGateway;
    $channel = new TelegramDeliveryChannel($gateway, recordingAudit(), hasher());

    $result = $channel->send(PhoneIdentifier::from('+393331234567'), 'Your code is 123456', Channel::Telegram, ctx());

    expect($result->accepted())->toBeTrue()
        ->and($result->failed())->toBeFalse()
        ->and($result->provider)->toBe('telegram')
        ->and($result->reference)->toBe('1')
        ->and($gateway->sent)->toHaveCount(1)
        ->and($gateway->sent[0]['chat_id'])->toBe('+393331234567')
        ->and($gateway->sent[0]['message'])->toBe('Your code is 123456');
});

it('records channel.delivery.sent on success with the chat_id stored as an HMAC', function (): void {
    $audit = recordingAudit();
    $channel = new TelegramDeliveryChannel(new FakeTelegramGateway, $audit, hasher());

    $channel->send(PhoneIdentifier::from('+393331234567'), 'hello', Channel::Telegram, ctx());

    expect($audit->events)->toHaveCount(1);
    $event = $audit->events[0];

    expect($event->type)->toBe('channel.delivery.sent')
        ->and($event->channel)->toBe('telegram')
        ->and($event->provider)->toBe('telegram')
        ->and($event->keyVersion)->toBe(1)
        ->and($event->identifierHmac)->not->toBeNull()
        ->and($event->identifierHmac)->not->toBe('+393331234567')
        ->and(str_contains((string) $event->identifierHmac, '393331234567'))->toBeFalse()
        ->and($event->metadata['message_status'])->toBe('sent')
        ->and($event->metadata['error_code'])->toBeNull()
        ->and($event->metadata['message_id'])->toBe('1');
});

it('returns a provider_error (not an exception) and records channel.delivery.failed when Telegram is down', function (): void {
    $audit = recordingAudit();
    $channel = new TelegramDeliveryChannel(new FakeTelegramGateway(healthy: false), $audit, hasher());

    $result = $channel->send(PhoneIdentifier::from('+393331234567'), 'hello', Channel::Telegram, ctx());

    expect($result->failed())->toBeTrue()
        ->and($result->accepted())->toBeFalse()
        ->and($result->reason)->toBe('provider_error')
        ->and($result->provider)->toBe('telegram');

    expect($audit->events)->toHaveCount(1)
        ->and($audit->events[0]->type)->toBe('channel.delivery.failed')
        ->and($audit->events[0]->channel)->toBe('telegram')
        ->and($audit->events[0]->metadata['message_status'])->toBe('failed')
        ->and($audit->events[0]->metadata['error_code'])->toBe('provider_error');
});

it('supports only the Telegram channel', function (): void {
    $channel = new TelegramDeliveryChannel(new FakeTelegramGateway, recordingAudit(), hasher());

    expect($channel->key())->toBe('telegram')
        ->and($channel->supports(Channel::Telegram))->toBeTrue()
        ->and($channel->supports(Channel::Sms))->toBeFalse()
        ->and($channel->supports(Channel::WhatsApp))->toBeFalse()
        ->and($channel->supports(Channel::Discord))->toBeFalse();
});

it('forwards the configured parse mode to the gateway', function (): void {
    $gateway = new FakeTelegramGateway;
    $channel = new TelegramDeliveryChannel($gateway, recordingAudit(), hasher(), 'MarkdownV2');

    $channel->send(PhoneIdentifier::from('+393331234567'), '*bold*', Channel::Telegram, ctx());

    expect($gateway->sent[0]['parse_mode'])->toBe('MarkdownV2');
});
