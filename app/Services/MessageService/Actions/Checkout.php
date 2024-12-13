<?php

namespace App\Services\MessageService\Actions;

use App\Models\Order;
use App\Models\OrderStep;
use App\Services\MessageService\AbstractAction;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Keyboard\Keyboard;

class Checkout extends AbstractAction
{
    protected ?string $name = 'checkout';
    /**
     * @return $this
     * @throws TelegramSDKException
     */
    public function checkout(): static {
        $chat_id = $this->messageService->chatId;
        $message_id = $this->messageService->messageId;
        $this->messageService->order->load('steps');
        $text = trans('telegram.checkout.order', ['number' => $this->messageService->order->id]) . "\n";
        $text .= trans('telegram.checkout.surname', ['surname' => $this->messageService->localTelegramUser->last_name]) . "\n";
        $text .= trans('telegram.checkout.name', ['name' => $this->messageService->localTelegramUser->first_name]) . "\n";
        $text .= trans('telegram.checkout.nickname', ['nickname' => $this->messageService->localTelegramUser->username]) . "\n";
        $caption = $text;
        $text .= $this->messageService->order->steps->filter(fn(OrderStep $step) => $step->value)->sortBy('id')
            ->map(fn (OrderStep $step) => $step->name . ' ' . $step->value)->implode("\n");
        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.completed'), 'callback_data' => 'completed'],
            ]
        ]]);
        $this->messageService->telegram->editMessageText(compact('chat_id','message_id', 'text'));
        $this->messageService->telegram->sendMessage(array_merge(compact('chat_id','message_id'), [
            'text' => trans('telegram.checkout.completed'),
        ]));

        $chat_id = '-1002401645369';
        $this->messageService->telegram->sendMessage([
            'chat_id' => $chat_id,
            'text' => $text,
            'reply_markup' => $reply_markup,
        ]);
        if (count($this->order->file_ids ?? [])) {
            foreach ($this->messageService->order->file_ids as $type => $files) {
                if ($type == Order::FILE_TYPE_DOOCUMENT) {
                    foreach ($files as $file) {
                        $document = $file;
                        $this->messageService->telegram->sendDocument(compact('chat_id', 'document', 'caption'));
                    }
                }
                if ($type == Order::FILE_TYPE_PHOTO) {
                    foreach ($files as $file) {
                        $photo = $file;
                        $this->messageService->telegram->sendPhoto(compact('chat_id', 'photo', 'caption'));
                    }
                }
            }
        }

        $this->messageService->order->checkout();

        return $this;
    }
}
