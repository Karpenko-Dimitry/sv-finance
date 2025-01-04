<?php

namespace App\Services\MessageService\Actions;

use App\Services\MessageService\AbstractAction;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Keyboard\Keyboard;

class InternationalTransfer extends AbstractAction
{
    protected ?string $name = "int_transfer";

    /**
     * @return $this
     * @throws TelegramSDKException
     */
    public function start(): static
    {
        $chat_id = $this->messageService->chatId;
        $message_id = $this->messageService->messageId;
        $this->messageService->order->syncSteps(
            $this->getActionKey(__FUNCTION__),
            trans('telegram.international_transfer.order.services'),
            $this->messageService->getSelectedOptionName()
        );
        $text = $this->messageService->order->getStepsFormattedData() . "\n\n";
        $text .= trans('telegram.international_transfer.message.service_type');

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                [
                    'text' => trans('telegram.international_transfer.button.cache_transfer'),
                    'callback_data' => (new InternationalCacheTransfer())->getActionKey()
                ],
            ], [
                [
                    'text' => trans('telegram.international_transfer.button.sepa_transfer'),
                    'callback_data' => (new InternationalSepaTransfer())->getActionKey()
                ],
                [
                    'text' => trans('telegram.international_transfer.button.swift_transfer'),
                    'callback_data' => (new InternationalSwiftTransfer())->getActionKey()
                ],
            ], [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->messageService->getBackKey($this->getActionKey(__FUNCTION__))],
            ]
        ]]);

        $this->messageService->sendOrEdit($chat_id, $message_id, $reply_markup, $text);

        return $this;
    }
}
