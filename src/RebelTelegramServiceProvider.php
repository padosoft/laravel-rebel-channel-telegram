<?php

declare(strict_types=1);

namespace Padosoft\Rebel\Channel\Telegram;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Client\Factory as HttpFactory;
use Padosoft\Rebel\Channel\Telegram\Contracts\TelegramGateway;
use Padosoft\Rebel\Channel\Telegram\Delivery\TelegramDeliveryChannel;
use Padosoft\Rebel\Channel\Telegram\Gateway\HttpTelegramGateway;
use Padosoft\Rebel\Channels\Routing\DeliveryChannelRegistry;
use Padosoft\Rebel\Core\Contracts\AuditLogger;
use Padosoft\Rebel\Core\Contracts\KeyedHasher;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

/**
 * Registers the Telegram delivery channel into the Rebel Channels container (when a
 * bot token is configured) and binds the Telegram gateway.
 *
 * The token is read lazily: the package installs cleanly with no Telegram config, and
 * the channel simply does not register until you set TELEGRAM_BOT_TOKEN.
 *
 * The channel registers itself into the shared {@see DeliveryChannelRegistry} (provided
 * by laravel-rebel-channels) keyed by `telegram`, so it coexists with every other
 * delivery channel (Discord, Twilio, ...) and the admin panel can enumerate them all.
 */
final class RebelTelegramServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-rebel-channel-telegram')
            ->hasConfigFile('rebel-channel-telegram');
    }

    public function packageBooted(): void
    {
        $config = $this->app->make(Repository::class);

        // No token → nothing Telegram-backed is wired (no unauthenticated gateway is
        // ever constructed in the container).
        if ($config->get('rebel-channel-telegram.register_provider', true) !== true) {
            return;
        }

        if ($this->botToken($config) === '') {
            return;
        }

        // Bind the real gateway only when not already bound (so a test can bind a fake first).
        if (! $this->app->bound(TelegramGateway::class)) {
            $this->app->singleton(TelegramGateway::class, function () use ($config): HttpTelegramGateway {
                return new HttpTelegramGateway(
                    $this->app->make(HttpFactory::class),
                    $this->botToken($config),
                    $this->timeout($config),
                );
            });
        }

        $this->app->singleton(TelegramDeliveryChannel::class, function () use ($config): TelegramDeliveryChannel {
            return new TelegramDeliveryChannel(
                $this->app->make(TelegramGateway::class),
                $this->app->make(AuditLogger::class),
                $this->app->make(KeyedHasher::class),
                $this->parseMode($config),
            );
        });

        // Register into the shared delivery registry (keyed 'telegram') so it coexists
        // with every other delivery channel instead of fighting over one contract binding.
        if (class_exists(DeliveryChannelRegistry::class) && $this->app->bound(DeliveryChannelRegistry::class)) {
            $this->app->make(DeliveryChannelRegistry::class)
                ->register($this->app->make(TelegramDeliveryChannel::class));
        }
    }

    private function botToken(Repository $config): string
    {
        return $this->stringConfig($config, 'bot_token');
    }

    private function parseMode(Repository $config): ?string
    {
        $value = $config->get('rebel-channel-telegram.parse_mode');

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function timeout(Repository $config): int
    {
        $value = $config->get('rebel-channel-telegram.timeout');

        return is_int($value) && $value > 0 ? $value : 10;
    }

    private function stringConfig(Repository $config, string $key): string
    {
        $value = $config->get("rebel-channel-telegram.{$key}");

        return is_string($value) ? $value : '';
    }
}
