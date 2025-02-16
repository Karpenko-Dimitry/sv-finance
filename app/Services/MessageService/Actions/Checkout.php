<?php

namespace App\Services\MessageService\Actions;

use App\Models\ChatMember;
use App\Models\Order;
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
    public function checkout(?bool $requestedContact = false): static {
        $chat_id = $this->messageService->chatId;
        $message_id = $this->messageService->messageId;
        $this->messageService->order->load('steps');
        $this->messageService->order->checkout();
        $nickname = $this->messageService->localTelegramUser->username;
        $phone = $this->messageService->localTelegramUser->phone;
        $text = trans('telegram.checkout.order', ['number' => $this->messageService->order->number]) . "\n";
        $text .= trans('telegram.checkout.date', ['date' => now()->format('d-m-Y H:i')]) . "\n";
        $text .= trans('telegram.checkout.surname', ['surname' => $this->messageService->localTelegramUser->last_name]) . "\n";
        $text .= trans('telegram.checkout.name', ['name' => $this->messageService->localTelegramUser->first_name]) . "\n";
        $text .= $nickname ? trans('telegram.checkout.nickname', compact('nickname')) . "\n" : '';
        $text .= $phone ? trans('telegram.checkout.phone', compact('phone')) . "\n" : '';
        $caption = $text;
        $text .= $this->messageService->order->getStepsFormattedData();

        !$requestedContact && $this->messageService->telegram->editMessageText(compact('chat_id','message_id', 'text'));
        $this->messageService->telegram->sendMessage(array_merge(compact('chat_id','message_id'), [
            'text' => trans('telegram.checkout.completed'),
//            'reply_markup' => json_encode([
//                'remove_keyboard' => true,
//            ]),
            'reply_markup' => new Keyboard(['inline_keyboard' => [
                [
                    ['text' => trans('telegram.button.feedback'), 'callback_data' => (new Feedback())->getActionKey('start')],
                ]
            ]])
        ]));

        if ($chat_id = ChatMember::where('order_type', $this->messageService->order->type)->first()->chat_id) {
            $reply_markup = new Keyboard(['inline_keyboard' => [
                [
                    ['text' => trans('telegram.button.completed'), 'callback_data' => (new Checkout())->getActionKey('completed', $this->messageService->order->id)],
                ]
            ]]);
            $this->messageService->telegram->sendMessage([
                'chat_id' => $chat_id,
                'text' => $text,
                'reply_markup' => $reply_markup,
            ]);
            if (count($this->messageService->order->file_ids ?? [])) {
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
        }

        return $this;
    }

    public function completed(): static {
        $data = $this->messageService->getSelectedOptionKey('id');
        /** @var Order|null $order */
        $order = $data ? Order::where($data)->first() : null;

        $order?->update(['status' => Order::STATUS_COMPLETED]);
        $chat_id = $this->messageService->chatId;
        $message_id = $this->messageService->messageId;
        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.delete'), 'callback_data' => (new Checkout())->getActionKey('delete')],
            ]
        ]]);

        $this->messageService->telegram->editMessageReplyMarkup(compact('chat_id','message_id', 'reply_markup'));
        return $this;
    }

    /**
     * @return $this
     * @throws TelegramSDKException
     */
    public function delete(): static {
        $chat_id = $this->messageService->chatId;
        $message_id = $this->messageService->messageId;
        $this->messageService->telegram->deleteMessage(compact('chat_id','message_id'));
        return $this;
    }
}
