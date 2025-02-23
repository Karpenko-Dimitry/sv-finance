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
    public const TYPE_ANONYMOUS = 'anonymous';

    /**
     * @return $this
     * @throws TelegramSDKException
     */
    public function start(): static
    {
        if ($this->messageService->lastStep?->current_key == $this->getActionKeyWithoutPostfix(__FUNCTION__)) {
            if (strlen($this->messageService->message->text) < 3) {
                $validationText = trans('telegram.errors.feedback_str_length');
            } else {
                $this->complete();
                return $this;
            }
        }
        $anonymous = (int) in_array($this->messageService->getSelectedOptionKey(), ['/afeedback', self::TYPE_ANONYMOUS]);
        $order = $this->messageService->order;
        $order?->steps()->delete();
        $order?->syncSteps(
            current_key: $this->getActionKey(__FUNCTION__),
            name: trans('telegram.feedback.order.start'),
            value_keys: compact('anonymous'),
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
        $order = $this->messageService->order;
        $chat_id = $this->messageService->chatId;
        $authorName = ($this->messageService->localTelegramUser->first_name ?? '') . ' ';
        $authorName .= ($this->messageService->localTelegramUser->last_name ?? '') . ' ';
        $authorName .= $this->messageService->localTelegramUser->username ? ('@' . $this->messageService->localTelegramUser->username) : '';
        $value = $this->messageService->message->text ?? '';
        $anonymous = $this->messageService->lastStep->value_keys['anonymous'] ?? 0;
        $authorName = $anonymous ? trans('telegram.feedback.message.anonymous') : $authorName;
        $value .= ("\n\n " . trans('telegram.feedback.message.author', ['author' => $authorName]));

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
