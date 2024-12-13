<?php

namespace App\Services\MessageService\Actions;

use App\Models\Order;
use App\Services\MessageService\AbstractAction;
use Illuminate\Support\Facades\Storage;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\FileUpload\InputFile;
use Telegram\Bot\Keyboard\Keyboard;

class Home extends AbstractAction
{
    protected ?string $name = 'home';

    /**
     * @return $this
     * @throws TelegramSDKException
     */
    public function start(): static
    {
        $chat_id = $this->messageService->chatId;
        $photo = InputFile::create(Storage::disk('public')->path('avatar-black.png'));
        $caption = trans('telegram.message.start');
        $reply_markup = new Keyboard(
            [
                'inline_keyboard' => [
                    [
                        ['text' => trans('telegram.button.individuals'), 'callback_data' => $this->getActionKey('individuals')],
                        ['text' => trans('telegram.button.legal_entities'), 'callback_data' => $this->getActionKey('legal_entities')],
                    ]
                ],
            ]
        );
        $this->messageService->order->steps()->delete();
        $this->messageService->setMainKeyboard();
        $this->messageService->telegram->sendPhoto(compact('chat_id', 'photo', 'caption', 'reply_markup'));

        return $this;
    }

    /**
     * @return $this
     * @throws TelegramSDKException
     */
    public function individuals(): static
    {
        $chat_id = $this->messageService->chatId;
        $text = trans('telegram.message.services');
        $this->messageService->order->syncSteps(
            $this->getActionKey(__FUNCTION__),
            trans('telegram.order.start'),
            $this->messageService->getSelectedOptionName()
        );
        $this->messageService->order->update(['type' => Order::TYPE_INDIVIDUALS]);
        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.currency_exchange'), 'callback_data' => (new CurrencyExchange())->getActionKey()],
            ], [
                ['text' =>  trans('telegram.button.crypto_exchange'), 'callback_data' => (new CryptoExchange())->getActionKey()],
            ], [
                ['text' =>  trans('telegram.button.international_transfers'), 'callback_data' => (new InternationalTransfer())->getActionKey()],
            ], [
                ['text' =>  trans('telegram.button.sailor_services'), 'callback_data' => (new SailorServices())->getActionKey()],
            ], [
                ['text' =>  trans('telegram.button.financial_consulting'), 'callback_data' => (new FinancialConsulting())->getActionKey()],
            ], [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->messageService->getBackKey($this->getActionKey(__FUNCTION__))],
            ]
        ]]);
        $this->messageService->telegram->sendMessage(compact('chat_id', 'text', 'reply_markup'));

        return $this;
    }

    /**
     * @return $this
     * @throws TelegramSDKException
     */
    public function legal_entities(): static {
        $chat_id = $this->messageService->chatId;
        $text = trans('telegram.message.services');
        $this->messageService->order->syncSteps(
            $this->getActionKey(__FUNCTION__),
            trans('telegram.order.start'),
            $this->messageService->getSelectedOptionName()
        );
        $this->messageService->order->update(['type' => Order::TYPE_LEGAL_ENTITIES]);

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.payment_invoices'), 'callback_data' => 'payment_invoices_purpose'],
                ['text' => trans('telegram.button.business_relocation'), 'callback_data' => 'business_relocation'],
            ], [
                ['text' => trans('telegram.button.payment_agency_agreement'), 'callback_data' => 'payment_agency_agreement'],
            ], [
                ['text' => trans('telegram.button.return_foreign_currency_revenue'), 'callback_data' => 'return_foreign_currency_revenue'],
            ], [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->messageService->getBackKey($this->getActionKey(__FUNCTION__))],
            ]
        ]]);
        $this->messageService->telegram->sendMessage(compact('chat_id', 'text', 'reply_markup'));

        return $this;
    }
}
