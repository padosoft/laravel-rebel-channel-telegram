<?php

declare(strict_types=1);

namespace Padosoft\Rebel\Channel\Telegram\Tests\Feature;

use Illuminate\Foundation\Application;
use Padosoft\Rebel\Channel\Telegram\Contracts\TelegramGateway;
use Padosoft\Rebel\Channel\Telegram\Delivery\TelegramDeliveryChannel;
use Padosoft\Rebel\Channel\Telegram\Tests\TestCase;

/**
 * Boots the package WITHOUT a bot token to assert the channel stays dormant: it must
 * not register, and no gateway is constructed.
 */
final class NotConfiguredTest extends TestCase
{
    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        // Remove the token the parent set, and the fake gateway, so the provider's
        // configuration gate is exercised.
        $app['config']->set('rebel-channel-telegram.bot_token', null);
        $app->forgetInstance(TelegramGateway::class);
    }

    public function test_it_does_not_register_the_channel_without_a_bot_token(): void
    {
        $this->assertFalse($this->app->bound(TelegramDeliveryChannel::class));
    }
}
