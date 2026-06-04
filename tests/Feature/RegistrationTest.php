<?php

declare(strict_types=1);

use Padosoft\Rebel\Channel\Telegram\Contracts\TelegramGateway;
use Padosoft\Rebel\Channel\Telegram\Delivery\TelegramDeliveryChannel;
use Padosoft\Rebel\Channel\Telegram\Testing\FakeTelegramGateway;
use Padosoft\Rebel\Channels\Enums\Channel;
use Padosoft\Rebel\Channels\Routing\DeliveryChannelRegistry;

it('registers the delivery channel when a bot token is configured', function (): void {
    // The base TestCase configures a dummy token + fake gateway.
    expect(app()->bound(TelegramDeliveryChannel::class))->toBeTrue();

    $channel = app(TelegramDeliveryChannel::class);

    expect($channel)->toBeInstanceOf(TelegramDeliveryChannel::class)
        ->and($channel->key())->toBe('telegram')
        ->and($channel->supports(Channel::Telegram))->toBeTrue();
});

it('registers the channel into the shared delivery registry keyed telegram', function (): void {
    $registry = app(DeliveryChannelRegistry::class);

    expect($registry->has('telegram'))->toBeTrue()
        ->and($registry->get('telegram'))->toBeInstanceOf(TelegramDeliveryChannel::class)
        ->and($registry->supporting(Channel::Telegram))
        ->toContain(app(TelegramDeliveryChannel::class));
});

it('uses the fake gateway bound in tests rather than building a real one', function (): void {
    // The base TestCase binds a FakeTelegramGateway; the provider must not overwrite it.
    expect(app(TelegramGateway::class))
        ->toBeInstanceOf(FakeTelegramGateway::class);
});
