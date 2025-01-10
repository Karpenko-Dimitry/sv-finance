<?php

namespace App\Services\MessageService;

use App\Models\ChatMember;
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
    const MANAGER_URL = 'https://t.me/SuperVisor_Finance';
    public Api $telegram;
    public Update $response;
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
            '/start' =>  $homeAction->getActionKey(),
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
        $data = $this->response->chatMember?->chat ?? $this->response->channelPost?->chat;
        ChatMember::makeNew($data?->toArray());

        if (!$this->telegramUser) {
            return $this;
        }

        $this->order = Order::getOrder($this->telegramUser, $this->chatId);
        $this->setLastStep();
        try {
            $this->executeMessage();
        } catch (\Throwable $exception) {
            $message = $exception->getMessage();
            $code = $exception->getCode();
            log_debug('ERROR MessageService ' . ($this->key ?? ''), compact('code', 'message'));
        }

        $this->callbackQuery && $this->telegram->answerCallbackQuery(['callback_query_id' => $this->callbackQuery->id]);

        return $this;
    }

    /**
     * @return $this
     */
    public function setLastStep(?OrderStep $step = null): static
    {
        $this->lastStep = $step ?? $this->order?->getLastStep();

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
            try {
                $this->telegram->deleteMessage(compact('chat_id', 'message_id'));
                $this->telegram->deleteMessage(['chat_id' => $chat_id, 'message_id' => $this->lastStep->message_id ?? '']);
            } catch (\Throwable $exception) {}

            $message = $this->telegram->sendMessage(compact('chat_id', 'text', 'reply_markup'));
            $this->lastStep->update(['message_id' => $message->messageId]);

        } else {
            $text && $this->telegram->editMessageText(compact('chat_id','message_id', 'text'));
            $this->telegram->editMessageReplyMarkup(compact('chat_id','message_id', 'reply_markup'));
        }
    }

    /**
     * @return mixed|null
     */
    public function getSelectedOptionName(?bool $onlyOption = false): mixed
    {
        $keyBoards = Arr::flatten($this->message->replyMarkup?->inline_keyboard ?? [],1);
        $keyBoard = collect($keyBoards)->where('callback_data', $this->key)->first();
        return $onlyOption ? ($keyBoard['text'] ?? '') : ($keyBoard['text'] ?? $this->message->text ?? '');
    }

    /**
     * @param string $key
     * @return string[]|null
     */
    public function getSelectedOptionKey(string $key): ?array
    {
        $keyBoards = Arr::flatten($this->message->replyMarkup?->inline_keyboard ?? [],1);
        $keyBoard = collect($keyBoards)->where('callback_data', $this->key)->first();
        $callbackData = $keyBoard['callback_data'] ?? '';
        $value = explode(':', $callbackData)[1] ?? $this->message->text ?? '';

        return !str_starts_with($value, AbstractAction::PREFIX) ? [$key => $value] : null;
    }

    /**
     * @throws TelegramSDKException
     */
    public function setMainKeyboard(): static
    {
        $chat_id = $this->chatId;
        $text = trans('telegram.message.app_name');
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
}
