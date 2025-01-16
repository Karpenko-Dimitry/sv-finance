<?php

namespace App\Services\MessageService\Actions;

use App\Services\MessageService\AbstractAction;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Keyboard\Keyboard;

class CryptoExchange extends AbstractAction
{
    protected ?string $name = 'crypto_exchange';

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
            trans('telegram.crypto_exchange.order.service_type'),
            $this->messageService->getSelectedOptionName()
        );
        $text = $this->messageService->order->getStepsFormattedData() . "\n\n";
        $text .= trans('telegram.crypto_exchange.message.service_type');

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                [
                    'text' => trans('telegram.button.buy'),
                    'callback_data' => $this->getActionKey('crypto_type', trans('telegram.crypto_exchange.message_vars.buy'))
                ],
                [
                    'text' =>  trans('telegram.button.sell'),
                    'callback_data' => $this->getActionKey('crypto_type', trans('telegram.crypto_exchange.message_vars.sell'))
                ],

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
    public function crypto_type(): static
    {
        $chat_id = $this->messageService->chatId;
        $message_id = $this->messageService->messageId;
        $value_keys = $this->messageService->getSelectedOptionKey('action');
        $action = $value_keys['action'] ?? null;
        $payment_action = $action == trans('telegram.crypto_exchange.message_vars.buy')
            ? trans('telegram.crypto_exchange.message_vars.give')
            : trans('telegram.crypto_exchange.message_vars.take');
        $value_keys = array_merge($value_keys, compact('payment_action'));

        $this->messageService->order->syncSteps(
            current_key: $this->getActionKey(__FUNCTION__),
            name: trans('telegram.crypto_exchange.order.services'),
            value: $this->messageService->getSelectedOptionName(),
            value_keys: $value_keys,
        );
        $text = $this->messageService->order->getStepsFormattedData() . "\n\n";
        $text .= trans('telegram.crypto_exchange.message.currency_type', $this->messageService->order->getStepsValueKeys());

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                [
                    'text' => trans('telegram.crypto_exchange.button.btc'),
                    'callback_data' => $this->getActionKey('amount', trans('telegram.crypto_exchange.message_vars.btc'))
                ],
                [
                    'text' =>  trans('telegram.crypto_exchange.button.eth'),
                    'callback_data' => $this->getActionKey('amount', trans('telegram.crypto_exchange.message_vars.eth'))
                ],
                [
                    'text' =>  trans('telegram.crypto_exchange.button.usdt'),
                    'callback_data' => $this->getActionKey('amount', trans('telegram.crypto_exchange.message_vars.usdt'))
                ],

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
    public function amount(): static {
        if ($this->messageService->lastStep->current_key == $this->getActionKeyWithoutPostfix(__FUNCTION__)) {
            $result = str_replace(',', '.', $this->messageService->message->text);
            if (!is_numeric($result)) {
                $validationText = trans('telegram.errors.not_numeric');
            } elseif ($result < 0.001) {
                $validationText = trans('telegram.errors.invalid_amount', ['amount' => 0.001]);
            } else {
                return $this->pay_type();
            }
        }
        $this->messageService->order->syncSteps(
            current_key: $this->getActionKey(__FUNCTION__),
            name: trans('telegram.crypto_exchange.order.currency_type'),
            value: $this->messageService->getSelectedOptionName(),
            value_keys: $this->messageService->getSelectedOptionKey('currency'),
        );
        $chat_id = $this->messageService->chatId;
        $message_id = $this->messageService->messageId;
        $text = $this->messageService->order->getStepsFormattedData() . "\n\n";
        $text .= trans('telegram.crypto_exchange.message.amount', $this->messageService->order->getStepsValueKeys());
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
    public function pay_type(): static
    {
        $chat_id = $this->messageService->chatId;
        $message_id = $this->messageService->messageId;
        $this->messageService->order->syncSteps(
           current_key:  $this->getActionKey(__FUNCTION__),
            name: trans('telegram.crypto_exchange.order.amount'),
            value: $this->messageService->message->text,
           value_keys: $this->messageService->getSelectedOptionKey('amount'),
        );
        $text = $this->messageService->order->getStepsFormattedData() . "\n\n";
        $text .= trans('telegram.crypto_exchange.message.pay_type', $this->messageService->order->getStepsValueKeys());

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.crypto_exchange.button.cash'), 'callback_data' => $this->getActionKey('city')],
                ['text' => trans('telegram.crypto_exchange.button.card'), 'callback_data' => $this->getActionKey('bank')],

            ], [
                ['text' => trans('telegram.button.custom'), 'callback_data' => $this->getActionKey('custom_pay_type')],
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
        $this->messageService->order->syncSteps(
            $this->getActionKey(__FUNCTION__),
            trans('telegram.crypto_exchange.order.pay_type'),
            $this->messageService->getSelectedOptionName()
        );
        $text = $this->messageService->order->getStepsFormattedData() . "\n\n";
        $text .= trans('telegram.crypto_exchange.message.city', $this->messageService->order->getStepsValueKeys());

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.moscow'), 'callback_data' => $this->getActionKey('currency_type')],
                ['text' =>  trans('telegram.button.sevastopol'), 'callback_data' => $this->getActionKey('currency_type')],
            ], [
                ['text' => trans('telegram.button.simferopol'), 'callback_data' => $this->getActionKey('currency_type')],
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
                return $this->currency_type();
            }
        }
        $this->messageService->order->syncSteps(
            $this->getActionKey(__FUNCTION__),
            trans('telegram.crypto_exchange.order.city'),
        );

        $chat_id = $this->messageService->chatId;
        $message_id = $this->messageService->messageId;
        $text = $this->messageService->order->getStepsFormattedData() . "\n\n";
        $text .= trans('telegram.crypto_exchange.message.custom_city', $this->messageService->order->getStepsValueKeys());
        $text = $validationText ?? $text;
        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->messageService->getBackKey($this->getActionKey(__FUNCTION__))],
            ]
        ]]);
        $this->messageService->sendOrEdit($chat_id, $message_id, $reply_markup, $text);

        return $this;
    }

    public function custom_pay_type(): static
    {
        if ($this->messageService->lastStep->current_key == $this->getActionKeyWithoutPostfix(__FUNCTION__)) {
            if (strlen($this->messageService->message->text) < 3 || strlen($this->messageService->message->text) > 200) {
                $validationText = trans('telegram.errors.str_length');
            } elseif (is_numeric($this->messageService->message->text)) {
                $validationText = trans('telegram.errors.not_alphabet');
            } else {
                return $this->cart();
            }
        }
        $this->messageService->order->syncSteps(
            $this->getActionKey(__FUNCTION__),
            trans('telegram.crypto_exchange.order.city'),
        );

        $chat_id = $this->messageService->chatId;
        $message_id = $this->messageService->messageId;
        $text = $this->messageService->order->getStepsFormattedData() . "\n\n";
        $text .= trans('telegram.crypto_exchange.message.custom_pay_type', $this->messageService->order->getStepsValueKeys());
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
    public function currency_type(): static
    {
        $chat_id = $this->messageService->chatId;
        $message_id = $this->messageService->messageId;
        $this->messageService->order->syncSteps(
            $this->getActionKey(__FUNCTION__),
            trans('telegram.crypto_exchange.order.city'),
            $this->messageService->getSelectedOptionName()
        );
        $text = $this->messageService->order->getStepsFormattedData() . "\n\n";
        $text .= trans('telegram.message.crypto_currency_type', $this->messageService->order->getStepsValueKeys());

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.usd'), 'callback_data' => $this->getActionKey('cart')],
                ['text' => trans('telegram.button.eur'), 'callback_data' => $this->getActionKey('cart')],
                ['text' => trans('telegram.button.rub'), 'callback_data' => $this->getActionKey('cart')],
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
    public function bank(): static
    {
        $chat_id = $this->messageService->chatId;
        $message_id = $this->messageService->messageId;
        $this->messageService->order->syncSteps(
            $this->getActionKey(__FUNCTION__),
            trans('telegram.crypto_exchange.order.pay_type'),
            $this->messageService->getSelectedOptionName()
        );
        $text = $this->messageService->order->getStepsFormattedData() . "\n\n";
        $text .= trans('telegram.crypto_exchange.message.bank', $this->messageService->order->getStepsValueKeys());

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.crypto_exchange.button.sberbank'), 'callback_data' => $this->getActionKey('cart')],
                ['text' => trans('telegram.crypto_exchange.button.tbank'), 'callback_data' => $this->getActionKey('cart')],
            ], [
                ['text' => trans('telegram.crypto_exchange.button.rnkb'), 'callback_data' => $this->getActionKey('cart')],
                ['text' => trans('telegram.button.custom'), 'callback_data' => $this->getActionKey('custom_bank')],
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
    public function custom_bank(): static
    {
        if ($this->messageService->lastStep->current_key == $this->getActionKeyWithoutPostfix(__FUNCTION__)) {
            if (strlen($this->messageService->message->text) < 3 || strlen($this->messageService->message->text) > 200) {
                $validationText = trans('telegram.errors.str_length');
            } elseif (is_numeric($this->messageService->message->text)) {
                $validationText = trans('telegram.errors.not_alphabet');
            } else {
                return $this->cart();
            }
        }
        $this->messageService->order->syncSteps(
            $this->getActionKey(__FUNCTION__),
            trans('telegram.crypto_exchange.order.city'),
        );

        $chat_id = $this->messageService->chatId;
        $message_id = $this->messageService->messageId;
        $text = $this->messageService->order->getStepsFormattedData() . "\n\n";
        $text .= trans('telegram.crypto_exchange.message.custom_bank', $this->messageService->order->getStepsValueKeys());
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
        $key = $this->messageService->lastStep->current_key;
        $key = explode('@', $key)[1];
        $key = explode(':', $key)[0];
        $this->messageService->order->syncSteps(
            $this->getActionKey(__FUNCTION__),
            trans('telegram.crypto_exchange.order.' .  $key),
            $this->messageService->getSelectedOptionName() ?: $this->messageService->message->text
        );
        $this->messageService->order->load('steps');

        $text = $this->messageService->order->getStepsFormattedData();

        $reply_markup = $this->getCartReplyMarkup();
        $this->messageService->sendOrEdit($chat_id, $message_id, $reply_markup, $text, $this->requestContact());

        return $this;
    }
}
