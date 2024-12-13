<?php

namespace App\Services\MessageService\Actions;

use App\Services\MessageService\AbstractAction;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Keyboard\Keyboard;

class SailorServices extends AbstractAction
{
    protected ?string $name = "sailor_services";

    /**
     * @return $this
     * @throws TelegramSDKException
     */
    public function start(): static
    {
        $chat_id = $this->messageService->chatId;
        $message_id = $this->messageService->messageId;
        $text = trans('telegram.sailor_services.message.service_type');
        $this->messageService->order->syncSteps(
            $this->getActionKey(__FUNCTION__),
            trans('telegram.sailor_services.order.services'),
            $this->messageService->getSelectedOptionName()
        );

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                [
                    'text' => trans('telegram.sailor_services.button.accept_foreign_currency_payment'),
                    'callback_data' => (new SailorServicesAcceptPayment())->getActionKey()
                ],
            ], [
                [
                    'text' =>  trans('telegram.sailor_services.button.currency_exchange'),
                    'callback_data' => (new CurrencyExchange())->getActionKey()],
            ], [
                [
                    'text' => trans('telegram.button.back'),
                    'callback_data' => $this->messageService->getBackKey($this->getActionKey(__FUNCTION__))
                ],
            ]
        ]]);

        $this->messageService->sendOrEdit($chat_id, $message_id, $reply_markup, $text);

        return $this;
    }

}
