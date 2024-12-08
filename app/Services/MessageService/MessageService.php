<?php

namespace App\Services\MessageService;

use App\Models\Order;
use App\Models\OrderStep;
use App\Models\TelegramUser;
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
    public Api $telegram;
    protected Update $response;
    protected ?CallbackQuery $callbackQuery;
    protected ?Message $message;
    protected ?User $telegramUser;
    protected ?TelegramUser $localTelegramUser;
    protected ?int $messageId;
    public ?int $chatId;
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
     * @param TelegramUser $telegramUser
     * @return void
     */
    public function setLocalTelegramUser(TelegramUser $telegramUser): void
    {
        $this->localTelegramUser = $telegramUser;
    }

    /**
     * @return string
     */
    public function getSendMethod(): string
    {
        $mapping = [
            trans('telegram.button.home') => 'start',
            trans('telegram.button.individuals') => 'individuals',
            trans('telegram.button.legal_entities') => 'legal_entities',
            '/home' => 'start',
            '/individuals' => 'individuals',
            '/legalentities' => 'legal_entities',
        ];

        $key = $mapping[$this->key] ?? $this->key;
        $method = explode(':', trim($key, '/'))[0];
        $method = method_exists(self::class, $method) ? $method : null;

        $method = $method ?? explode(':', $this->lastStep?->current_key ?? '')[0];
        return method_exists(self::class, $method) ? $method : self::DEFAULT_KEY;
    }

    /**
     * @param string $activeKey
     * @return string
     */
    public function getBackKey(string $activeKey): string
    {
        $lastStepCurrentKey = $this->lastStep?->current_key ?? 'start';
        $lastStepPrevKey = $this->lastStep?->prev_key ?? 'start';
        return $lastStepCurrentKey == $activeKey ? $lastStepPrevKey : $lastStepCurrentKey;
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
        return $keyBoard['text'] ?? $this->message->text ?? '';
    }

    public function getSelectedOptionCallbackData(): mixed
    {
        $keyBoards = Arr::flatten($this->message->replyMarkup?->inline_keyboard ?? [],1);
        $keyBoard = collect($keyBoards)->where('callback_data', $this->key)->first();
        return $keyBoard['callback_data'] ?? '';
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
        $reply_markup = new Keyboard(
            [
                'inline_keyboard' => [
                    [
                        ['text' => trans('telegram.button.individuals'), 'callback_data' => 'individuals'],
                    ], [
                        ['text' => trans('telegram.button.legal_entities'), 'callback_data' => 'legal_entities'],
                    ]
                ],
            ]
        );
        $this->order->steps()->delete();
        $this->setMainKeyboard();
        $this->telegram->sendPhoto(compact('chat_id', 'photo', 'caption', 'reply_markup'));

        return $this;
    }

    /**
     * @throws TelegramSDKException
     */
    public function setMainKeyboard(): static
    {
        $chat_id = $this->chatId;
        $text = trans('telegram.message.start');
        $reply_markup = new Keyboard(
            [
                'keyboard' => [
                    [
                        ['text' => trans('telegram.button.home')],
                    ], [
                        ['text' => trans('telegram.button.individuals')],
                        ['text' => trans('telegram.button.legal_entities')],
                    ]
                ],
                'resize_keyboard' => true,
                'one_time_keyboard' => false,
            ]
        );
        $this->telegram->sendMessage(compact('chat_id', 'text', 'reply_markup'));

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
        $this->order->update(['type' => Order::TYPE_INDIVIDUALS]);
        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.currency_exchange'), 'callback_data' => 'currency_exchange'],
            ], [
                ['text' =>  trans('telegram.button.crypto_exchange'), 'callback_data' => 'crypto_exchange'],
            ], [
                ['text' =>  trans('telegram.button.international_transfers'), 'callback_data' => 'international_transfers'],
            ], [
                ['text' =>  trans('telegram.button.sailor_services'), 'callback_data' => 'sailor_services'],
            ], [
                ['text' =>  trans('telegram.button.financial_consulting'), 'callback_data' => 'financial_consulting'],
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
        $this->order->update(['type' => Order::TYPE_LEGAL_ENTITIES]);

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.payment_invoices'), 'callback_data' => 'payment_invoices_purpose'],
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

    /**
     * @return $this
     * @throws TelegramSDKException
     */
    public function cart(): static {
        $chat_id = $this->chatId;
        $message_id = $this->messageId;
        $this->order->syncSteps(__FUNCTION__, trans('telegram.order.' . $this->lastStep?->current_key ?? ''),  $this->getSelectedOptionName() ?: $this->message->text);
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
        $this->order->load('steps');
        $text = "Заявка #{$this->order->id}\n";
        $text .= "Фамилия: " . $this->localTelegramUser->last_name . "\n";
        $text .= "Имя: " . $this->localTelegramUser->first_name . "\n";
        $text .= "Ник: @" . $this->localTelegramUser->username . "\n";
        $caption = $text;
        $text .= $this->order->steps->filter(fn(OrderStep $step) => $step->value)->sortBy('id')
            ->map(fn (OrderStep $step) => $step->name . ' ' . $step->value)->implode("\n");
        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.completed'), 'callback_data' => 'completed'],
            ]
        ]]);
        $this->telegram->editMessageText(compact('chat_id','message_id', 'text'));
        $this->telegram->sendMessage(array_merge(compact('chat_id','message_id'), [
            'text' => "Ваша заявка подтверждена ждите ответа оператора"
        ]));

        $chat_id = '-4588641921';
        $this->telegram->sendMessage([
            'chat_id' => $chat_id,
            'text' => $text,
            'reply_markup' => $reply_markup,
        ]);
        if (count($this->order->file_ids ?? [])) {
            foreach ($this->order->file_ids as $type => $files) {
                if ($type == Order::FILE_TYPE_DOOCUMENT) {
                    foreach ($files as $file) {
                        $document = $file;
                        $this->telegram->sendDocument(compact('chat_id', 'document', 'caption'));
                    }
                }
                if ($type == Order::FILE_TYPE_PHOTO) {
                    foreach ($files as $file) {
                        $photo = $file;
                        $this->telegram->sendPhoto(compact('chat_id', 'photo', 'caption'));
                    }
                }
            }
        }

        $this->order->checkout();

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
                ['text' => trans('telegram.button.buy'), 'callback_data' => 'city:buy'],
                ['text' =>  trans('telegram.button.sell'), 'callback_data' => 'city:sell'],
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
                ['text' =>  trans('telegram.button.eur'), 'callback_data' => 'currency_amount'],
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

        if ($this->lastStep->current_key == __FUNCTION__) {
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
        if ($this->lastStep->current_key == __FUNCTION__) {
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
                ['text' => trans('telegram.button.btc'), 'callback_data' => 'crypto_amount:btc'],
                ['text' =>  trans('telegram.button.eth'), 'callback_data' => 'crypto_amount:eth'],
                ['text' =>  trans('telegram.button.usdt'), 'callback_data' => 'crypto_amount:usdt'],

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
        if ($this->lastStep->current_key == __FUNCTION__) {
            if (!is_numeric($this->message->text)) {
                $text = trans('telegram.errors.not_numeric');
            } elseif ($this->message->text < 1000) {
                $text = trans('telegram.errors.invalid_amount', ['amount' => 1000]);
            } else {
                return $this->crypto_pay_type();
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

    public function crypto_city(): static
    {
        $chat_id = $this->chatId;
        $message_id = $this->messageId;
        $text = trans('telegram.message.city');
        $this->order->syncSteps(__FUNCTION__, trans('telegram.order.crypto_pay_type'), $this->getSelectedOptionName());

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
        $this->order->syncSteps(__FUNCTION__, trans('telegram.order.city'), $this->getSelectedOptionName());

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.usd'), 'callback_data' => 'cart:usd'],
                ['text' => trans('telegram.button.eur'), 'callback_data' => 'cart:eur'],
                ['text' => trans('telegram.button.rub'), 'callback_data' => 'cart:rub'],
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
    public function crypto_pay_type(): static
    {
        $chat_id = $this->chatId;
        $message_id = $this->messageId;
        $text = trans('telegram.message.crypto_pay_type');
        $this->order->syncSteps(__FUNCTION__, trans('telegram.order.amount'), $this->message->text);

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

    /**
     * @return $this
     * @throws TelegramSDKException
     */
    public function international_transfers(): static
    {
        $chat_id = $this->chatId;
        $message_id = $this->messageId;
        $text = trans('telegram.message.international_transfers');
        $this->order->syncSteps(__FUNCTION__, trans('telegram.order.services'), $this->getSelectedOptionName());

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.international_cache_transfer'), 'callback_data' => 'international_cache_transfer_direction'],
            ], [
                ['text' => trans('telegram.button.international_sepa_transfer'), 'callback_data' => 'international_sepa_transfer_direction'],
                ['text' => trans('telegram.button.international_swift_transfer'), 'callback_data' => 'international_swift_transfer_direction'],
            ], [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->getBackKey(__FUNCTION__)],
            ]
        ]]);

        $this->sendOrEdit($chat_id, $message_id, $reply_markup, $text);

        return $this;
    }

    public function international_cache_transfer_direction(): static
    {
        $chat_id = $this->chatId;
        $message_id = $this->messageId;
        $text = trans('telegram.message.international_transfer_direction');
        $this->order->syncSteps(__FUNCTION__, trans('telegram.order.international_transfers'), $this->getSelectedOptionName());

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.from_rf'), 'callback_data' => 'international_cache_transfer_city'],
                ['text' => trans('telegram.button.to_rf'), 'callback_data' => 'international_cache_transfer_city'],
            ], [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->getBackKey(__FUNCTION__)],
            ]
        ]]);

        $this->sendOrEdit($chat_id, $message_id, $reply_markup, $text);

        return $this;
    }

    public function international_cache_transfer_city(): static
    {
        $chat_id = $this->chatId;
        $message_id = $this->messageId;
        $text = trans('telegram.message.city');
        $this->order->syncSteps(__FUNCTION__, trans('telegram.order.international_transfer_direction'), $this->getSelectedOptionName());

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.moscow'), 'callback_data' => 'international_cache_transfer_amount:moscow'],
                ['text' =>  trans('telegram.button.sevastopol'), 'callback_data' => 'international_cache_transfer_amount:sevastopol'],
            ], [
                ['text' => trans('telegram.button.simferopol'), 'callback_data' => 'international_cache_transfer_amount:simferopol'],
            ], [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->getBackKey(__FUNCTION__)],
            ]
        ]]);

        $this->sendOrEdit($chat_id, $message_id, $reply_markup, $text);

        return $this;
    }

    public function international_cache_transfer_amount(): static
    {
        if ($this->lastStep->current_key == __FUNCTION__) {
            if (!is_numeric($this->message->text)) {
                $text = trans('telegram.errors.not_numeric');
            } elseif ($this->message->text < 1000) {
                $text = trans('telegram.errors.invalid_amount', ['amount' => 1000]);
            } else {
                return $this->international_cache_transfer_recipient_city();
            }
        }
        $this->order->syncSteps(__FUNCTION__, trans('telegram.order.city'),  $this->getSelectedOptionName());

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

    public function international_cache_transfer_recipient_city(): static
    {
        if ($this->lastStep->current_key == __FUNCTION__) {
            if (strlen($this->message->text) < 3 || strlen($this->message->text) > 200) {
                $text = trans('telegram.errors.str_length');
            } else {
                return $this->international_cache_transfer_currency();
            }
        }
        $this->order->syncSteps(__FUNCTION__, trans('telegram.order.amount'),  $this->message->text);

        $chat_id = $this->chatId;
        $message_id = $this->messageId;
        $text = $text ?? trans('telegram.message.international_transfer_recipient_city');
        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->getBackKey(__FUNCTION__)],
            ]
        ]]);
        $this->sendOrEdit($chat_id, $message_id, $reply_markup, $text);


        return $this;
    }


    public function international_cache_transfer_currency(): static
    {
        $chat_id = $this->chatId;
        $message_id = $this->messageId;
        $text = trans('telegram.message.currency');
        $this->order->syncSteps(__FUNCTION__, trans('telegram.order.recipient_city'),  $this->message->text);

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.usd'), 'callback_data' => 'cart:usd'],
                ['text' =>  trans('telegram.button.eur'), 'callback_data' => 'cart:eur'],
                ['text' =>  trans('telegram.button.rub'), 'callback_data' => 'cart:rub'],
                ['text' =>  trans('telegram.button.custom_currency'), 'callback_data' => 'international_cache_transfer_custom_currency'],
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
    public function international_cache_transfer_custom_currency(): static
    {
        if ($this->lastStep->current_key == __FUNCTION__) {
            if (strlen($this->message->text) < 3 || strlen($this->message->text) > 200) {
                $text = trans('telegram.errors.str_length');
            } else {
                return $this->cart();
            }
        }
        $this->order->syncSteps(__FUNCTION__, trans('telegram.order.amount'),  $this->message->text);

        $chat_id = $this->chatId;
        $message_id = $this->messageId;
        $text = $text ?? trans('telegram.message.custom_currency');

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->getBackKey(__FUNCTION__)],
            ]
        ]]);

        $this->sendOrEdit($chat_id, $message_id, $reply_markup, $text);

        return $this;
    }

    public function international_sepa_transfer_direction(): static
    {
        $chat_id = $this->chatId;
        $message_id = $this->messageId;
        $text = trans('telegram.message.international_transfer_direction');
        $this->order->syncSteps(__FUNCTION__, trans('telegram.order.international_transfers'), $this->getSelectedOptionName());

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.from_rf'), 'callback_data' => 'international_sepa_transfer_pay_system'],
                ['text' => trans('telegram.button.to_rf'), 'callback_data' => 'international_sepa_transfer_pay_system'],
            ], [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->getBackKey(__FUNCTION__)],
            ]
        ]]);

        $this->sendOrEdit($chat_id, $message_id, $reply_markup, $text);

        return $this;
    }

    public function international_sepa_transfer_pay_system(): static
    {
        $chat_id = $this->chatId;
        $message_id = $this->messageId;
        $text = trans('telegram.message.international_transfer_pay_system');
        $this->order->syncSteps(__FUNCTION__, trans('telegram.order.international_transfer_direction'), $this->getSelectedOptionName());

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.wise_revolut'), 'callback_data' => 'international_sepa_transfer_currency'],
                ['text' => trans('telegram.button.visa_mastercard'), 'callback_data' => 'international_sepa_transfer_currency'],
            ], [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->getBackKey(__FUNCTION__)],
            ]
        ]]);

        $this->sendOrEdit($chat_id, $message_id, $reply_markup, $text);

        return $this;
    }

    public function international_sepa_transfer_currency(): static
    {
        $chat_id = $this->chatId;
        $message_id = $this->messageId;
        $text = trans('telegram.message.currency');
        $this->order->syncSteps(__FUNCTION__, trans('telegram.order.pay_system'),  $this->getSelectedOptionName());

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.usd'), 'callback_data' => 'international_sepa_transfer_amount:usd'],
                ['text' =>  trans('telegram.button.eur'), 'callback_data' => 'international_sepa_transfer_amount:eur'],
                ['text' =>  trans('telegram.button.rub'), 'callback_data' => 'international_sepa_transfer_amount:rub'],
                ['text' =>  trans('telegram.button.custom_currency'), 'callback_data' => 'international_sepa_transfer_custom_currency'],
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
    public function international_sepa_transfer_custom_currency(): static
    {
        if ($this->lastStep->current_key == __FUNCTION__) {
            if (strlen($this->message->text) < 3 || strlen($this->message->text) > 200) {
                $text = trans('telegram.errors.str_length');
            } else {
                return $this->international_sepa_transfer_amount();
            }
        }
        $this->order->syncSteps(__FUNCTION__, trans('telegram.order.currency'),  $this->getSelectedOptionName() ?? $this->message->text);

        $chat_id = $this->chatId;
        $message_id = $this->messageId;
        $text = $text ?? trans('telegram.message.custom_currency');

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->getBackKey(__FUNCTION__)],
            ]
        ]]);

        $this->sendOrEdit($chat_id, $message_id, $reply_markup, $text);

        return $this;
    }

    public function international_sepa_transfer_amount(): static
    {
        if ($this->lastStep->current_key == __FUNCTION__) {
            if (!is_numeric($this->message->text)) {
                $text = trans('telegram.errors.not_numeric');
            } elseif ($this->message->text < 1000) {
                $text = trans('telegram.errors.invalid_amount', ['amount' => 1000]);
            } else {
                return $this->international_sepa_transfer_transaction_type();
            }
        }
        $this->order->syncSteps(__FUNCTION__, trans('telegram.order.currency'),  $this->getSelectedOptionName() ?: $this->message->text);

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

    public function international_sepa_transfer_transaction_type(): static
    {
        $chat_id = $this->chatId;
        $message_id = $this->messageId;
        $text = trans('telegram.message.international_transfer_transaction_type');
        $this->order->syncSteps(__FUNCTION__, trans('telegram.order.amount'),  $this->message->text);

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.transaction_in_office'), 'callback_data' => 'cart'],
                ['text' =>  trans('telegram.button.transaction_by_card'), 'callback_data' => 'cart'],
            ], [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->getBackKey(__FUNCTION__)],
            ]
        ]]);

        $this->sendOrEdit($chat_id, $message_id, $reply_markup, $text);

        return $this;
    }

    public function international_swift_transfer_direction(): static
    {
        $chat_id = $this->chatId;
        $message_id = $this->messageId;
        $text = trans('telegram.message.international_transfer_direction');
        $this->order->syncSteps(__FUNCTION__, trans('telegram.order.international_transfers'), $this->getSelectedOptionName());

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.from_rf'), 'callback_data' => 'international_swift_transfer_pay_system'],
                ['text' => trans('telegram.button.to_rf'), 'callback_data' => 'international_swift_transfer_pay_system'],
            ], [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->getBackKey(__FUNCTION__)],
            ]
        ]]);

        $this->sendOrEdit($chat_id, $message_id, $reply_markup, $text);

        return $this;
    }

    public function international_swift_transfer_pay_system(): static
    {
        $chat_id = $this->chatId;
        $message_id = $this->messageId;
        $text = trans('telegram.message.international_transfer_pay_system');
        $this->order->syncSteps(__FUNCTION__, trans('telegram.order.international_transfer_direction'), $this->getSelectedOptionName());

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.wise_revolut'), 'callback_data' => 'international_swift_transfer_currency'],
                ['text' => trans('telegram.button.visa_mastercard'), 'callback_data' => 'international_swift_transfer_currency'],
            ], [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->getBackKey(__FUNCTION__)],
            ]
        ]]);

        $this->sendOrEdit($chat_id, $message_id, $reply_markup, $text);

        return $this;
    }

    public function international_swift_transfer_currency(): static
    {
        $chat_id = $this->chatId;
        $message_id = $this->messageId;
        $text = trans('telegram.message.currency');
        $this->order->syncSteps(__FUNCTION__, trans('telegram.order.pay_system'),  $this->getSelectedOptionName());

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.usd'), 'callback_data' => 'international_swift_transfer_amount:usd'],
                ['text' =>  trans('telegram.button.eur'), 'callback_data' => 'international_swift_transfer_amount:eur'],
                ['text' =>  trans('telegram.button.rub'), 'callback_data' => 'international_swift_transfer_amount:rub'],
                ['text' =>  trans('telegram.button.custom_currency'), 'callback_data' => 'international_sepa_transfer_custom_currency'],
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
    public function international_swift_transfer_custom_currency(): static
    {
        if ($this->lastStep->current_key == __FUNCTION__) {
            if (strlen($this->message->text) < 3 || strlen($this->message->text) > 200) {
                $text = trans('telegram.errors.str_length');
            } else {
                return $this->international_swift_transfer_amount();
            }
        }
        $this->order->syncSteps(__FUNCTION__, trans('telegram.order.pay_system'),  $this->getSelectedOptionName());

        $chat_id = $this->chatId;
        $message_id = $this->messageId;
        $text = $text ?? trans('telegram.message.custom_currency');

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->getBackKey(__FUNCTION__)],
            ]
        ]]);

        $this->sendOrEdit($chat_id, $message_id, $reply_markup, $text);

        return $this;
    }

    public function international_swift_transfer_amount(): static
    {
        if ($this->lastStep->current_key == __FUNCTION__) {
            if (!is_numeric($this->message->text)) {
                $text = trans('telegram.errors.not_numeric');
            } elseif ($this->message->text < 1000) {
                $text = trans('telegram.errors.invalid_amount', ['amount' => 1000]);
            } else {
                return $this->international_swift_transfer_transaction_type();
            }
        }
        $this->order->syncSteps(__FUNCTION__, trans('telegram.order.currency'),  $this->getSelectedOptionName() ?: $this->message->text);

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

    public function international_swift_transfer_transaction_type(): static
    {
        $chat_id = $this->chatId;
        $message_id = $this->messageId;
        $text = trans('telegram.message.international_transfer_transaction_type');
        $this->order->syncSteps(__FUNCTION__, trans('telegram.order.amount'),  $this->message->text);

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.transaction_in_office'), 'callback_data' => 'cart:in_office'],
                ['text' =>  trans('telegram.button.transaction_by_card'), 'callback_data' => 'cart:by_card'],
            ], [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->getBackKey(__FUNCTION__)],
            ]
        ]]);

        $this->sendOrEdit($chat_id, $message_id, $reply_markup, $text);

        return $this;
    }

    public function sailor_services(): static
    {
        $chat_id = $this->chatId;
        $message_id = $this->messageId;
        $text = trans('telegram.message.services');
        $this->order->syncSteps(__FUNCTION__, trans('telegram.order.services'),  $this->getSelectedOptionName());

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.accept_foreign_currency_payment'), 'callback_data' => 'sailor_accept_foreign_currency_payment'],
            ], [
                ['text' =>  trans('telegram.button.currency_exchange'), 'callback_data' => 'currency_exchange'],
            ], [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->getBackKey(__FUNCTION__)],
            ]
        ]]);

        $this->sendOrEdit($chat_id, $message_id, $reply_markup, $text);

        return $this;
    }

    public function sailor_accept_foreign_currency_payment(): static
    {
        $chat_id = $this->chatId;
        $message_id = $this->messageId;
        $text = trans('telegram.message.pay_type');
        $this->order->syncSteps(__FUNCTION__, trans('telegram.order.services'),  $this->getSelectedOptionName());

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.ship_money'), 'callback_data' => 'sailor_currency:ship_money'],
                ['text' =>  trans('telegram.button.mar_trust'), 'callback_data' => 'sailor_currency:mar_trust'],
                ['text' =>  trans('telegram.button.custom_option'), 'callback_data' => 'custom_sailor_pay_type'],
            ], [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->getBackKey(__FUNCTION__)],
            ]
        ]]);

        $this->sendOrEdit($chat_id, $message_id, $reply_markup, $text);

        return $this;
    }

    public function sailor_currency(): static
    {
        $chat_id = $this->chatId;
        $message_id = $this->messageId;
        $text = trans('telegram.message.currency');
        $this->order->syncSteps(__FUNCTION__, trans('telegram.order.pay_type'),  $this->getSelectedOptionName());

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.usd'), 'callback_data' => 'sailor_amount:usd'],
                ['text' =>  trans('telegram.button.eur'), 'callback_data' => 'sailor_amount:eur'],
                ['text' =>  trans('telegram.button.rub'), 'callback_data' => 'sailor_amount:rub'],
                ['text' =>  trans('telegram.button.custom_currency'), 'callback_data' => 'sailor_custom_currency'],
            ], [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->getBackKey(__FUNCTION__)],
            ]
        ]]);

        $this->sendOrEdit($chat_id, $message_id, $reply_markup, $text);

        return $this;
    }

    public function sailor_custom_currency(): static
    {
        if ($this->lastStep->current_key == __FUNCTION__) {
            if (strlen($this->message->text) < 3 || strlen($this->message->text) > 200) {
                $text = trans('telegram.errors.str_length');
            } else {
                return $this->sailor_amount();
            }
        }
        $this->order->syncSteps(__FUNCTION__, trans('telegram.order.currency'),  $this->getSelectedOptionName());

        $chat_id = $this->chatId;
        $message_id = $this->messageId;
        $text = $text ?? trans('telegram.message.custom_currency');

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->getBackKey(__FUNCTION__)],
            ]
        ]]);

        $this->sendOrEdit($chat_id, $message_id, $reply_markup, $text);

        return $this;
    }

    public function sailor_amount(): static {
        if ($this->lastStep->current_key == __FUNCTION__) {
            if (!is_numeric($this->message->text)) {
                $text = trans('telegram.errors.not_numeric');
            } elseif ($this->message->text < 1000) {
                $text = trans('telegram.errors.invalid_amount', ['amount' => 1000]);
            } else {
                return $this->sailor_receipt_type();
            }
        }
        $this->order->syncSteps(__FUNCTION__, trans('telegram.order.currency'),  $this->getSelectedOptionName());
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

    public function sailor_receipt_type(): static
    {
        $chat_id = $this->chatId;
        $message_id = $this->messageId;
        $text = trans('telegram.message.receipt_type');
        $this->order->syncSteps(__FUNCTION__, trans('telegram.order.amount'),  $this->getSelectedOptionName());

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.office'), 'callback_data' => 'cart:sailor_office_receipt_type'],
                ['text' =>  trans('telegram.button.cart'), 'callback_data' => 'cart:sailor_cart_receipt_type'],
                ['text' =>  trans('telegram.button.custom_option'), 'callback_data' => 'sailor_custom_receipt_type'],
            ], [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->getBackKey(__FUNCTION__)],
            ]
        ]]);

        $this->sendOrEdit($chat_id, $message_id, $reply_markup, $text);

        return $this;
    }

    public function sailor_custom_receipt_type(): static
    {
        if ($this->lastStep->current_key == __FUNCTION__) {
            if (strlen($this->message->text) < 3 || strlen($this->message->text) > 200) {
                $text = trans('telegram.errors.str_length');
            } else {
                return $this->cart();
            }
        }
        $this->order->syncSteps(__FUNCTION__, trans('telegram.order.receipt_type'),  $this->getSelectedOptionName());

        $chat_id = $this->chatId;
        $message_id = $this->messageId;
        $text = $text ?? trans('telegram.message.custom_receipt_type');

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->getBackKey(__FUNCTION__)],
            ]
        ]]);

        $this->sendOrEdit($chat_id, $message_id, $reply_markup, $text);

        return $this;
    }

    public function custom_sailor_pay_type(): static
    {
        if ($this->lastStep->current_key == __FUNCTION__) {
            if (strlen($this->message->text) < 3 || strlen($this->message->text) > 200) {
                $text = trans('telegram.errors.str_length');
            } else {
                return $this->sailor_currency();
            }
        }
        $this->order->syncSteps(__FUNCTION__, trans('telegram.order.pay_type'),  $this->getSelectedOptionName());

        $chat_id = $this->chatId;
        $message_id = $this->messageId;
        $text = $text ?? trans('telegram.message.custom_pay_type');

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->getBackKey(__FUNCTION__)],
            ]
        ]]);

        $this->sendOrEdit($chat_id, $message_id, $reply_markup, $text);

        return $this;
    }

    public function financial_consulting(): static
    {
        $chat_id = $this->chatId;
        $message_id = $this->messageId;
        $text = trans('telegram.message.service_type');
        $this->order->syncSteps(__FUNCTION__, trans('telegram.order.services'),  $this->getSelectedOptionName());

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.relocation'), 'callback_data' => 'relocation'],
            ], [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->getBackKey(__FUNCTION__)],
            ]
        ]]);

        $this->sendOrEdit($chat_id, $message_id, $reply_markup, $text);

        return $this;
    }

    public function relocation(): static
    {
        $chat_id = $this->chatId;
        $message_id = $this->messageId;
        $text = trans('telegram.message.service_type');
        $this->order->syncSteps(__FUNCTION__, trans('telegram.order.service_type'),  $this->getSelectedOptionName());

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.vng'), 'callback_data' => 'vng_location'],
            ], [
                ['text' => trans('telegram.button.financial_resource_legalization'), 'callback_data' => 'financial_resource_legalization'],
            ], [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->getBackKey(__FUNCTION__)],
            ]
        ]]);

        $this->sendOrEdit($chat_id, $message_id, $reply_markup, $text);

        return $this;
    }

    public function vng_location(): static
    {
        if ($this->lastStep->current_key == __FUNCTION__) {
            if (strlen($this->message->text) < 3 || strlen($this->message->text) > 200) {
                $text = trans('telegram.errors.str_length');
            } else {
                return $this->cart();
            }
        }
        $this->order->syncSteps(__FUNCTION__, trans('telegram.order.service_type'),  $this->getSelectedOptionName());

        $chat_id = $this->chatId;
        $message_id = $this->messageId;
        $text = $text ?? trans('telegram.message.country_city');

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->getBackKey(__FUNCTION__)],
            ]
        ]]);

        $this->sendOrEdit($chat_id, $message_id, $reply_markup, $text);

        return $this;
    }

    public function financial_resource_legalization(): static
    {
        if ($this->lastStep->current_key == __FUNCTION__) {
            if (strlen($this->message->text) < 3 || strlen($this->message->text) > 200) {
                $text = trans('telegram.errors.str_length');
            } else {
                return $this->financial_resource_legalization_aim();
            }
        }
        $this->order->syncSteps(__FUNCTION__, trans('telegram.order.service_type'),  $this->getSelectedOptionName());

        $chat_id = $this->chatId;
        $message_id = $this->messageId;
        $text = $text ?? trans('telegram.message.country_city');

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->getBackKey(__FUNCTION__)],
            ]
        ]]);

        $this->sendOrEdit($chat_id, $message_id, $reply_markup, $text);

        return $this;
    }

    public function financial_resource_legalization_aim(): static
    {
        if ($this->lastStep->current_key == __FUNCTION__) {
            if (strlen($this->message->text) < 3 || strlen($this->message->text) > 200) {
                $text = trans('telegram.errors.str_length');
            } else {
                return $this->financial_resource_legalization_amount();
            }
        }
        $this->order->syncSteps(__FUNCTION__, trans('telegram.order.country_city'),  $this->getSelectedOptionName());

        $chat_id = $this->chatId;
        $message_id = $this->messageId;
        $text = $text ?? trans('telegram.message.financial_resource_legalization_aim');

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->getBackKey(__FUNCTION__)],
            ]
        ]]);

        $this->sendOrEdit($chat_id, $message_id, $reply_markup, $text);

        return $this;
    }

    public function financial_resource_legalization_amount(): static {
        if ($this->lastStep->current_key == __FUNCTION__) {
            if (!is_numeric($this->message->text)) {
                $text = trans('telegram.errors.not_numeric');
            } elseif ($this->message->text < 1000) {
                $text = trans('telegram.errors.invalid_amount', ['amount' => 1000]);
            } else {
                return $this->financial_resource_legalization_currency();
            }
        }
        $this->order->syncSteps(__FUNCTION__, trans('telegram.order.financial_resource_legalization_aim'),  $this->getSelectedOptionName());
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

    public function financial_resource_legalization_currency(): static
    {
        $chat_id = $this->chatId;
        $message_id = $this->messageId;
        $text = trans('telegram.message.currency');
        $this->order->syncSteps(__FUNCTION__, trans('telegram.order.amount'),  $this->message->text);

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.usd'), 'callback_data' => 'financial_resource_legalization_receipt_location:usd'],
                ['text' =>  trans('telegram.button.eur'), 'callback_data' => 'financial_resource_legalization_receipt_location:eur'],
                ['text' =>  trans('telegram.button.rub'), 'callback_data' => 'financial_resource_legalization_receipt_location:rub'],
                ['text' =>  trans('telegram.button.custom_currency'), 'callback_data' => 'financial_resource_legalization_custom_currency'],
            ], [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->getBackKey(__FUNCTION__)],
            ]
        ]]);

        $this->sendOrEdit($chat_id, $message_id, $reply_markup, $text);

        return $this;
    }

    public function financial_resource_legalization_custom_currency(): static
    {
        if ($this->lastStep->current_key == __FUNCTION__) {
            if (strlen($this->message->text) < 3 || strlen($this->message->text) > 200) {
                $text = trans('telegram.errors.str_length');
            } else {
                return $this->financial_resource_legalization_receipt_location();
            }
        }
        $this->order->syncSteps(__FUNCTION__, trans('telegram.order.currency_type'),  $this->getSelectedOptionName());
        $chat_id = $this->chatId;
        $message_id = $this->messageId;
        $text = $text ?? trans('telegram.message.custom_currency');
        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->getBackKey(__FUNCTION__)],
            ]
        ]]);
        $this->sendOrEdit($chat_id, $message_id, $reply_markup, $text);

        return $this;
    }

    public function financial_resource_legalization_receipt_location(): static {
        if ($this->lastStep->current_key == __FUNCTION__) {
            if (strlen($this->message->text) < 3 || strlen($this->message->text) > 200) {
                $text = trans('telegram.errors.str_length');
            } else {
                return $this->cart();
            }
        }
        $this->order->syncSteps(__FUNCTION__, trans('telegram.order.currency'),  $this->getSelectedOptionName());
        $chat_id = $this->chatId;
        $message_id = $this->messageId;
        $text = $text ?? trans('telegram.message.financial_resource_legalization_receipt_location');
        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->getBackKey(__FUNCTION__)],
            ]
        ]]);
        $this->sendOrEdit($chat_id, $message_id, $reply_markup, $text);

        return $this;
    }

    /**
     * @return $this|MessageService
     * @throws TelegramSDKException
     */
    public function payment_invoices_purpose(): MessageService|static
    {
        if ($this->lastStep->current_key == __FUNCTION__) {
            if (strlen($this->message->text) < 3 || strlen($this->message->text) > 200) {
                $text = trans('telegram.errors.str_length');
            } else {
                return $this->payment_invoices_receipt_location();
            }
        }
        $this->order->syncSteps(__FUNCTION__, trans('telegram.order.services'),  $this->getSelectedOptionName());
        $chat_id = $this->chatId;
        $message_id = $this->messageId;
        $text = $text ?? trans('telegram.message.payment_invoices_purpose');
        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->getBackKey(__FUNCTION__)],
            ]
        ]]);
        $this->sendOrEdit($chat_id, $message_id, $reply_markup, $text);

        return $this;
    }

    public function payment_invoices_receipt_location(): MessageService|static
    {
        if ($this->lastStep->current_key == __FUNCTION__) {
            if (strlen($this->message->text) < 3 || strlen($this->message->text) > 200) {
                $text = trans('telegram.errors.str_length');
            } else {
                return $this->payment_invoices_receipt_amount();
            }
        }
        $this->order->syncSteps(__FUNCTION__, trans('telegram.order.payment_invoices_purpose'),  $this->getSelectedOptionName());
        $chat_id = $this->chatId;
        $message_id = $this->messageId;
        $text = $text ?? trans('telegram.message.payment_invoices_receipt_location');
        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->getBackKey(__FUNCTION__)],
            ]
        ]]);
        $this->sendOrEdit($chat_id, $message_id, $reply_markup, $text);

        return $this;
    }

    public function payment_invoices_receipt_amount(): static {
        if ($this->lastStep->current_key == __FUNCTION__) {
            if (!is_numeric($this->message->text)) {
                $text = trans('telegram.errors.not_numeric');
            } elseif ($this->message->text < 1000) {
                $text = trans('telegram.errors.invalid_amount', ['amount' => 1000]);
            } else {
                return $this->payment_invoices_receipt_currency();
            }
        }
        $this->order->syncSteps(__FUNCTION__, trans('telegram.order.payment_invoices_receipt_location'),  $this->getSelectedOptionName());
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

    public function payment_invoices_receipt_currency(): static
    {
        $chat_id = $this->chatId;
        $message_id = $this->messageId;
        $text = trans('telegram.message.currency');
        $this->order->syncSteps(__FUNCTION__, trans('telegram.order.amount'),  $this->message->text);

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.usd'), 'callback_data' => 'payment_invoices_city:usd'],
                ['text' =>  trans('telegram.button.eur'), 'callback_data' => 'payment_invoices_city:eur'],
                ['text' =>  trans('telegram.button.cny'), 'callback_data' => 'payment_invoices_city:cny'],
                ['text' =>  trans('telegram.button.custom_currency'), 'callback_data' => 'payment_invoices_custom_currency'],
            ], [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->getBackKey(__FUNCTION__)],
            ]
        ]]);

        $this->sendOrEdit($chat_id, $message_id, $reply_markup, $text);

        return $this;
    }

    public function payment_invoices_custom_currency(): static
    {
        if ($this->lastStep->current_key == __FUNCTION__) {
            if (strlen($this->message->text) < 3 || strlen($this->message->text) > 200) {
                $text = trans('telegram.errors.str_length');
            } else {
                return $this->payment_invoices_city();
            }
        }
        $this->order->syncSteps(__FUNCTION__, trans('telegram.order.currency_type'),  $this->message->text);

        $chat_id = $this->chatId;
        $message_id = $this->messageId;
        $text = $text ?? trans('telegram.message.custom_currency');

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->getBackKey(__FUNCTION__)],
            ]
        ]]);

        $this->sendOrEdit($chat_id, $message_id, $reply_markup, $text);

        return $this;
    }

    public function payment_invoices_city(): static
    {
        $chat_id = $this->chatId;
        $message_id = $this->messageId;
        $text = trans('telegram.message.payment_invoices_city');
        $this->order->syncSteps(__FUNCTION__, trans('telegram.order.currency'), $this->getSelectedOptionName());

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.moscow'), 'callback_data' => 'payment_invoices_file:moscow'],
                ['text' =>  trans('telegram.button.sevastopol'), 'callback_data' => 'payment_invoices_file:sevastopol'],
            ], [
                ['text' => trans('telegram.button.simferopol'), 'callback_data' => 'payment_invoices_file:simferopol'],
            ], [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->getBackKey(__FUNCTION__)],
            ]
        ]]);

        $this->sendOrEdit($chat_id, $message_id, $reply_markup, $text);

        return $this;
    }

    public function payment_invoices_file(): static
    {
        $chat_id = $this->chatId;
        $message_id = $this->messageId;
        $text = trans('telegram.message.payment_invoices_file');
        $this->order->syncSteps(__FUNCTION__, trans('telegram.order.currency'), $this->getSelectedOptionName());

        $result = $this->saveFiles();
        $text = $result ? trans('telegram.message.success_file') : $text;

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.forward'), 'callback_data' => 'cart'],
            ], [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->getBackKey(__FUNCTION__)],
            ]
        ]]);

        $this->sendOrEdit($chat_id, $message_id, $reply_markup, $text);
        return $this;
    }

    public function saveFiles()
    {
        $result = false;
        if ($this->message->hasAny([Order::FILE_TYPE_DOOCUMENT, Order::FILE_TYPE_PHOTO])) {
            if ($this->message->has(Order::FILE_TYPE_DOOCUMENT)) {
                $file_ids = $this->order->file_ids ?? [];
                if (!isset($file_ids[Order::FILE_TYPE_DOOCUMENT])) {
                    $file_ids[Order::FILE_TYPE_DOOCUMENT] = [];
                }

                $file_ids[Order::FILE_TYPE_DOOCUMENT][] = $this->message->document->get('file_id');
                $file_ids[Order::FILE_TYPE_DOOCUMENT] = array_unique(array_filter($file_ids[Order::FILE_TYPE_DOOCUMENT], fn($item) => $item));
                $this->order->update(compact('file_ids'));
            }

            if ($this->message->has(Order::FILE_TYPE_PHOTO)) {
                $file_ids = $this->order->file_ids ?? [];
                if (!isset($file_ids[Order::FILE_TYPE_PHOTO])) {
                    $file_ids[Order::FILE_TYPE_PHOTO] = [];
                }

                $photo = $this->message->photo[count($this->message->photo) - 1];
                $file_ids[Order::FILE_TYPE_PHOTO][] = $photo['file_id'] ?? null;
                $file_ids[Order::FILE_TYPE_PHOTO] = array_unique(array_filter($file_ids[Order::FILE_TYPE_PHOTO], fn($item) => $item));
                $this->order->update(compact('file_ids'));
            }
            $result = true;
        }

        return $result;
    }

}
