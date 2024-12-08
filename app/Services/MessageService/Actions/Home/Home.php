<?php

namespace App\Services\MessageService\Actions\Home;

use App\Services\MessageService\Actions\BaseAction;
use Illuminate\Support\Facades\Storage;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\FileUpload\InputFile;
use Telegram\Bot\Keyboard\Keyboard;

class Home extends BaseAction
{
    /**
     * @return $this
     * @throws TelegramSDKException
     */
    public function start(): static
    {
        $chat_id = $this->messageService->chatId;
        $photo = InputFile::create(Storage::disk('public')->path('avatar-black.png'));
        $caption = trans('telegram.message.start');
        $reply_markup = new Keyboard(
            [
                'inline_keyboard' => [
                    [
                        ['text' => trans('telegram.button.individuals'), 'callback_data' => 'individuals'],
                        ['text' => trans('telegram.button.legal_entities'), 'callback_data' => 'legal_entities'],
                    ]
                ],
            ]
        );
        $this->messageService->order->steps()->delete();
        $this->messageService->setMainKeyboard();
        $this->messageService->telegram->sendPhoto(compact('chat_id', 'photo', 'caption', 'reply_markup'));

        return $this;
    }

    public function individuals(): static
    {
        $chat_id = $this->messageService->chatId;
        $text = trans('telegram.message.services');
        $this->messageService->order->syncSteps(__FUNCTION__, trans('telegram.order.start'), $this->messageService->getSelectedOptionName());

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.currency_exchange'), 'callback_data' => 'currency_exchange'],
                ['text' =>  trans('telegram.button.crypto_exchange'), 'callback_data' => 'crypto_exchange'],
            ], [
                ['text' =>  trans('telegram.button.international_transfers'), 'callback_data' => 'international_transfers'],
            ], [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->messageService->getBackKey(__FUNCTION__)],
            ]
        ]]);
        $this->messageService->telegram->sendMessage(compact('chat_id', 'text', 'reply_markup'));

        return $this;
    }
}
