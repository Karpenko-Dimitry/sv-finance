<?php

namespace App\Services\MessageService\Actions;

use App\Models\ChatMember;
use App\Models\Order;
use App\Services\MessageService\AbstractAction;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Keyboard\Keyboard;

class Feedback extends AbstractAction
{
    protected ?string $name = "feedback";

    /**
     * @return $this
     * @throws TelegramSDKException
     */
    public function start(): static
    {
        if ($this->messageService->lastStep?->current_key == $this->getActionKeyWithoutPostfix(__FUNCTION__)) {
            if (strlen($this->messageService->message->text) < 3 || strlen($this->messageService->message->text) > 200) {
                $validationText = trans('telegram.errors.str_length');
            } else {
                $this->complete();
                return $this;
            }
        }
        $order = $this->messageService->order;
        $order?->steps()->delete();
        $order?->syncSteps(
            $this->getActionKey(__FUNCTION__),
            trans('telegram.feedback.order.start'),
        );

        $order?->update(['type' => Order::TYPE_FEEDBACK]);

        $chat_id = $this->messageService->chatId;
        $message_id = $this->messageService->messageId;
        $text = trans('telegram.feedback.message.start');
        $text = $validationText ?? $text;
        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.back'), 'callback_data' => self::getActionKey('back')],
            ]
        ]]);
        $this->messageService->sendOrEdit($chat_id, $message_id, $reply_markup, $text);
        return $this;
    }

    /**
     * @return void
     * @throws TelegramSDKException
     */
    public function complete(): void
    {
        $chat_id = $this->messageService->chatId;
        $value = $this->messageService->message->text ?? '';
        $order = $this->messageService->order;
        $order?->update(['status' => Order::STATUS_COMPLETED]);
        $order->syncSteps(
            current_key: $this->getActionKeyWithoutPostfix('start'),
            name: trans('telegram.feedback.order.start'),
            value: $value,
        );

        $text = trans('telegram.feedback.message.complete');
        $this->messageService->telegram->sendMessage(compact('chat_id', 'text'));

        if ($chat_id = ChatMember::where('order_type', Order::TYPE_FEEDBACK)->first()->chat_id) {
            $this->messageService->telegram->sendMessage([
                'chat_id' => $chat_id,
                'text' => $value,
            ]);
        }
    }

    /**
     * @return $this
     * @throws TelegramSDKException
     */
    public function back(): static
    {
        (new Home())->start();
        return $this;
    }
}
