<?php

declare(strict_types=1);

use Illuminate\Http\Client\Factory as HttpFactory;
use Padosoft\Rebel\Channel\Telegram\Gateway\HttpTelegramGateway;

/**
 * LIVE tests: they hit the real Telegram Bot API and SEND A REAL MESSAGE.
 *
 * They run ONLY when you explicitly opt in with REBEL_TELEGRAM_LIVE=1 AND both
 * TELEGRAM_BOT_TOKEN and TELEGRAM_TEST_CHAT_ID are present — otherwise they self-skip,
 * so the offline suite and external PRs never trigger a send. In CI, provide the
 * values as secrets and set REBEL_TELEGRAM_LIVE=1.
 */
function liveEnv(string $key): string
{
    $value = getenv($key);

    return is_string($value) ? $value : '';
}

beforeEach(function (): void {
    if (liveEnv('REBEL_TELEGRAM_LIVE') !== '1') {
        test()->markTestSkipped('Live Telegram tests are opt-in (set REBEL_TELEGRAM_LIVE=1).');
    }

    foreach (['TELEGRAM_BOT_TOKEN', 'TELEGRAM_TEST_CHAT_ID'] as $key) {
        if (liveEnv($key) === '') {
            test()->markTestSkipped("Live Telegram credentials absent ({$key}).");
        }
    }
});

it('sends a real message via the Telegram Bot API', function (): void {
    $gateway = new HttpTelegramGateway(new HttpFactory, liveEnv('TELEGRAM_BOT_TOKEN'));

    $result = $gateway->sendMessage(
        liveEnv('TELEGRAM_TEST_CHAT_ID'),
        'Laravel Rebel — live test '.date('c'),
    );

    expect($result['message_id'])->toBeGreaterThan(0)
        ->and($result['chat_id'])->toBe(liveEnv('TELEGRAM_TEST_CHAT_ID'));
})->group('live');
