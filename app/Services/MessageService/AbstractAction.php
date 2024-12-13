<?php

namespace App\Services\MessageService;

abstract class AbstractAction
{
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
     * @return string
     */
    public function getActionKey(?string $methodName = null): string {
        $uniquePostfix = $this->postfix++;

        return $this->getActionKeyWithoutPostfix($methodName) . ":$uniquePostfix";
    }

    public function getActionKeyWithoutPostfix(?string $methodName = null): string {
        $actionName = $this->name;
        $methodName = $methodName ?? self::DEFAULT_METHOD;

        return "$actionName@$methodName";
    }
}
