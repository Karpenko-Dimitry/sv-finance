<?php

namespace App\Services\MessageService;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Telegram\Bot\FileUpload\InputFile;
use Telegram\Bot\Keyboard\Keyboard;

class MessagesConfig
{
    protected string $locale;

    public function __construct() {
        $this->locale = App::currentLocale();
    }

    public function config(string $key, string $defaultKey = '/start') {
        $config = $this->getConfig();

        return Arr::get($config, $key) ?? Arr::get($config, $defaultKey);
    }

    /**
     * @return array[]
     */
    protected function getConfig(): array
    {
        return [
            '/start' => [
                'default' => true,
                'method' => 'sendPhoto',
                'params' => [
                    'photo' => InputFile::create(Storage::disk('public')->path('avatar-black.png')),
                    'caption' => trans('telegram.greeting'),
                    'reply_markup' => new Keyboard(['inline_keyboard' => [
                        [
                            ['text' => trans('telegram.individuals'), 'callback_data' => 'individuals'],
                            ['text' => trans('telegram.legal_entities'), 'callback_data' => 'legal_entities'],
                        ]
                    ]])
                ]
            ]
        ];
    }
}
