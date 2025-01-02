<?php

namespace App\Services\MessageService\Actions;

use App\Models\Order;
use App\Services\MessageService\AbstractAction;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Keyboard\Keyboard;

class PaymentAgencyAgreement extends AbstractAction
{
    protected ?string $name = "payment_agency_agreement";

    /**
     * @return $this
     * @throws TelegramSDKException
     */
    public function start(): static
    {
        if ($this->messageService->lastStep->current_key == $this->getActionKeyWithoutPostfix(__FUNCTION__)) {
            if (strlen($this->messageService->message->text) < 3 || strlen($this->messageService->message->text) > 200) {
                $validationText = trans('telegram.errors.str_length');
            } else {
                return $this->country();
            }
        }
        $this->messageService->order->syncSteps(
            $this->getActionKey(__FUNCTION__),
            trans('telegram.payment_agency_agreement.order.services_type'),
            $this->messageService->getSelectedOptionName()
        );

        $chat_id = $this->messageService->chatId;
        $message_id = $this->messageService->messageId;
        $text =  $this->messageService->order->getStepsFormattedData() . "\n\n";
        $text .= trans('telegram.payment_agency_agreement.message.aim');
        $text = $validationText ?? $text;
        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->messageService->getBackKey($this->getActionKey(__FUNCTION__))],
            ]
        ]]);
        $this->messageService->sendOrEdit($chat_id, $message_id, $reply_markup, $text);

        return $this;
    }

    public function country(): static
    {
        if ($this->messageService->lastStep->current_key == $this->getActionKeyWithoutPostfix(__FUNCTION__)) {
            if (strlen($this->messageService->message->text) < 3 || strlen($this->messageService->message->text) > 200) {
                $validationText = trans('telegram.errors.str_length');
            } else {
                return $this->amount();
            }
        }
        $this->messageService->order->syncSteps(
            $this->getActionKey(__FUNCTION__),
            trans('telegram.payment_agency_agreement.order.aim'),
            $this->messageService->getSelectedOptionName()
        );

        $chat_id = $this->messageService->chatId;
        $message_id = $this->messageService->messageId;
        $text =  $this->messageService->order->getStepsFormattedData() . "\n\n";
        $text .= trans('telegram.payment_agency_agreement.message.country');
        $text = $validationText ?? $text;
        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->messageService->getBackKey($this->getActionKey(__FUNCTION__))],
            ]
        ]]);
        $this->messageService->sendOrEdit($chat_id, $message_id, $reply_markup, $text);

        return $this;
    }

    /**
     * @return $this
     * @throws TelegramSDKException
     */
    public function amount(): static {
        if ($this->messageService->lastStep->current_key == $this->getActionKeyWithoutPostfix(__FUNCTION__)) {
            if (!is_numeric($this->messageService->message->text)) {
                $validationText = trans('telegram.errors.not_numeric');
            } elseif ($this->messageService->message->text < 1000) {
                $validationText = trans('telegram.errors.invalid_amount', ['amount' => 1000]);
            } else {
                return $this->currency();
            }
        }
        $this->messageService->order->syncSteps(
            $this->getActionKey(__FUNCTION__),
            trans('telegram.payment_agency_agreement.order.country'),
            $this->messageService->getSelectedOptionName()
        );
        $chat_id = $this->messageService->chatId;
        $message_id = $this->messageService->messageId;
        $text =  $this->messageService->order->getStepsFormattedData() . "\n\n";
        $text .= trans('telegram.payment_agency_agreement.message.amount');
        $text = $validationText ?? $text;
        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->messageService->getBackKey($this->getActionKey(__FUNCTION__))],
            ]
        ]]);
        $this->messageService->sendOrEdit($chat_id, $message_id, $reply_markup, $text);

        return $this;
    }

    public function currency(): static
    {
        $chat_id = $this->messageService->chatId;
        $message_id = $this->messageService->messageId;
        $this->messageService->order->syncSteps(
            $this->getActionKey(__FUNCTION__),
            trans('telegram.payment_agency_agreement.order.amount'),
            $this->messageService->getSelectedOptionName()
        );

        $text =  $this->messageService->order->getStepsFormattedData() . "\n\n";
        $text .= trans('telegram.payment_agency_agreement.message.currency');

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.usd'), 'callback_data' => $this->getActionKey('city')],
                ['text' =>  trans('telegram.button.eur'), 'callback_data' => $this->getActionKey('city')],
                ['text' =>  trans('telegram.button.rub'), 'callback_data' => $this->getActionKey('city')],
            ], [
                ['text' =>  trans('telegram.button.custom_currency'), 'callback_data' => $this->getActionKey('custom_currency')],
            ], [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->messageService->getBackKey($this->getActionKey(__FUNCTION__))],
            ]
        ]]);

        $this->messageService->sendOrEdit($chat_id, $message_id, $reply_markup, $text);

        return $this;
    }

    /**
     * @return $this
     * @throws TelegramSDKException
     */
    public function custom_currency(): static
    {
        if ($this->messageService->lastStep->current_key == $this->getActionKeyWithoutPostfix(__FUNCTION__)) {
            if (strlen($this->messageService->message->text) < 3) {
                $validationText = trans('telegram.errors.str_length');
            } elseif (is_numeric($this->messageService->message->text)) {
                $validationText = trans('telegram.errors.not_alphabet');
            } else {
                return $this->city();
            }
        }
        $this->messageService->order->syncSteps(
            $this->getActionKey(__FUNCTION__),
            trans('telegram.payment_agency_agreement.order.currency'),
        );
        $chat_id = $this->messageService->chatId;
        $message_id = $this->messageService->messageId;
        $text =  $this->messageService->order->getStepsFormattedData() . "\n\n";
        $text .= trans('telegram.payment_agency_agreement.message.custom_currency');
        $text = $validationText ?? $text;
        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->messageService->getBackKey($this->getActionKey(__FUNCTION__))],
            ]
        ]]);
        $this->messageService->sendOrEdit($chat_id, $message_id, $reply_markup, $text);

        return $this;
    }

    /**
     * @return $this
     * @throws TelegramSDKException
     */
    public function city(): static
    {
        $chat_id = $this->messageService->chatId;
        $message_id = $this->messageService->messageId;
        $this->messageService->order->syncSteps(
            $this->getActionKey(__FUNCTION__),
            trans('telegram.payment_agency_agreement.order.currency'),
            $this->messageService->getSelectedOptionName()
        );

        $text =  $this->messageService->order->getStepsFormattedData() . "\n\n";
        $text .= trans('telegram.payment_agency_agreement.message.city');

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.moscow'), 'callback_data' => $this->getActionKey('file')],
                ['text' =>  trans('telegram.button.sevastopol'), 'callback_data' => $this->getActionKey('file')],
            ], [
                ['text' => trans('telegram.button.simferopol'), 'callback_data' => $this->getActionKey('file')],
                ['text' => trans('telegram.button.custom_city'), 'callback_data' => $this->getActionKey('custom_city')],

            ], [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->messageService->getBackKey($this->getActionKey(__FUNCTION__))],
            ]
        ]]);

        $this->messageService->sendOrEdit($chat_id, $message_id, $reply_markup, $text);

        return $this;
    }

    /**
     * @return $this
     * @throws TelegramSDKException
     */
    public function custom_city(): static
    {
        if ($this->messageService->lastStep->current_key == $this->getActionKeyWithoutPostfix(__FUNCTION__)) {
            if (strlen($this->messageService->message->text) < 3 || strlen($this->messageService->message->text) > 200) {
                $validationText = trans('telegram.errors.str_length');
            } elseif (is_numeric($this->messageService->message->text)) {
                $validationText = trans('telegram.errors.not_alphabet');
            } else {
                return $this->file();
            }
        }
        $this->messageService->order->syncSteps(
            $this->getActionKey(__FUNCTION__),
            trans('telegram.payment_agency_agreement.order.city'),
        );

        $chat_id = $this->messageService->chatId;
        $message_id = $this->messageService->messageId;
        $text =  $this->messageService->order->getStepsFormattedData() . "\n\n";
        $text .= trans('telegram.payment_agency_agreement.message.custom_city');
        $text = $validationText ?? $text;
        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->messageService->getBackKey($this->getActionKey(__FUNCTION__))],
            ]
        ]]);
        $this->messageService->sendOrEdit($chat_id, $message_id, $reply_markup, $text);

        return $this;
    }

    public function file(): static
    {
        $chat_id = $this->messageService->chatId;
        $message_id = $this->messageService->messageId;
        $text = trans('telegram.payment_agency_agreement.message.file');
        $this->messageService->order->syncSteps(
            $this->getActionKey(__FUNCTION__),
            trans('telegram.payment_agency_agreement.order.city'),
            $this->messageService->getSelectedOptionName()
        );

        $result = $this->saveFiles();
        $text = $result ? trans('telegram.payment_agency_agreement.message.success_file') : $text;

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.payment_agency_agreement.button.forward'), 'callback_data' => $this->getActionKey('cart')],
            ], [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->messageService->getBackKey($this->getActionKey(__FUNCTION__))],
            ]
        ]]);

        $this->messageService->sendOrEdit($chat_id, $message_id, $reply_markup, $text);
        return $this;
    }

    /**
     * @return bool
     */
    public function saveFiles(): bool
    {
        $result = false;
        if ($this->messageService->message->hasAny([Order::FILE_TYPE_DOOCUMENT, Order::FILE_TYPE_PHOTO])) {
            if ($this->messageService->message->has(Order::FILE_TYPE_DOOCUMENT)) {
                $file_ids = $this->messageService->order->file_ids ?? [];
                if (!isset($file_ids[Order::FILE_TYPE_DOOCUMENT])) {
                    $file_ids[Order::FILE_TYPE_DOOCUMENT] = [];
                }

                $file_ids[Order::FILE_TYPE_DOOCUMENT][] = $this->messageService->message->document->get('file_id');
                $file_ids[Order::FILE_TYPE_DOOCUMENT] = array_unique(array_filter($file_ids[Order::FILE_TYPE_DOOCUMENT], fn($item) => $item));
                $this->messageService->order->update(compact('file_ids'));
            }

            if ($this->messageService->message->has(Order::FILE_TYPE_PHOTO)) {
                $file_ids = $this->messageService->order->file_ids ?? [];
                if (!isset($file_ids[Order::FILE_TYPE_PHOTO])) {
                    $file_ids[Order::FILE_TYPE_PHOTO] = [];
                }

                $photo = $this->messageService->message->photo[count($this->messageService->message->photo) - 1];
                $file_ids[Order::FILE_TYPE_PHOTO][] = $photo['file_id'] ?? null;
                $file_ids[Order::FILE_TYPE_PHOTO] = array_unique(array_filter($file_ids[Order::FILE_TYPE_PHOTO], fn($item) => $item));
                $this->messageService->order->update(compact('file_ids'));
            }
            $result = true;
        }

        return $result;
    }

    /**
     * @return $this
     * @throws TelegramSDKException
     */
    public function cart(): static {
        $chat_id = $this->messageService->chatId;
        $message_id = $this->messageService->messageId;
        $count = collect($this->messageService->order->file_ids ?? [])->reduce(function ($carry, $item) {
            $carry += count($item);
            return $carry;
        }, 0);

        $this->messageService->order->syncSteps(
            $this->getActionKey(__FUNCTION__),
            trans('telegram.payment_agency_agreement.order.attachment'),
            trans('telegram.payment_agency_agreement.order.files', compact('count')),
        );
        $this->messageService->order->load('steps');

        $text = trans('telegram.currency_exchange.order.order', ['number' => $this->messageService->order->id]) . "\n\n";
        $text .=  $this->messageService->order->getStepsFormattedData();

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.checkout'), 'callback_data' => (new Checkout())->getActionKey('checkout')],
            ], [
                ['text' => trans('telegram.button.back'), 'callback_data' =>  $this->messageService->getBackKey($this->getActionKey(__FUNCTION__))],
            ]
        ]]);
        $this->messageService->sendOrEdit($chat_id, $message_id, $reply_markup, $text);

        return $this;
    }
}
