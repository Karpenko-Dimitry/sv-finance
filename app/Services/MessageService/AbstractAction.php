<?php

namespace App\Services\MessageService;

abstract class AbstractAction
{
   protected ?string $name = null;

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
     * @param string $methodName
     * @param string|null $actionName
     * @return string
     */
    public function getActionKey(string $methodName, ?string $actionName = null): string {
        $action = $actionName ? MessageService::getAction($actionName) : $this;
        $actionName = $action->name;
        return "$actionName@$methodName";
    }
}
