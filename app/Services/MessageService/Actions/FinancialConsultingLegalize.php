<?php

namespace App\Services\MessageService\Actions;

use App\Models\OrderStep;
use App\Services\MessageService\AbstractAction;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Keyboard\Keyboard;

class FinancialConsultingLegalize extends AbstractAction
{
    protected ?string $name = "fin_consulting_legalize";

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
                return $this->aim();
            }
        }
        $this->messageService->order->syncSteps(
            $this->getActionKey(__FUNCTION__),
            trans('telegram.financial_consulting_legalize.order.services_type'),
            $this->messageService->getSelectedOptionName()
        );

        $chat_id = $this->messageService->chatId;
        $message_id = $this->messageService->messageId;
        $text = $this->messageService->order->getStepsFormattedData() . "\n\n";
        $text .= trans('telegram.financial_consulting_legalize.message.country');
        $text = $validationText ?? $text;
        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->messageService->getBackKey($this->getActionKey(__FUNCTION__))],
            ]
        ]]);
        $this->messageService->sendOrEdit($chat_id, $message_id, $reply_markup, $text);

        return $this;
    }

    public function aim(): static
    {
        if ($this->messageService->lastStep->current_key == $this->getActionKeyWithoutPostfix(__FUNCTION__)) {
            if (strlen($this->messageService->message->text) < 3 || strlen($this->messageService->message->text) > 200) {
                $validationText = trans('telegram.errors.str_length');
            } else {
                return $this->city();
            }
        }
        $this->messageService->order->syncSteps(
            $this->getActionKey(__FUNCTION__),
            trans('telegram.financial_consulting_legalize.order.country'),
            $this->messageService->getSelectedOptionName()
        );

        $chat_id = $this->messageService->chatId;
        $message_id = $this->messageService->messageId;
        $text = $this->messageService->order->getStepsFormattedData() . "\n\n";
        $text .= trans('telegram.financial_consulting_legalize.message.aim');
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
            trans('telegram.financial_consulting_legalize.order.aim'),
            $this->messageService->getSelectedOptionName()
        );
        $text = $this->messageService->order->getStepsFormattedData() . "\n\n";
        $text .= trans('telegram.financial_consulting_legalize.message.city');

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.moscow'), 'callback_data' => $this->getActionKey('currency')],
                ['text' =>  trans('telegram.button.sevastopol'), 'callback_data' => $this->getActionKey('currency')],
            ], [
                ['text' => trans('telegram.button.simferopol'), 'callback_data' => $this->getActionKey('currency')],
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
                return $this->currency();
            }
        }
        $this->messageService->order->syncSteps(
            $this->getActionKey(__FUNCTION__),
            trans('telegram.financial_consulting_legalize.order.city'),
        );

        $chat_id = $this->messageService->chatId;
        $message_id = $this->messageService->messageId;
        $text = $this->messageService->order->getStepsFormattedData() . "\n\n";
        $text .= trans('telegram.financial_consulting_legalize.message.city');
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
    public function currency(): static
    {
        $chat_id = $this->messageService->chatId;
        $message_id = $this->messageService->messageId;
        $this->messageService->order->syncSteps(
            $this->getActionKey(__FUNCTION__),
            trans('telegram.financial_consulting_legalize.order.city'),
            $this->messageService->getSelectedOptionName()
        );
        $text = $this->messageService->order->getStepsFormattedData() . "\n\n";
        $text .= trans('telegram.financial_consulting_legalize.message.currency');

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
                $validationText = trans('telegram.errors.str_length');
            } elseif (is_numeric($this->messageService->message->text)) {
                $validationText = trans('telegram.errors.not_alphabet');
            } else {
                return $this->amount();
            }
        }
        $this->messageService->order->syncSteps(
            $this->getActionKey(__FUNCTION__),
            trans('telegram.financial_consulting_legalize.order.currency'),
        );

        $chat_id = $this->messageService->chatId;
        $message_id = $this->messageService->messageId;
        $text = $this->messageService->order->getStepsFormattedData() . "\n\n";
        $text .= trans('telegram.financial_consulting_legalize.message.currency');
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
                return $this->cart();
            }
        }
        $this->messageService->order->syncSteps(
            $this->getActionKey(__FUNCTION__),
            trans('telegram.financial_consulting_legalize.order.currency'),
            $this->messageService->getSelectedOptionName()
        );
        $chat_id = $this->messageService->chatId;
        $message_id = $this->messageService->messageId;
        $text = $this->messageService->order->getStepsFormattedData() . "\n\n";
        $text .= trans('telegram.financial_consulting_legalize.message.amount');
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
            trans('telegram.financial_consulting_legalize.order.amount'),
            $this->messageService->getSelectedOptionName() ?: $this->messageService->message->text
        );
        $this->messageService->order->load('steps');

        $text = trans('telegram.currency_exchange.order.order', ['number' => $this->messageService->order->id]) . "\n";
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
