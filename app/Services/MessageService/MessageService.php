<?php

namespace App\Services\MessageService;

use App\Models\Order;
use App\Models\OrderStep;
use App\Models\TelegramUser;
use App\Services\Exceptions\AlreadyRegisteredActionException;
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
    public ?Message $message;
    protected ?User $telegramUser;
    public ?TelegramUser $localTelegramUser;
    public ?int $messageId;
    public ?int $chatId;
    public ?string $key;
    protected bool $send = false;
    public ?Order $order = null;
    public ?OrderStep $lastStep = null;
    public string $defaultKey;

    public static array $actions = [];

    public function __construct()
    {
        $this->telegram = new Api();
        $this->response = $this->telegram->getWebhookUpdate();
        $this->defaultKey = 'home@start';
        $this->callbackQuery = $this->response->callbackQuery;
        $this->telegramUser = $this->callbackQuery?->from ?? $this->response->message?->from;
        $this->message = $this->response->message ?? $this->callbackQuery?->message;
        $this->messageId = $this->message?->messageId;
        $this->chatId = $this->message?->chat?->id;
        $this->key = $this->callbackQuery?->data ?? $this->message?->text;
    }

    /**
     * @throws AlreadyRegisteredActionException
     */
    public static function setActions(array $actions = [], bool $append = true): array
    {
        if (!$append) {
            self::$actions = [];
        }

        /** @var AbstractAction $action */
        foreach ($actions as $action) {
            self::addAction($action);
        }

        return self::getActions();
    }

    public static function getActions(): array
    {
        return self::$actions;
    }

    public static function addAction(AbstractAction $action): AbstractAction
    {
        if (isset(self::$actions[$action->getName()])) {
            throw new AlreadyRegisteredActionException(sprintf(
                "Action %s already registered",
                $action->getName()
            ));
        }

        return self::$actions[$action->getName()] = $action;
    }

    public static function getAction(string $name): ?AbstractAction
    {
        return self::$actions[$name] ?? null;
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
     * @return bool
     */
    public function executeMessage(): bool
    {
        return $this->executeMessageByKey($this->key ?? '') || $this->executeMessageByKey($this->lastStep?->current_key ?? '')
            || $this->executeMessageByKey($this->defaultKey ?? '');
    }

    /**
     * @param string $key
     * @return bool
     */
    public function executeMessageByKey(string $key): bool
    {
        $homeAction = self::getAction('home');
        $mapping = [
            trans('telegram.button.home') => $homeAction->getActionKey(),
            trans('telegram.button.individuals') => $homeAction->getActionKey('individuals'),
            trans('telegram.button.legal_entities') => $homeAction->getActionKey('legal_entities'),
            '/home' =>  $homeAction->getActionKey(),
            '/individuals' =>  $homeAction->getActionKey('individuals'),
            '/legalentities' =>  $homeAction->getActionKey('legal_entities'),
        ];

        $key = $mapping[$key] ?? $key;
        $keyArray = explode('@', $key);
        $actionName = $keyArray[0] ?? '';
        $action = self::getAction($actionName);
        $actionClass = $action ? get_class($action) : null;
        $methodName = explode(':', trim($keyArray[1] ?? '', '/'))[0];

        if ($actionClass && method_exists($actionClass, $methodName)) {
            $action->{$methodName}();
            return true;
        }

        return false;
    }

    /**
     * @param string $activeKey
     * @return string
     */
    public function getBackKey(string $activeKey): string
    {
        $activeKey = explode(':', $activeKey)[0];
        $lastStepCurrentKey = $this->lastStep?->current_key ?? $this->defaultKey;
        $lastStepPrevKey = $this->lastStep?->prev_key ?? $this->defaultKey;

        return $lastStepCurrentKey == $activeKey ? $lastStepPrevKey : $lastStepCurrentKey;
    }

    /**
     * @throws TelegramSDKException
     */
    public function receive(): static
    {
        $this->order = Order::getOrder($this->telegramUser);
        $this->setLastStep();
        $this->executeMessage();
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
