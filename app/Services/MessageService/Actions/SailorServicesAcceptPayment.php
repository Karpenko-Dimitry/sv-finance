<?php

namespace App\Services\MessageService\Actions;

use App\Models\OrderStep;
use App\Services\MessageService\AbstractAction;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Keyboard\Keyboard;

class SailorServicesAcceptPayment extends AbstractAction
{
    protected ?string $name = "sailor_services_accept_payment";

    /**
     * @return $this
     * @throws TelegramSDKException
     */
    public function start(): static
    {
        $chat_id = $this->messageService->chatId;
        $message_id = $this->messageService->messageId;
        $text = trans('telegram.sailor_services_accept_payment.message.pay_type');
        $this->messageService->order->syncSteps(
            $this->getActionKey(__FUNCTION__),
            trans('telegram.sailor_services_accept_payment.order.service_type'),
            $this->messageService->getSelectedOptionName()
        );

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.sailor_services_accept_payment.button.ship_money'), 'callback_data' => $this->getActionKey('currency')],
                ['text' =>  trans('telegram.sailor_services_accept_payment.button.mar_trust'), 'callback_data' => $this->getActionKey('currency')],
                ['text' =>  trans('telegram.sailor_services_accept_payment.button.custom_option'), 'callback_data' => $this->getActionKey('custom_pay_type')],
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
    public function currency(): static
    {
        $chat_id = $this->messageService->chatId;
        $message_id = $this->messageService->messageId;
        $text = trans('telegram.sailor_services_accept_payment.message.currency');
        $this->messageService->order->syncSteps(
            $this->getActionKey(__FUNCTION__),
            trans('telegram.sailor_services_accept_payment.order.pay_type'),
            $this->messageService->getSelectedOptionName()
        );

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.usd'), 'callback_data' => $this->getActionKey('amount')],
                ['text' =>  trans('telegram.button.eur'), 'callback_data' => $this->getActionKey('amount')],
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
                $text = trans('telegram.errors.str_length');
            } else {
                return $this->amount();
            }
        }
        $this->messageService->order->syncSteps(
            $this->getActionKey(__FUNCTION__),
            trans('telegram.sailor_services_accept_payment.order.currency'),
            $this->messageService->getSelectedOptionName()
        );
        $chat_id = $this->messageService->chatId;
        $message_id = $this->messageService->messageId;
        $text = $text ?? trans('telegram.sailor_services_accept_payment.message.currency');
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
                $text = trans('telegram.errors.not_numeric');
            } elseif ($this->messageService->message->text < 1000) {
                $text = trans('telegram.errors.invalid_amount', ['amount' => 1000]);
            } else {
                return $this->transfer_type();
            }
        }
        $this->messageService->order->syncSteps(
            $this->getActionKey(__FUNCTION__),
            trans('telegram.sailor_services_accept_payment.order.currency'),
            $this->messageService->getSelectedOptionName()
        );
        $chat_id = $this->messageService->chatId;
        $message_id = $this->messageService->messageId;
        $text = $text ?? trans('telegram.sailor_services_accept_payment.message.amount');
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
    public function transfer_type(): static
    {
        $chat_id = $this->messageService->chatId;
        $message_id = $this->messageService->messageId;
        $text = trans('telegram.sailor_services_accept_payment.message.transfer_type');
        $this->messageService->order->syncSteps(
            $this->getActionKey(__FUNCTION__),
            trans('telegram.sailor_services_accept_payment.order.amount'),
            $this->messageService->getSelectedOptionName()
        );

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.sailor_services_accept_payment.button.cash'), 'callback_data' => $this->getActionKey('city_transfer')],
                ['text' => trans('telegram.sailor_services_accept_payment.button.card'), 'callback_data' => $this->getActionKey('cart')],
                ['text' => trans('telegram.sailor_services_accept_payment.button.custom'), 'callback_data' => $this->getActionKey('custom_transfer')],
            ], [
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
    public function city_transfer(): static
    {
        $chat_id = $this->messageService->chatId;
        $message_id = $this->messageService->messageId;
        $text = trans('telegram.sailor_services_accept_payment.message.city_transfer');
        $this->messageService->order->syncSteps(
            $this->getActionKey(__FUNCTION__),
            trans('telegram.sailor_services_accept_payment.order.transfer_type'),
            $this->messageService->getSelectedOptionName()
        );

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.moscow'), 'callback_data' => $this->getActionKey('recipient_currency')],
                ['text' =>  trans('telegram.button.sevastopol'), 'callback_data' => $this->getActionKey('recipient_currency')],
            ], [
                ['text' => trans('telegram.button.simferopol'), 'callback_data' => $this->getActionKey('recipient_currency')],
                ['text' => trans('telegram.button.custom_city'), 'callback_data' => $this->getActionKey('custom_city_transfer')],
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
    public function custom_city_transfer(): static
    {
        if ($this->messageService->lastStep->current_key == $this->getActionKeyWithoutPostfix(__FUNCTION__)) {
            if (strlen($this->messageService->message->text) < 3 || strlen($this->messageService->message->text) > 200) {
                $text = trans('telegram.errors.str_length');
            } else {
                return $this->recipient_currency();
            }
        }
        $this->messageService->order->syncSteps(
            $this->getActionKey(__FUNCTION__),
            trans('telegram.sailor_services_accept_payment.order.city_transfer'),
            $this->messageService->getSelectedOptionName()
        );

        $chat_id = $this->messageService->chatId;
        $message_id = $this->messageService->messageId;
        $text = $text ?? trans('telegram.sailor_services_accept_payment.message.city_transfer');
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
    public function cart(): static {
        $chat_id = $this->messageService->chatId;
        $message_id = $this->messageService->messageId;
        $key = $this->messageService->lastStep->current_key;
        $key = explode('@', $key)[1];
        $key = explode(':', $key)[0];
        $this->messageService->order->syncSteps(
            $this->getActionKey(__FUNCTION__),
            trans('telegram.sailor_services_accept_payment.order.recipient_currency'),
            $this->messageService->getSelectedOptionName() ?: $this->messageService->message->text
        );
        $this->messageService->order->load('steps');

        $text = trans('telegram.currency_exchange.order.order', ['number' => $this->messageService->order->id]) . "\n";
        $text .=  $this->messageService->order->steps->filter(fn(OrderStep $step) => $step->value)->sortBy('id')
            ->map(fn (OrderStep $step) => $step->name . ' ' . $step->value)->implode("\n");

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

    /**
     * @return $this
     * @throws TelegramSDKException
     */
    public function custom_transfer(): static
    {
        if ($this->messageService->lastStep->current_key == $this->getActionKeyWithoutPostfix(__FUNCTION__)) {
            if (strlen($this->messageService->message->text) < 3 || strlen($this->messageService->message->text) > 200) {
                $text = trans('telegram.errors.str_length');
            } else {
                return $this->recipient_currency();
            }
        }
        $this->messageService->order->syncSteps(
            $this->getActionKey(__FUNCTION__),
            trans('telegram.sailor_services_accept_payment.order.transfer_type'),
            $this->messageService->getSelectedOptionName()
        );

        $chat_id = $this->messageService->chatId;
        $message_id = $this->messageService->messageId;
        $text = $text ?? trans('telegram.sailor_services_accept_payment.message.transfer_type');
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
    public function custom_pay_type(): static
    {
        if ($this->messageService->lastStep->current_key == $this->getActionKeyWithoutPostfix(__FUNCTION__)) {
            if (strlen($this->messageService->message->text) < 3 || strlen($this->messageService->message->text) > 200) {
                $text = trans('telegram.errors.str_length');
            } else {
                return $this->currency();
            }
        }
        $this->messageService->order->syncSteps(
            $this->getActionKey(__FUNCTION__),
            trans('telegram.sailor_services_accept_payment.order.pay_type'),
            $this->messageService->getSelectedOptionName()
        );

        $chat_id = $this->messageService->chatId;
        $message_id = $this->messageService->messageId;
        $text = $text ?? trans('telegram.sailor_services_accept_payment.message.pay_type');
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
    public function recipient_currency(): static
    {
        $chat_id = $this->messageService->chatId;
        $message_id = $this->messageService->messageId;
        $text = trans('telegram.sailor_services_accept_payment.message.recipient_currency');
        $key = $this->messageService->lastStep->current_key;
        $key = explode('@', $key)[1];
        $key = explode(':', $key)[0];
        $this->messageService->order->syncSteps(
            $this->getActionKey(__FUNCTION__),
            trans('telegram.sailor_services_accept_payment.order.' . $key),
            $this->messageService->getSelectedOptionName()
        );

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.usd'), 'callback_data' => $this->getActionKey('cart')],
                ['text' =>  trans('telegram.button.eur'), 'callback_data' => $this->getActionKey('cart')],
                ['text' =>  trans('telegram.button.custom_currency'), 'callback_data' => $this->getActionKey('custom_recipient_currency')],
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
    public function custom_recipient_currency(): static
    {
        if ($this->messageService->lastStep->current_key == $this->getActionKeyWithoutPostfix(__FUNCTION__)) {
            if (strlen($this->messageService->message->text) < 3) {
                $text = trans('telegram.errors.str_length');
            } else {
                return $this->cart();
            }
        }
        $this->messageService->order->syncSteps(
            $this->getActionKey(__FUNCTION__),
            trans('telegram.sailor_services_accept_payment.order.recipient_currency'),
            $this->messageService->getSelectedOptionName()
        );
        $chat_id = $this->messageService->chatId;
        $message_id = $this->messageService->messageId;
        $text = $text ?? trans('telegram.sailor_services_accept_payment.message.recipient_currency');
        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->messageService->getBackKey($this->getActionKey(__FUNCTION__))],
            ]
        ]]);
        $this->messageService->sendOrEdit($chat_id, $message_id, $reply_markup, $text);

        return $this;
    }
}
