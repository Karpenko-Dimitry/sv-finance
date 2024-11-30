<?php

namespace App\Services\MessageService;

use App\Models\Order;
use App\Models\OrderStep;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\FileUpload\InputFile;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Objects\CallbackQuery;
use Telegram\Bot\Objects\Message;
use Telegram\Bot\Objects\Update;
use Telegram\Bot\Objects\User;

class MessageService
{
    protected Api $telegram;
    protected Update $response;
    protected ?CallbackQuery $callbackQuery;
    protected ?Message $message;
    protected ?User $telegramUser;
    protected ?int $messageId;
    protected ?int $chatId;
    protected ?string $key;
    protected bool $send = false;
    public ?Order $order = null;
    public ?OrderStep $lastStep = null;
    public const DEFAULT_KEY = 'start';
    public function __construct()
    {
        $this->telegram = new Api();
        $this->response = $this->telegram->getWebhookUpdate();

        $this->callbackQuery = $this->response->callbackQuery;
        $this->telegramUser = $this->callbackQuery?->from ?? $this->response->message?->from;
        $this->message = $this->response->message ?? $this->callbackQuery->message;
        $this->messageId = $this->message->messageId;
        $this->chatId = $this->message->chat->id;
        $this->key = $this->callbackQuery?->data ?? $this->message->text;
    }

    /**
     * @return string
     */
    public function getSendMethod(): string
    {
        $method = explode(':', trim($this->key, '/'))[0];
        $method = method_exists(self::class, $method) ? $method : null;

        $method = $method ?? explode(':', $this->lastStep?->current_key ?? '')[0];
        return method_exists(self::class, $method) ? $method : self::DEFAULT_KEY;
    }

    /**
     * @throws TelegramSDKException
     */
    public function receive(): static
    {
        $this->order = Order::getOrder($this->telegramUser);
        $this->setLastStep();
        $method = $this->getSendMethod();

        call_user_func([static::class, $method]);

        $this->callbackQuery && $this->telegram->answerCallbackQuery(['callback_query_id' => $this->callbackQuery->id]);

        return $this;
    }

    /**
     * @return $this
     */
    public function setLastStep(?OrderStep $step = null): static
    {
        $this->lastStep = $step ?? $this->order->getLastStep();

        return $this;
    }

    /**
     * @param int $chat_id
     * @param int $message_id
     * @param Keyboard|null $reply_markup
     * @param string|null $text
     * @return void
     * @throws TelegramSDKException
     */
    public function sendOrEdit(int $chat_id, int $message_id, ?Keyboard $reply_markup, ?string $text = ''): void
    {
        if (!$this->callbackQuery || !$reply_markup) {
            $this->telegram->sendMessage(compact('chat_id', 'text', 'reply_markup'));
        } else {
            $text && $this->telegram->editMessageText(compact('chat_id','message_id', 'text'));
            $this->telegram->editMessageReplyMarkup(compact('chat_id','message_id', 'reply_markup'));
        }
    }

    /**
     * @return mixed|null
     */
    public function getSelectedOptionName(): mixed
    {
        $keyBoards = Arr::flatten($this->message->replyMarkup?->inline_keyboard ?? [],1);
        $keyBoard = collect($keyBoards)->where('callback_data', $this->key)->first();
        return $keyBoard['text'] ?? '';
    }

    /**
     * @return $this
     * @throws TelegramSDKException
     */
    public function start(): static
    {
        $chat_id = $this->chatId;
        $photo = InputFile::create(Storage::disk('public')->path('avatar-black.png'));
        $caption = trans('telegram.message.start');
        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.individuals'), 'callback_data' => 'individuals'],
                ['text' => trans('telegram.button.legal_entities'), 'callback_data' => 'legal_entities'],
            ]
        ]]);
        $this->order->steps()->delete();
        $this->telegram->sendPhoto(compact('chat_id', 'photo', 'caption', 'reply_markup'));

        return $this;
    }

    /**
     * @return $this
     * @throws TelegramSDKException
     */
    public function individuals(): static
    {
        $chat_id = $this->chatId;
        $text = trans('telegram.message.services');
        $this->order->syncSteps(__FUNCTION__, trans('telegram.order.start'), $this->getSelectedOptionName());

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.currency_exchange'), 'callback_data' => 'currency_exchange'],
                ['text' =>  trans('telegram.button.crypto_exchange'), 'callback_data' => 'crypto_exchange'],
            ], [
                ['text' =>  trans('telegram.button.international_transfers'), 'callback_data' => 'international_transfers'],
            ], [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->getBackKey(__FUNCTION__)],
            ]
        ]]);
        $this->telegram->sendMessage(compact('chat_id', 'text', 'reply_markup'));

        return $this;
    }

    /**
     * @return $this
     * @throws TelegramSDKException
     */
    public function legal_entities(): static {
        $chat_id = $this->chatId;
        $text = trans('telegram.message.services');
        $this->order->syncSteps(__FUNCTION__, trans('telegram.order.start'), $this->getSelectedOptionName());

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.payment_invoices'), 'callback_data' => 'payment_invoices'],
                ['text' => trans('telegram.button.business_relocation'), 'callback_data' => 'business_relocation'],
            ], [
                ['text' => trans('telegram.button.payment_agency_agreement'), 'callback_data' => 'payment_agency_agreement'],
            ], [
                ['text' => trans('telegram.button.return_foreign_currency_revenue'), 'callback_data' => 'return_foreign_currency_revenue'],
            ], [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->getBackKey(__FUNCTION__)],
            ]
        ]]);
        $this->telegram->sendMessage(compact('chat_id', 'text', 'reply_markup'));

        return $this;
    }

    public function cart(): static {
        $chat_id = $this->chatId;
        $message_id = $this->messageId;
        $this->order->syncSteps(__FUNCTION__, trans('telegram.order.amount'),  $this->message->text);
        $this->order->load('steps');

        $text = "Ваша заявка \n";
        $text .= $this->order->steps->filter(fn(OrderStep $step) => $step->value)->sortBy('id')
            ->map(fn (OrderStep $step) => $step->name . ' ' . $step->value)->implode("\n");

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.checkout'), 'callback_data' => 'checkout'],
            ], [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->getBackKey(__FUNCTION__)],
            ]
        ]]);
        $this->sendOrEdit($chat_id, $message_id, $reply_markup, $text);

        return $this;
    }

    /**
     * @return $this
     * @throws TelegramSDKException
     */
    public function checkout(): static {
        $chat_id = $this->chatId;
        $message_id = $this->messageId;
        $this->order->checkout()->load('steps');

        $text = "Ваша заявка подтверждена ждите ответа оператора";
        $this->sendOrEdit($chat_id, $message_id, null, $text);

        $text = "Заявка \n";
        $text .= $this->order->steps->filter(fn(OrderStep $step) => $step->value)->sortBy('id')
            ->map(fn (OrderStep $step) => $step->name . ' ' . $step->value)->implode("\n");
        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.completed'), 'callback_data' => 'completed'],
            ]
        ]]);
        $this->telegram->sendMessage([
            'chat_id' => '-4588641921',
            'text' => $text,
            'reply_markup' => $reply_markup,
        ]);

        return $this;
    }

    /**
     * @return $this
     * @throws TelegramSDKException
     */
    public function currency_exchange(): static
    {
        $chat_id = $this->chatId;
        $message_id = $this->messageId;
        $text = trans('telegram.message.service_type');
        $this->order->syncSteps(__FUNCTION__, trans('telegram.order.services'), $this->getSelectedOptionName());

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.buy'), 'callback_data' => 'city'],
                ['text' =>  trans('telegram.button.sell'), 'callback_data' => 'city'],
            ], [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->getBackKey(__FUNCTION__)],
            ]
        ]]);
        $this->sendOrEdit($chat_id, $message_id, $reply_markup, $text);

        return $this;
    }

    /**
     * @return $this
     * @throws TelegramSDKException
     */
    public function city(): static
    {
        $chat_id = $this->chatId;
        $message_id = $this->messageId;
        $text = trans('telegram.message.city');
        $this->order->syncSteps(__FUNCTION__, trans('telegram.order.service_type'), $this->getSelectedOptionName());

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.moscow'), 'callback_data' => 'sell_buy_currency:moscow'],
                ['text' =>  trans('telegram.button.sevastopol'), 'callback_data' => 'sell_buy_currency:sevastopol'],
            ], [
                ['text' => trans('telegram.button.simferopol'), 'callback_data' => 'sell_buy_currency:simferopol'],
            ], [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->getBackKey(__FUNCTION__)],
            ]
        ]]);

        $this->sendOrEdit($chat_id, $message_id, $reply_markup, $text);

        return $this;
    }

    /**
     * @return $this
     * @throws TelegramSDKException
     */
    public function sell_buy_currency(): static
    {
        $chat_id = $this->chatId;
        $message_id = $this->messageId;
        $text = trans('telegram.message.currency');
        $this->order->syncSteps(__FUNCTION__, trans('telegram.order.city'),  $this->getSelectedOptionName());

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.usd'), 'callback_data' => 'sell_buy_usd'],
                ['text' =>  trans('telegram.button.eur'), 'callback_data' => 'amount'],
                ['text' =>  trans('telegram.button.custom_currency'), 'callback_data' => 'custom_currency'],
            ], [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->getBackKey(__FUNCTION__)],
            ]
        ]]);

        $this->sendOrEdit($chat_id, $message_id, $reply_markup, $text);

        return $this;
    }

    /**
     * @return $this
     * @throws TelegramSDKException
     */
    public function custom_currency(): static
    {
        $chat_id = $this->chatId;
        $message_id = $this->messageId;
        $text = trans('telegram.message.custom_currency');

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->getBackKey(__FUNCTION__)],
            ]
        ]]);

        if ($this->lastStep->key == 'custom_currency') {
            $this->lastStep->update(['value' => trans('telegram.button.custom_currency') . ': ' . $this->message->text]);
            $this->currency_amount();
        } else {
            $this->order->syncSteps(__FUNCTION__, trans('telegram.order.currency_type'),  $this->getSelectedOptionName());
            $this->sendOrEdit($chat_id, $message_id, $reply_markup, $text);
        }

        return $this;
    }

    /**
     * @return $this
     * @throws TelegramSDKException
     */
    public function sell_buy_usd(): static
    {
        $chat_id = $this->chatId;
        $message_id = $this->messageId;
        $text = trans('telegram.message.currency_type');
        $this->order->syncSteps(__FUNCTION__, trans('telegram.order.currency'),  $this->getSelectedOptionName());

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.old_usd_banknote'), 'callback_data' => 'currency_amount:old_usd_banknote'],
            ], [
                ['text' =>  trans('telegram.button.new_usd_banknote'), 'callback_data' => 'currency_amount:new_usd_banknote'],
            ],[
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->getBackKey(__FUNCTION__)],
            ]
        ]]);

        $this->sendOrEdit($chat_id, $message_id, $reply_markup, $text);

        return $this;
    }

    public function currency_amount(): static {
        if ($this->lastStep->key == 'currency_amount') {
            if (!is_numeric($this->message->text)) {
                $text = trans('telegram.errors.not_numeric');
            } elseif ($this->message->text < 1000) {
                $text = trans('telegram.errors.invalid_amount', ['amount' => 1000]);
            } else {
                return $this->cart();
            }
        }
        $this->order->syncSteps(__FUNCTION__, trans('telegram.order.currency_type'),  $this->getSelectedOptionName());
        $chat_id = $this->chatId;
        $message_id = $this->messageId;
        $text = $text ?? trans('telegram.message.amount');
        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->getBackKey(__FUNCTION__)],
            ]
        ]]);
        $this->sendOrEdit($chat_id, $message_id, $reply_markup, $text);

        return $this;
    }

    /**
     * @return $this
     * @throws TelegramSDKException
     */
    public function crypto_exchange(): static
    {
        $chat_id = $this->chatId;
        $message_id = $this->messageId;
        $text = trans('telegram.message.service_type');
        $this->order->syncSteps(__FUNCTION__, trans('telegram.order.service_type'), $this->getSelectedOptionName());

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.buy'), 'callback_data' => 'crypto_type'],
                ['text' =>  trans('telegram.button.sell'), 'callback_data' => 'crypto_type'],

            ], [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->getBackKey(__FUNCTION__)],
            ]
        ]]);
        $this->sendOrEdit($chat_id, $message_id, $reply_markup, $text);

        return $this;
    }

    /**
     * @return $this
     * @throws TelegramSDKException
     */
    public function crypto_type(): static
    {
        $chat_id = $this->chatId;
        $message_id = $this->messageId;
        $text = trans('telegram.message.crypto_type');
        $this->order->syncSteps(__FUNCTION__, trans('telegram.order.services'), $this->getSelectedOptionName());

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.btc'), 'callback_data' => 'crypto_amount'],
                ['text' =>  trans('telegram.button.eth'), 'callback_data' => 'crypto_amount'],
                ['text' =>  trans('telegram.button.usdt'), 'callback_data' => 'crypto_amount'],

            ], [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->getBackKey(__FUNCTION__)],
            ]
        ]]);
        $this->sendOrEdit($chat_id, $message_id, $reply_markup, $text);

        return $this;
    }

    /**
     * @return $this
     * @throws TelegramSDKException
     */
    public function crypto_amount(): static
    {
        if ($this->lastStep->current_key == 'crypto_amount') {
            if (!is_numeric($this->message->text)) {
                $text = trans('telegram.errors.not_numeric');
            } elseif ($this->message->text < 1000) {
                $text = trans('telegram.errors.invalid_amount', ['amount' => 1000]);
            } else {
                return $this->crypto_pay_type();
            }
        }
        $this->order->syncSteps(__FUNCTION__, trans('telegram.order.currency_type'),  $this->message->text);

        $chat_id = $this->chatId;
        $message_id = $this->messageId;
        $text = $text ?? trans('telegram.message.amount');
        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->getBackKey(__FUNCTION__)],
            ]
        ]]);
        $this->sendOrEdit($chat_id, $message_id, $reply_markup, $text);


        return $this;
    }

    public function crypto_city(): static
    {
        $chat_id = $this->chatId;
        $message_id = $this->messageId;
        $text = trans('telegram.message.city');
        $this->order->syncSteps(__FUNCTION__, trans('telegram.order.service_type'), $this->getSelectedOptionName());

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.moscow'), 'callback_data' => 'crypto_currency_type:moscow'],
                ['text' =>  trans('telegram.button.sevastopol'), 'callback_data' => 'crypto_currency_type:sevastopol'],
            ], [
                ['text' => trans('telegram.button.simferopol'), 'callback_data' => 'crypto_currency_type:simferopol'],
            ], [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->getBackKey(__FUNCTION__)],
            ]
        ]]);

        $this->sendOrEdit($chat_id, $message_id, $reply_markup, $text);

        return $this;
    }

    public function crypto_currency_type(): static
    {
        $chat_id = $this->chatId;
        $message_id = $this->messageId;
        $text = trans('telegram.message.crypto_currency_type');
        $this->order->syncSteps(__FUNCTION__, trans('telegram.order.service_type'), $this->getSelectedOptionName());

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.usd'), 'callback_data' => 'cart'],
                ['text' => trans('telegram.button.eur'), 'callback_data' => 'cart'],
                ['text' => trans('telegram.button.rub'), 'callback_data' => 'cart'],
            ], [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->getBackKey(__FUNCTION__)],
            ]
        ]]);

        $this->sendOrEdit($chat_id, $message_id, $reply_markup, $text);

        return $this;
    }

    public function crypto_pay_type(): static
    {
        $chat_id = $this->chatId;
        $message_id = $this->messageId;
        $text = trans('telegram.message.crypto_pay_type');
        $this->order->syncSteps(__FUNCTION__, trans('telegram.order.service_type'), $this->getSelectedOptionName());

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.cash'), 'callback_data' => 'crypto_city'],
                ['text' => trans('telegram.button.card'), 'callback_data' => 'crypto_bank'],

            ], [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->getBackKey(__FUNCTION__)],
            ]
        ]]);

        $this->sendOrEdit($chat_id, $message_id, $reply_markup, $text);

        return $this;
    }

    public function crypto_bank(): static
    {
        $chat_id = $this->chatId;
        $message_id = $this->messageId;
        $text = trans('telegram.message.crypto_bank');
        $this->order->syncSteps(__FUNCTION__, trans('telegram.order.crypto_pay_type'), $this->getSelectedOptionName());

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.sberbank'), 'callback_data' => 'cart'],
                ['text' => trans('telegram.button.tbank'), 'callback_data' => 'cart'],
                ['text' => trans('telegram.button.rnkb'), 'callback_data' => 'cart'],
            ], [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->getBackKey(__FUNCTION__)],
            ]
        ]]);

        $this->sendOrEdit($chat_id, $message_id, $reply_markup, $text);

        return $this;
    }

    public function getBackKey(string $activeKey) {
        $lastStepCurrentKey = $this->lastStep?->current_key ?? 'start';
        $lastStepPrevKey = $this->lastStep?->prev_key ?? 'start';
         return $lastStepCurrentKey == $activeKey ? $lastStepPrevKey : $lastStepCurrentKey;
    }
}
