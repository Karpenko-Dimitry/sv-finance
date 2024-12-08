<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SetWebhookRequest;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

class TelegramController extends Controller
{
    protected Api $telegram;

    public function __construct(Api $telegram)
    {
        $this->telegram = $telegram;
    }

    /**
     * @throws TelegramSDKException
     */
    public function webhook()
    {
        log_debug('test0000');

        resolve('message')->receive();
    }

    /**
     * @throws TelegramSDKException
     */
    public function setWebhook(SetWebhookRequest $request)
    {
        if ($request->get('pass') === config('telegram.bots.mybot.webhook_secret')) {
            return $this->telegram->setWebhook([
                'url' => 'https://karp.s-host.net/api/telegram/webhook',
                'secret_token' => config('telegram.bots.mybot.webhook_secret'),
            ]);
        } else {
            abort(404);
        }
    }
}
