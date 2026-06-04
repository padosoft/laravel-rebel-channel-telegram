<?php

declare(strict_types=1);

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Request;
use Padosoft\Rebel\Channel\Telegram\Gateway\HttpTelegramGateway;

it('posts the message to the Telegram sendMessage endpoint and parses the result', function (): void {
    $http = new HttpFactory;
    $http->fake([
        'api.telegram.org/*' => $http->response([
            'ok' => true,
            'result' => ['message_id' => 4242, 'chat' => ['id' => 12345]],
        ]),
    ]);

    $gateway = new HttpTelegramGateway($http, 'BOT-TOKEN-SECRET');

    $result = $gateway->sendMessage('12345', 'hello', 'HTML');

    expect($result['message_id'])->toBe(4242)
        ->and($result['chat_id'])->toBe('12345');

    $http->assertSent(function (Request $request): bool {
        return str_contains($request->url(), '/botBOT-TOKEN-SECRET/sendMessage')
            && $request['chat_id'] === '12345'
            && $request['text'] === 'hello'
            && $request['parse_mode'] === 'HTML';
    });
});

it('omits parse_mode when none is configured', function (): void {
    $http = new HttpFactory;
    $http->fake([
        'api.telegram.org/*' => $http->response(['ok' => true, 'result' => ['message_id' => 1]]),
    ]);

    (new HttpTelegramGateway($http, 'TOKEN'))->sendMessage('99', 'plain');

    $http->assertSent(fn (Request $request): bool => ! isset($request['parse_mode']));
});

it('throws on a non-2xx response (so the channel can fail closed)', function (): void {
    $http = new HttpFactory;
    $http->fake([
        'api.telegram.org/*' => $http->response(['ok' => false, 'description' => 'Unauthorized'], 401),
    ]);

    expect(fn () => (new HttpTelegramGateway($http, 'TOKEN'))->sendMessage('99', 'x'))
        ->toThrow(RuntimeException::class);
});

it('throws when Telegram returns ok=false on a 200', function (): void {
    $http = new HttpFactory;
    $http->fake([
        'api.telegram.org/*' => $http->response(['ok' => false, 'description' => 'chat not found'], 200),
    ]);

    expect(fn () => (new HttpTelegramGateway($http, 'TOKEN'))->sendMessage('99', 'x'))
        ->toThrow(RuntimeException::class);
});

it('never leaks the bot token in the thrown error message', function (): void {
    $http = new HttpFactory;
    $http->fake([
        'api.telegram.org/*' => $http->response(['ok' => false], 500),
    ]);

    try {
        (new HttpTelegramGateway($http, 'SUPER-SECRET-TOKEN'))->sendMessage('99', 'x');
        $this->fail('Expected the gateway to throw.');
    } catch (RuntimeException $e) {
        expect(str_contains($e->getMessage(), 'SUPER-SECRET-TOKEN'))->toBeFalse();
    }
});
