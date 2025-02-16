<?php

namespace App\Services\MessageService;

use App\Services\MessageService\Actions\Checkout;
use Telegram\Bot\Keyboard\Keyboard;

abstract class AbstractAction
{
    const PREFIX = '_P_';
    protected ?string $name = null;
    public const DEFAULT_METHOD = 'start';
    protected int $postfix = 0;

    protected MessageService $messageService;
    public function __construct()
    {
        $this->messageService = resolve('message');
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string|null $methodName
     * @param string|null $customPostfix
     * @return string
     */
    public function getActionKey(?string $methodName = null, ?string $customPostfix = null): string {
        $uniquePostfix = $customPostfix ?? (self::PREFIX . $this->postfix++);

        return $this->getActionKeyWithoutPostfix($methodName) . ":$uniquePostfix";
    }

    public function getActionKeyWithoutPostfix(?string $methodName = null): string {
        $actionName = $this->name;
        $methodName = $methodName ?? self::DEFAULT_METHOD;

        return "$actionName@$methodName";
    }

    /**
     * @return bool
     */
    public function requestContact(): bool
    {
        return !$this->messageService->localTelegramUser->username && !$this->messageService->localTelegramUser->phone;
    }

    /**
     * @return Keyboard
     */
    public function getCartReplyMarkup(): Keyboard
    {
        if ($this->requestContact()) {
            return new Keyboard(
                [
                    'keyboard' => [
                        [
                            ['text' => trans('telegram.button.checkout'), 'request_contact' => true],
                        ], [
                            ['text' => trans('telegram.button.home')],
                        ]
                    ],
                    'resize_keyboard' => true,
                ]
            );
        } else {
            return new Keyboard(['inline_keyboard' => [
                [
                    ['text' => trans('telegram.button.checkout'), 'callback_data' => (new Checkout())->getActionKey('checkout')],
                ], [
                    ['text' => trans('telegram.button.back'), 'callback_data' =>  $this->messageService->getBackKey($this->getActionKey(__FUNCTION__))],
                ]
            ]]);
        }
    }
}
