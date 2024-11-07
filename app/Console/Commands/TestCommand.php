<?php

namespace App\Console\Commands;

use App\Models\Chat;
use Illuminate\Console\Command;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

class TestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     * @throws TelegramSDKException
     */
    public function handle(): int
    {
        $telegram = new Api();

        $phrases = config('phrases.morning');
        $key = rand(0, count($phrases) - 1);

        /** @var Chat $chat */
        foreach (Chat::whereNotNull('chat_id')->get() as $chat) {
            sleep(1);
            $telegram->sendMessage([
                'chat_id' => $chat->chat_id,
                'text' => $phrases[$key],
            ]);
        }
        return Command::SUCCESS;
    }
}
