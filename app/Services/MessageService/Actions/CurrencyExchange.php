<?php

namespace App\Services\MessageService\Actions;

use App\Models\Order;
use App\Models\OrderStep;
use App\Services\MessageService\AbstractAction;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Keyboard\Keyboard;

class CurrencyExchange extends AbstractAction
{
    protected ?string $name = "currency_exchange";

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
            trans('telegram.currency_exchange.order.services'),
            $this->messageService->getSelectedOptionName()
        );
        $text = trans('telegram.currency_exchange.message.service_type');

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                [
                    'text' => trans('telegram.currency_exchange.button.buy'),
                    'callback_data' => $this->getActionKey('city', trans('telegram.currency_exchange.message_vars.buy'))
                ],
                [
                    'text' =>  trans('telegram.currency_exchange.button.sell'),
                    'callback_data' => $this->getActionKey('city', trans('telegram.currency_exchange.message_vars.sell'))],
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
    public function city(): static
    {
        $chat_id = $this->messageService->chatId;
        $message_id = $this->messageService->messageId;
        $order = $this->messageService->order->syncSteps(
            current_key: $this->getActionKey(__FUNCTION__),
            name: trans('telegram.currency_exchange.order.service_type'),
            value: $this->messageService->getSelectedOptionName(),
            value_keys: $this->messageService->getSelectedOptionKey('action')
        );

        $text = trans('telegram.currency_exchange.message.city', $order->getStepsValueKeys());
        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                [
                    'text' => trans('telegram.button.moscow'),
                    'callback_data' => $this->getActionKey('sell_buy', trans('telegram.currency_exchange.message_vars.moscow'))
                ],
                [
                    'text' =>  trans('telegram.button.sevastopol'),
                    'callback_data' => $this->getActionKey('sell_buy', trans('telegram.currency_exchange.message_vars.sevastopol'))
                ],
            ], [
                [
                    'text' => trans('telegram.button.simferopol'),
                    'callback_data' => $this->getActionKey('sell_buy', trans('telegram.currency_exchange.message_vars.simferopol'))
                ],
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
            } else {
                return $this->sell_buy();
            }
        }
        $order = $this->messageService->order->syncSteps(
            $this->getActionKey(__FUNCTION__),
            trans('telegram.currency_exchange.order.city'),
        );

        $chat_id = $this->messageService->chatId;
        $message_id = $this->messageService->messageId;
        $text = trans('telegram.currency_exchange.message.custom_city', $order->getStepsValueKeys());
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
    public function sell_buy(): static
    {
        $chat_id = $this->messageService->chatId;
        $message_id = $this->messageService->messageId;
        $order = $this->messageService->order->syncSteps(
            current_key: $this->getActionKey(__FUNCTION__),
            name: trans('telegram.currency_exchange.order.city'),
            value: $this->messageService->getSelectedOptionName(),
            value_keys: $this->messageService->getSelectedOptionKey('city')
        );

        $text = trans('telegram.currency_exchange.message.currency' , $order->getStepsValueKeys());

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                [
                    'text' => trans('telegram.button.usd'),
                    'callback_data' => $this->getActionKey('sell_buy_usd', trans('telegram.currency_exchange.message_vars.usd'))
                ],
                [
                    'text' =>  trans('telegram.button.eur'),
                    'callback_data' => $this->getActionKey('amount', trans('telegram.currency_exchange.message_vars.eur'))
                ],
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
            } else {
                return $this->amount();
            }
        }
        $order = $this->messageService->order->syncSteps(
            current_key: $this->getActionKey(__FUNCTION__),
            name: trans('telegram.currency_exchange.order.currency_type'),
        );
        $chat_id = $this->messageService->chatId;
        $message_id = $this->messageService->messageId;
        $text = trans('telegram.message.custom_currency', $order->getStepsValueKeys());
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
    public function sell_buy_usd(): static
    {
        $chat_id = $this->messageService->chatId;
        $message_id = $this->messageService->messageId;
        $order = $this->messageService->order->syncSteps(
            current_key: $this->getActionKey(__FUNCTION__),
            name: trans('telegram.currency_exchange.order.currency'),
            value: $this->messageService->getSelectedOptionName(),
            value_keys: $this->messageService->getSelectedOptionKey('currency'),
        );

        $text = trans('telegram.currency_exchange.message.currency_type', $order->getStepsValueKeys());

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.currency_exchange.button.old_usd_banknote'), 'callback_data' => $this->getActionKey('amount')],
            ], [
                ['text' =>  trans('telegram.currency_exchange.button.new_usd_banknote'), 'callback_data' => $this->getActionKey('amount')],
            ],[
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
                return $this->cart();
            }
        }
        $order = $this->messageService->order->syncSteps(
            current_key: $this->getActionKey(__FUNCTION__),
            name: trans('telegram.currency_exchange.order.currency_type'),
            value: $this->messageService->getSelectedOptionName(),
            value_keys: $this->messageService->getSelectedOptionKey('currency'),
        );
        $chat_id = $this->messageService->chatId;
        $message_id = $this->messageService->messageId;

        $text = trans('telegram.currency_exchange.message.amount', $order->getStepsValueKeys());
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
    public function cart(): static {
        $chat_id = $this->messageService->chatId;
        $message_id = $this->messageService->messageId;
        $this->messageService->order->syncSteps(
            $this->getActionKey(__FUNCTION__),
            trans('telegram.currency_exchange.order.amount'),
            $this->messageService->getSelectedOptionName() ?: $this->messageService->message->text
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
