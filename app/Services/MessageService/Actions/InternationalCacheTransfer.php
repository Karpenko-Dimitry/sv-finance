<?php

namespace App\Services\MessageService\Actions;

use App\Services\MessageService\AbstractAction;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Keyboard\Keyboard;

class InternationalCacheTransfer extends AbstractAction
{
    protected ?string $name = "international_cache_transfer";

    /**
     * @return $this
     * @throws TelegramSDKException
     */
    public function start(): static
    {
        $chat_id = $this->messageService->chatId;
        $message_id = $this->messageService->messageId;
        $text = trans('telegram.international_cache_transfer.message.direction');
        $this->messageService->order->syncSteps(
            $this->getActionKey(__FUNCTION__),
            trans('telegram.international_cache_transfer.order.service_type'),
            $this->messageService->getSelectedOptionName()
        );

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                [
                    'text' => trans('telegram.international_cache_transfer.button.from_rf'),
                    'callback_data' => (new InternationalCacheTransferFromRf())->getActionKey()
                ],
                [
                    'text' => trans('telegram.international_cache_transfer.button.to_rf'),
                    'callback_data' => (new InternationalCacheTransferToRf())->getActionKey()
                ],
            ], [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->messageService->getBackKey($this->getActionKey(__FUNCTION__))],
            ]
        ]]);

        $this->messageService->sendOrEdit($chat_id, $message_id, $reply_markup, $text);

        return $this;
    }
}
