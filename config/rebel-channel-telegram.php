<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Telegram bot token
    |--------------------------------------------------------------------------
    | The token issued by @BotFather when you create a bot (looks like
    | "123456789:AAExxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"). This is REQUIRED for the
    | delivery channel to register — with no token the package installs cleanly but
    | stays dormant, and no unauthenticated gateway is ever constructed.
    |
    | Keep it secret: it is never logged, and is only ever embedded in the outbound
    | request URL to api.telegram.org.
    */
    'bot_token' => env('TELEGRAM_BOT_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Default chat id (optional)
    |--------------------------------------------------------------------------
    | An optional fallback chat_id (e.g. a security-alerts group) you can use when a
    | recipient is not derived from a user's PhoneIdentifier. Telegram has no phone
    | concept: the recipient passed to send() is the chat_id (a user, group or channel
    | identifier). This value is purely informational for the host app — the channel
    | itself always sends to the chat_id it is given.
    */
    'default_chat_id' => env('TELEGRAM_DEFAULT_CHAT_ID'),

    /*
    |--------------------------------------------------------------------------
    | Registration
    |--------------------------------------------------------------------------
    | Whether to auto-register the Telegram delivery channel into the Rebel Channels
    | container on boot (only happens when a bot token is also present).
    */
    'register_provider' => env('REBEL_TELEGRAM_REGISTER', true),

    /*
    |--------------------------------------------------------------------------
    | Parse mode
    |--------------------------------------------------------------------------
    | Telegram message formatting: null (plain text), 'MarkdownV2' or 'HTML'. Plain
    | text is the safe default — Markdown/HTML require escaping special characters in
    | the message body, which is the caller's responsibility.
    */
    'parse_mode' => env('TELEGRAM_PARSE_MODE'),

    /*
    |--------------------------------------------------------------------------
    | HTTP timeout (seconds)
    |--------------------------------------------------------------------------
    | Maximum time to wait for the Telegram Bot API before treating the send as a
    | failure (which becomes a graceful `provider_error`, never an exception).
    */
    'timeout' => (int) env('TELEGRAM_TIMEOUT', 10),

];
