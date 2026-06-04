<?php

declare(strict_types=1);

use Padosoft\Rebel\Channel\Telegram\Contracts\TelegramGateway;
use Padosoft\Rebel\Channel\Telegram\Delivery\TelegramDeliveryChannel;
use Padosoft\Rebel\Channel\Telegram\RebelTelegramServiceProvider;
use Padosoft\Rebel\Channel\Telegram\Testing\FakeTelegramGateway;
use Padosoft\Rebel\Channels\Contracts\MessageDeliveryChannel;
use Padosoft\Rebel\Channels\Enums\Channel;

it('registers the delivery channel when a bot token is configured', function (): void {
    // The base TestCase configures a dummy token + fake gateway.
    expect(app()->bound(TelegramDeliveryChannel::class))->toBeTrue();

    $channel = app(TelegramDeliveryChannel::class);

    expect($channel)->toBeInstanceOf(TelegramDeliveryChannel::class)
        ->and($channel->key())->toBe('telegram')
        ->and($channel->supports(Channel::Telegram))->toBeTrue();
});

it('binds the channel under the MessageDeliveryChannel contract', function (): void {
    expect(app()->bound(MessageDeliveryChannel::class))->toBeTrue()
        ->and(app(MessageDeliveryChannel::class))->toBeInstanceOf(TelegramDeliveryChannel::class);
});

it('tags the channel under the shared delivery tag', function (): void {
    $tagged = iterator_to_array(app()->tagged(RebelTelegramServiceProvider::DELIVERY_TAG));

    expect($tagged)->toHaveCount(1)
        ->and($tagged[0])->toBeInstanceOf(TelegramDeliveryChannel::class);
});

it('uses the fake gateway bound in tests rather than building a real one', function (): void {
    // The base TestCase binds a FakeTelegramGateway; the provider must not overwrite it.
    expect(app(TelegramGateway::class))
        ->toBeInstanceOf(FakeTelegramGateway::class);
});
