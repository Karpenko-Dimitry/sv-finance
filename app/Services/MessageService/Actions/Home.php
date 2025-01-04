<?php

namespace App\Services\MessageService\Actions;

use App\Models\Order;
use App\Services\MessageService\AbstractAction;
use App\Services\MessageService\MessageService;
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
        $message_id = $this->messageService->messageId;
        $photo = InputFile::create(public_path('assets/img/avatar-black.png'));
        $caption = trans('telegram.message.start');
        $reply_markup = new Keyboard(
            [
                'inline_keyboard' => [
                    [
                        ['text' => trans('telegram.button.individuals'), 'callback_data' => $this->getActionKey('individuals')],
                    ], [
                        ['text' => trans('telegram.button.legal_entities'), 'callback_data' => $this->getActionKey('legal_entities')],
                    ], [
                        ['text' => trans('telegram.button.contact_manager'), 'url' => MessageService::MANAGER_URL],
                    ]
                ],
            ]
        );

        try {
            $this->messageService->telegram->deleteMessage(compact('message_id', 'chat_id'));
        } catch (\Throwable $exception) {}

        $this->messageService->order->steps->pluck('message_id')->unique()->each(function ($message_id) use ($chat_id) {
            try {
                $this->messageService->telegram->deleteMessage(compact('message_id', 'chat_id'));
            } catch (\Throwable $exception) {}
        });

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
        $message_id = $this->messageService->messageId;
        $text = trans('telegram.message.services');
        $this->messageService->order->syncSteps(
            $this->getActionKey(__FUNCTION__),
            trans('telegram.order.start'),
            trans('telegram.button.individuals')
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
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->getActionKeyWithoutPostfix()],
            ]
        ]]);
        $this->messageService->telegram->deleteMessage(compact('chat_id', 'message_id'));
        $this->messageService->telegram->sendMessage(compact('chat_id', 'text', 'reply_markup'));

        return $this;
    }

    /**
     * @return $this
     * @throws TelegramSDKException
     */
    public function legal_entities(): static {
        $chat_id = $this->messageService->chatId;
        $message_id = $this->messageService->messageId;
        $text = trans('telegram.message.services');
        $this->messageService->order->syncSteps(
            $this->getActionKey(__FUNCTION__),
            trans('telegram.order.start'),
            trans('telegram.button.legal_entities')
        );
        $this->messageService->order->update(['type' => Order::TYPE_LEGAL_ENTITIES]);

        $reply_markup = new Keyboard(['inline_keyboard' => [
            [
                ['text' => trans('telegram.button.payment_invoices') , 'web_app' => ['url' => route('payment-invoices.create')]],
                ['text' => trans('telegram.button.business_relocation'), 'callback_data' => (new BusinessRelocation())->getActionKey()],
            ], [
                ['text' => trans('telegram.button.payment_agency_agreement'), 'callback_data' => (new PaymentAgencyAgreement())->getActionKey()],
            ], [
                ['text' => trans('telegram.button.return_foreign_currency_revenue'), 'web_app' => ['url' => route('return-foreign-currency-revenue.create')]],
            ], [
                ['text' => trans('telegram.button.contact_manager'), 'url' => MessageService::MANAGER_URL],
            ], [
                ['text' => trans('telegram.button.back'), 'callback_data' => $this->getActionKeyWithoutPostfix()],
            ]
        ]]);

        $this->messageService->telegram->deleteMessage(compact('chat_id', 'message_id'));
        $this->messageService->telegram->sendMessage(compact('chat_id', 'text', 'reply_markup'));

        return $this;
    }
}
