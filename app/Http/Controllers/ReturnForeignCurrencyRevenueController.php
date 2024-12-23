<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentInvoiceRequest;
use App\Http\Requests\StoreReturnForeignCurrencyRevenueRequest;
use App\Models\Order;
use App\Models\TelegramUser;
use App\Services\MessageService\Actions\Home;
use App\Services\MessageService\Actions\PaymentInvoices;
use App\Services\MessageService\Actions\ReturnForeignCurrencyRevenue;
use App\Services\MessageService\MessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Telegram\Bot\Exceptions\TelegramSDKException;

class ReturnForeignCurrencyRevenueController extends Controller
{
    private MessageService $messageService;

    public function __construct() {
        $this->messageService = resolve('message');
    }

    public function create(Request $request)
    {
        return view('forms.return-foreign-currency-revenue');
    }

    /**
     * @param StoreReturnForeignCurrencyRevenueRequest $request
     * @return JsonResponse
     * @throws TelegramSDKException
     */
    public function store(StoreReturnForeignCurrencyRevenueRequest $request)
    {
        $user = $request->get('user');
        $localUser = TelegramUser::where('user_id', $user['id'] ?? null)->first();
        $order = Order::getOrderByLocalUser($localUser);

        $formData = collect($request->only([
            'beneficiary_name', 'beneficiary_address', 'iban', 'swift_code', 'bank_name',
            'bank_address', 'director', 'registration_date', 'registration_number', 'vat', 'web',
            'example', 'task', 'conditions',
        ]))->reduce(function(array $collection, mixed $value, string $key) use ($request) {
            !is_null($value) && $collection[] = ['name' => $key, 'value' => $value];
            return $collection;
        }, []);

        $action = new ReturnForeignCurrencyRevenue();
        $order->syncSteps(
            current_key: $action->getActionKey('cart'),
            name: trans('telegram.return_foreign_currency_revenue.order.form'),
            form_data: $formData,
            prev_key: (new Home())->getActionKeyWithoutPostfix('legal_entities')
        );
        $this->messageService->chatId = $order->chat_id;
        $this->messageService->order = $order;
        $this->messageService->setLastStep();
        $action->cart();

        return response()->json(['data' => $request->all()]);
    }
}
