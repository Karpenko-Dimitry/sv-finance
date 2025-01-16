<?php

namespace App\Services\MessageService\Actions;

use App\Services\MessageService\AbstractAction;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Keyboard\Keyboard;

class PaymentInvoices extends AbstractAction
{
    protected ?string $name = "payment_invoice";

    /**
     * @return $this
     * @throws TelegramSDKException
     */
    public function cart(): static {
        $chat_id = $this->messageService->chatId;
        $this->messageService->order->load('steps');

        $text = $this->messageService->order->getStepsFormattedData();

        $reply_markup = $this->getCartReplyMarkup();
        $this->messageService->telegram->sendMessage(compact('chat_id', 'text', 'reply_markup'));

        return $this;
    }
}
