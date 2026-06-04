<?php

declare(strict_types=1);

namespace Padosoft\Rebel\Channel\Telegram\Tests\Feature;

use Illuminate\Foundation\Application;
use Padosoft\Rebel\Channel\Telegram\Delivery\TelegramDeliveryChannel;
use Padosoft\Rebel\Channel\Telegram\Tests\TestCase;

/**
 * Even with a valid token, `register_provider = false` must keep the channel out of
 * the container (the host app opts out explicitly).
 */
final class RegistrationDisabledTest extends TestCase
{
    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('rebel-channel-telegram.register_provider', false);
    }

    public function test_it_does_not_register_when_register_provider_is_false(): void
    {
        $this->assertFalse($this->app->bound(TelegramDeliveryChannel::class));
    }
}
