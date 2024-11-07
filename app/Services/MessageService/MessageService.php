<?php

namespace App\Services\MessageService;

use App\Models\TelegramUser;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\FileUpload\InputFile;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Objects\Update;

class MessageService
{
    protected Api $telegram;
    protected MessagesConfig $messages;

    public function __construct()
    {
        $this->telegram = new Api();
        $this->messages = new MessagesConfig();
    }

    /**
     * @throws TelegramSDKException
     */
    public function receive(Update $response): static
    {
        $message = $response->getMessage();
        $key = $message->get('text');
        $chat = $response->getChat();
        $chat_id = $chat->get('id');

        $params = $this->messages->config( $key . '.params', '/start.params');
        $method = $this->messages->config( $key . '.method', '/start.method');
        $args = array_merge(compact('chat_id'), $params ?? []);

        if (method_exists(Api::class, $method)) {
            call_user_func([$this->telegram, $method], $args);
        }

        return $this;
    }

    /**
     * @param Update $response
     * @return $this
     * @throws TelegramSDKException
     */
    public function start(Update $response): static
    {
        $chat = $response->getChat();
        $chatId = $chat->get('id');
        $message = $response->getMessage();

//        $this->telegram->deleteMessage([
//            'chat_id' => $chatId,
//            'message_id' => $message->get('message_id')
//        ]);


        $this->telegram->sendPhoto([
            'chat_id' => $chatId,
            'photo' => InputFile::create(Storage::disk('public')->path('avatar-black.png')),
            'caption' => "Thank you for joining us. We hope you enjoyed it!",
            'reply_markup' => new Keyboard(['inline_keyboard' => [
                [
                    ['text' => '1', 'callback_data' => 'cb_btn'],
//                    ['text' => '2 🔗', 'url' => 'https://www.google.com'],
//                    ['text' => '3 🔗', 'url' => 'https://www.google.com'],
                ]
            ]]),
        ]);

        TelegramUser::makeNew($message->toArray());

        return $this;
    }

    /**
     * @param int $chatId
     * @return $this
     * @throws TelegramSDKException
     */
    public function answer(Update $response): static
    {
        $chat = $response->getChat();
        $chatId = $chat->get('id');

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => "Thank you for joining us. We hope you enjoyed it! Keyboard",
            'reply_markup' => new Keyboard(['inline_keyboard' => [
                [
                    ['text' => '1 🔗', 'url' => 'https://www.google.com'],
                    ['text' => '2 🔗', 'url' => 'https://www.google.com'],
                    ['text' => '3 🔗', 'url' => 'https://www.google.com'],
                ]
            ]]),

        ]);

        return $this;
    }
}
