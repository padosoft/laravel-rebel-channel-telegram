<?php

declare(strict_types=1);

namespace Padosoft\Rebel\Channel\Telegram;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

/**
 * Service provider for the laravel-rebel-channel-telegram package (initial skeleton).
 * The full implementation will arrive in its roadmap macro-task.
 */
final class RebelTelegramServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package->name('laravel-rebel-channel-telegram');
    }
}
