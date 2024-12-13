<?php

namespace App\Services\MessageService\Actions;

use App\Services\MessageService\AbstractAction;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Keyboard\Keyboard;

class FinancialConsulting extends AbstractAction
{
    protected ?string $name = "financial_consulting";

    /**
     * @return $this
     * @throws TelegramSDKException
     */
    public function start(): static
    {
        $chat_id = $this->messageService->chatId;
        $message_id = $this->messageService->messageId;
        $text = trans('telegram.financial_consulting.message.service_type');
        $this->messageService->order->syncSteps(
            $this->getActionKey(__FUNCTION__),
            trans('telegram.financial_consulting.order.services'),
            $this->messageService->getSelectedOptionName()
        );

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                [
                    'text' => trans('telegram.financial_consulting.button.relocation'),
                    'callback_data' => (new FinancialConsultingRelocation())->getActionKey()
                ],
            ], [
                [
                    'text' =>  trans('telegram.financial_consulting.button.financial_resource_legalization'),
                    'callback_data' => (new FinancialConsultingLegalize())->getActionKey()
                ]
            ], [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->messageService->getBackKey($this->getActionKey(__FUNCTION__))],
            ]
        ]]);
        $this->messageService->sendOrEdit($chat_id, $message_id, $reply_markup, $text);

        return $this;
    }
}
