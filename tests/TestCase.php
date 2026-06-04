<?php

declare(strict_types=1);

namespace Padosoft\Rebel\Channel\Telegram\Tests;

use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as Orchestra;
use Padosoft\Rebel\Channel\Telegram\Contracts\TelegramGateway;
use Padosoft\Rebel\Channel\Telegram\RebelTelegramServiceProvider;
use Padosoft\Rebel\Channel\Telegram\Testing\FakeTelegramGateway;
use Padosoft\Rebel\Channels\RebelChannelsServiceProvider;
use Padosoft\Rebel\Core\RebelCoreServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * @param  Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            RebelCoreServiceProvider::class,
            RebelChannelsServiceProvider::class,
            RebelTelegramServiceProvider::class,
        ];
    }

    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('rebel-core.peppers', [1 => 'test-pepper']);
        $app['config']->set('rebel-core.pepper_current', 1);
        $app['config']->set('cache.default', 'array');

        // A dummy bot token so the channel registers, plus a fake gateway so no real
        // Telegram call is made in the offline suite.
        $app['config']->set('rebel-channel-telegram.bot_token', '123456:TEST');

        $app->instance(TelegramGateway::class, new FakeTelegramGateway);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../vendor/padosoft/laravel-rebel-core/database/migrations');
    }
}
