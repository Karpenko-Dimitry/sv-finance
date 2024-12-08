<?php

namespace App\Services\MessageService\Actions;

use App\Services\MessageService\Actions\Home\Home;
use App\Services\MessageService\Actions\Individuals\Individuals;
use App\Services\MessageService\MessageService;

class BaseAction
{
    protected MessageService $messageService;
    public function __construct()
    {
        $this->messageService = resolve('message');
    }

    public static function mappingClasses(?string $baseClassName = null) {
        return [
            class_basename(Home::class) => Home::class,
            class_basename(Individuals::class) => Individuals::class,
        ];
    }
}
