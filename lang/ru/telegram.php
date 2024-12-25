<?php

return [
    'currency_exchange' => [
        'message' => [
            'service_type' => 'Выберите услугу по обмену валюты:',
            'city' => 'Выберите город для покупки (продажи) валюты:',
            'currency' => 'Выберите валюту для покупки (продажи) валюты:',
            'currency_type' => 'Выберите тип валюты для покупки (продажи) валюты:',
            'amount' => 'Укажите сумму для покупки (продажи) валюты:',
            'custom_currency' => 'Укажите желаемую валюту для покупки (продажи) валюты:',
            'custom_city' => 'Укажите город для покупки (продажи) валюты:',
        ],
        'button' => [
            'buy' => '⬇ Я покупаю',
            'sell' => '⬆ Я продаю',
            'new_usd_banknote' => '🔵 Нового образца - Синий',
            'old_usd_banknote' => '⚪ Старого образца - Белый',
        ],
        'order' => [
            'order' => 'Ваша заявка №:number',
            'currency' => 'Валюта:',
            'service_type' => 'Тип услуги по обмену:',
            'city' => 'Город обмена:',
            'custom_city' => 'свой вариант',
            'currency_type' => 'Тип обмениваемой валюты:',
            'amount' => 'Cумма:',
            'services' => 'Тип услуги:',
        ],
    ],
    'crypto_exchange' => [
        'message' => [
            'service_type' => 'Выберите услугу по обмену криптовалюты:',
            'city' => 'Выберите город по обмену криптовалюты:',
            'currency' => 'Выберите обмениваюмую криптовалюту:',
            'currency_type' => 'Выберите тип обмениваемой криптовалюты:',
            'amount' => 'Укажите сумму обмена криптовалюты:',
            'custom_currency' => 'Укажите желаюмую криптовалюту обмена:',
            'custom_city' => 'Укажите город по обмену криптовалюты:',
            'pay_type' => 'Я отдаю:',
            'bank' => 'Выберите банк по оплате криптовалюты:',
        ],
        'button' => [
            'buy' => '⬇ Я покупаю',
            'sell' => '⬆ Я продаю',
            'btc' => '🪙 BTC',
            'eth' => '🪙 ETH',
            'usdt' => '🪙 USDT',
            'cash' => 'Наличными',
            'card' => 'На карту',
            'sberbank' => 'Сбербанк',
            'tbank' => 'Т Банк',
            'rnkb' => 'РНКБ',
        ],
        'order' => [
            'order' => 'Ваша заявка №:number',
            'currency' => 'Криптовалюта:',
            'service_type' => 'Тип услуги:',
            'city' => 'Город обмена криптовалюты:',
            'custom_city' => 'свой вариант',
            'currency_type' => 'Тип обмениваемой криптовалюты:',
            'amount' => 'Cумма криптовалюты:',
            'services' => 'Услуга по обмену криптовалюты:',
            'pay_type' => 'Тип оплаты:',
            'bank' => 'Банк оплаты:',
        ],
    ],
    'international_transfer' => [
        'message' => [
            'service_type' => 'Выберите тип международного перевода:',
        ],
        'button' => [
            'cache_transfer' => 'Наличными по всему миру',
            'sepa_transfer' => 'SEPA',
            'swift_transfer' => 'SWIFT',
        ],
        'order' => [
            'services' => 'Сервис:'
        ]
    ],
    'international_cache_transfer' => [
        'message' => [
            'direction' => 'Выберите направление международного перевода:',
        ],
        'button' => [
            'from_rf' => 'Из РФ',
            'to_rf' => 'В РФ',
        ],
        'order' => [
            'service_type' => 'Тип перевода:',
        ],
    ],
    'international_cache_transfer_from_rf' => [
        'message' => [
            'amount' => 'Сумма наличного международного перевода из РФ:',
            'city' => 'Город отправления наличного международного перевода из РФ:',
            'custom_city' => 'Укажите город отправления наличного международного перевода из РФ:',
            'recipient_country' => 'Страна и город получения международного перевода из РФ:',
            'currency' => 'Валюта отправления наличного международного перевода из РФ:',
            'recipient_currency' => 'Валюта получения наличного международного перевода из РФ:',
            'custom_currency' => 'Укажите валюту отправления наличного международного перевода из РФ:',
            'recipient_custom_currency' => 'Укажите валюту получения наличного международного перевода из РФ:',
        ],
        'button' => [

        ],
        'order' => [
            'direction' => 'Направление перевода:',
            'city' => 'Город отправления перевода:',
            'currency' => 'Валюта отправления:',
            'recipient_currency' => 'Валюта получения:',
            'amount' => 'Cумма отравления:',
            'recipient_country' => 'Cтрана и город получения:'
        ],
    ],
    'international_cache_transfer_to_rf' => [
        'message' => [
            'country' => 'Страна и город отправления перевода в РФ:',
            'currency' => 'Валюта отправления перевода в РФ:',
            'custom_currency' => 'Укажите валюту отправления перевода в РФ:',
            'amount' => 'Укажите сумму отправления перевода в РФ:',
            'recipient_city' => 'Город получения перевода в РФ:',
            'recipient_custom_city' => 'Укажите город получения перевода в РФ:',
            'recipient_amount' => 'Укажите сумму получения перевода в РФ:',
            'recipient_currency' => 'Укажите валюту получения перевода в РФ:',
        ],
        'button' => [

        ],
        'order' => [
            'direction' => 'Направление перевода:',
            'country' => 'Страна и город отравления:',
            'currency' => 'Валюта отравления:',
            'amount' => 'Cумму отправления:',
            'recipient_city' => 'Город получения:',
            'recipient_amount' => 'Cумма получения:',
            'recipient_currency' => 'Валюта получения:',
        ],
    ],
    'international_sepa_transfer' => [
        'message' => [
            'direction' => 'Выберите направление SEPA перевода:',
        ],
        'button' => [
            'from_rf' => 'Из РФ',
            'to_rf' => 'В РФ',
        ],
        'order' => [
            'service_type' => 'Тип перевода:',
        ],
    ],
    'international_sepa_transfer_from_rf' => [
        'message' => [
            'recipient_country' => 'Укажите страну и город получателя SEPA перевода:',
            'recipient_amount' => 'Укажите сумму получения SEPA перевода:',
            'recipient_currency' => 'Укажите валюту получения SEPA перевода:',
            'transfer_type' => 'Я отдаю:',
            'city_cash_transfer' => 'Укажите город в котором передаете наличные по SEPA переводу:',
            'bank_transfer' => 'Укажите банк платежной карты по SEPA переводу:',
            'custom_transfer' => 'Укажите свой вариант передачи стредств по SEPA переводу:',
        ],
        'button' => [
            'cash' => 'Наличными',
            'card' => 'Платежная карта',
            'crypto_wallet' => 'Кошелек в usdt',
            'custom' => 'Свой вариант',
            'sberbank' => 'Сбербанк',
            'tbank' => 'Т Банк',
            'rnkb' => 'РНКБ',
        ],
        'order' => [
            'direction' => 'Направление перевода:',
            'recipient_country' => 'Страна/город получателя:',
            'recipient_amount' => 'Сумма получения:',
            'recipient_currency' => 'Валюта получения:',
            'transfer_type' => 'Тип перевода средств:',
            'city_cash_transfer' => 'Город передачи наличных:',
            'custom_city_cash_transfer' => 'Город передачи наличных:',
            'bank_transfer' => 'Банковская платежная карта:',
            'custom_bank_transfer' => 'Банковская платежная карта:',
            'custom_transfer' => 'Метод передачи средств:',
        ],
    ],
    'international_sepa_transfer_to_rf' => [
        'message' => [
            'sender_country' => 'Укажите страну и город отправителя SEPA перевода:',
            'sender_amount' => 'Укажите сумму отправителя SEPA перевода:',
            'sender_currency' => 'Укажите валюту отправления SEPA перевода:',
            'transfer_type' => 'Укажите тип получения SEPA перевода:',
            'city_cash_transfer' => 'Укажите город в котором получаете наличные по SEPA переводу:',
            'bank_transfer' => 'Укажите банк платежной карты для получения SEPA перевода:',
            'custom_transfer' => 'Укажите свой вариант получения стредств по SEPA переводу:',
        ],
        'button' => [
            'cash' => 'Наличными',
            'card' => 'Платежная карта',
            'crypto_wallet' => 'Кошелек в usdt',
            'custom' => 'Свой вариант',
            'sberbank' => 'Сбербанк',
            'tbank' => 'Т Банк',
            'rnkb' => 'РНКБ',
        ],
        'order' => [
            'direction' => 'Направление перевода:',
            'sender_country' => 'Страна/город отпраувителя:',
            'sender_amount' => 'Сумма отправления:',
            'sender_currency' => 'Валюта отправления:',
            'transfer_type' => 'Тип перевода средств:',
            'city_cash_transfer' => 'Город получения наличных:',
            'custom_city_cash_transfer' => 'Город получения наличных:',
            'bank_transfer' => 'Банковская платежная карта:',
            'custom_bank_transfer' => 'Банковская платежная карта:',
            'custom_transfer' => 'Метод получения средств:',
        ],
    ],
    'international_swift_transfer' => [
        'message' => [
            'direction' => 'Выберите направление SWIFT перевода:',
        ],
        'button' => [
            'from_rf' => 'Из РФ',
            'to_rf' => 'В РФ',
        ],
        'order' => [
            'service_type' => 'Тип перевода:',
        ],
    ],
    'international_swift_transfer_from_rf' => [
        'message' => [
            'recipient_country' => 'Укажите страну и город получателя SWIFT перевода:',
            'recipient_amount' => 'Укажите сумму получения SWIFT перевода:',
            'recipient_currency' => 'Укажите валюту получения SWIFT перевода:',
            'transfer_type' => 'Укажите тип передачи SWIFT перевода:',
            'city_cash_transfer' => 'Укажите город в котором передаете наличные по SWIFT переводу:',
            'bank_transfer' => 'Укажите банк платежной карты по SWIFT переводу:',
            'custom_transfer' => 'Укажите свой вариант передачи стредств по SWIFT переводу:',
        ],
        'button' => [
            'cash' => 'Наличными',
            'card' => 'Платежная карта',
            'crypto_wallet' => 'Кошелек в usdt',
            'custom' => 'Свой вариант',
            'sberbank' => 'Сбербанк',
            'tbank' => 'Т Банк',
            'rnkb' => 'РНКБ',
        ],
        'order' => [
            'direction' => 'Направление перевода:',
            'recipient_country' => 'Страна/город получателя:',
            'recipient_amount' => 'Сумма получения:',
            'recipient_currency' => 'Валюта получения:',
            'transfer_type' => 'Тип перевода средств:',
            'city_cash_transfer' => 'Город передачи наличных:',
            'custom_city_cash_transfer' => 'Город передачи наличных:',
            'bank_transfer' => 'Банковская платежная карта:',
            'custom_bank_transfer' => 'Банковская платежная карта:',
            'custom_transfer' => 'Метод передачи средств:',
        ],
    ],
    'international_swift_transfer_to_rf' => [
        'message' => [
            'sender_country' => 'Укажите страну и город отправителя SWIFT перевода:',
            'sender_amount' => 'Укажите сумму отправителя SWIFT перевода:',
            'sender_currency' => 'Укажите валюту отправления SWIFT перевода:',
            'transfer_type' => 'Укажите тип получения SWIFT перевода:',
            'city_cash_transfer' => 'Укажите город в котором получаете наличные по SWIFT переводу:',
            'bank_transfer' => 'Укажите банк платежной карты для получения SWIFT перевода:',
            'custom_transfer' => 'Укажите свой вариант получения стредств по SWIFT переводу:',
        ],
        'button' => [
            'cash' => 'Наличными',
            'card' => 'Платежная карта',
            'crypto_wallet' => 'Кошелек в usdt',
            'custom' => 'Свой вариант',
            'sberbank' => 'Сбербанк',
            'tbank' => 'Т Банк',
            'rnkb' => 'РНКБ',
        ],
        'order' => [
            'direction' => 'Направление перевода:',
            'sender_country' => 'Страна/город отпраувителя:',
            'sender_amount' => 'Сумма отправления:',
            'sender_currency' => 'Валюта отправления:',
            'transfer_type' => 'Тип перевода средств:',
            'city_cash_transfer' => 'Город получения наличных:',
            'custom_city_cash_transfer' => 'Город получения наличных:',
            'bank_transfer' => 'Банковская платежная карта:',
            'custom_bank_transfer' => 'Банковская платежная карта:',
            'custom_transfer' => 'Метод получения средств:',
        ],
    ],
    'sailor_services' => [
        'message' => [
            'service_type' => 'Выберите тип услуги:',
        ],
        'button' => [
            'accept_foreign_currency_payment' => 'Прием валютных платежей',
            'currency_exchange' => '💰Обмен валюты',
        ],
        'order' => [
            'services' => 'Сервис:'
        ],
    ],

    'sailor_services_accept_payment' => [
        'message' => [
            'pay_type' => 'Укажите метод отправки валютного платежа:',
            'currency' => 'Укажите валюту отправки валютного платежа:',
            'amount' => 'Укажите сумму отправки валютного платежа:',
            'transfer_type' => 'Укажите тип отправки валютного платежа:',
            'city_transfer' => 'Укажите город приема валютного платежа::',
            'recipient_currency' => 'Укажите валюту приема валютного платежа:',
        ],
        'button' => [
            'ship_money' => 'ShipMoney',
            'mar_trust' => 'MarTrust',
            'custom_option' => 'Cвой вариант',
            'cash' => 'Наличными',
            'card' => 'Платежная карта',
            'custom' => 'Свой вариант',
        ],
        'order' => [
            'currency' => 'Валюта отправки валютного платежа:',
            'service_type' => 'Тип сервисаа:',
            'pay_type' => 'Метод отправки валютного платежа:',
            'amount' => 'Cумма отправки валютного платежа:',
            'transfer_type' => 'Тип отправки валютного платежа:',
            'custom_transfer' => 'Тип отправки валютного платежа:',
            'city_transfer' => 'Город приема валютного платежа:',
            'custom_city_transfer' => 'Город приема валютного платежа:',
            'recipient_currency' => 'Валюта приема валютного платежа:',
        ]
    ],

    'financial_consulting' => [
        'message' => [
            'service_type' => 'Выберите тип финансовой консультации:',
        ],
        'button' => [
            'relocation' => 'Релокация',
            'financial_resource_legalization' => 'Легализация денежных средств',

        ],
        'order' => [
            'services' => 'Cервис:',
        ],
    ],

    'financial_consulting_relocation' => [
        'message' => [
            'relocation_type' => 'Выберите тип релокации:',
            'country' => 'Укажите страну и город:',
        ],
        'button' => [
            'vng' => 'ВНЖ',

        ],
        'order' => [
            'services_type' => 'Тип сервиса:',
            'relocation_type' => 'Тип релокации:',
            'country' => 'Страна / город:',
        ],
    ],

    'financial_consulting_legalize' => [
        'message' => [
            'city' => 'Выберите город передачи денежных стредств:',
            'country' => 'Укажите страну и город легализации денежных стредств:',
            'aim' => 'Укажите цель легализации денежных стредств:',
            'currency' => 'Укажите валюту легализации денежных стредств:',
            'amount' => 'Укажите сумму легализации денежных стредств:',
        ],
        'button' => [

        ],
        'order' => [
            'services_type' => 'Тип сервиса:',
            'country' => 'Cтрана / город легализации:',
            'aim' => 'Цель легализации:',
            'city' => 'Город передачи денежных стредств:',
            'currency' => 'Валюта легализации:',
            'amount' => 'Cумма легализации:',
        ],
    ],

    'payment_invoice' => [
        'form' => [
            'title' => 'Опросник по оплате инвойсов',
            'text' => 'Внимательно заполните форму',
            'placeholder' => [
                'first_name' => 'Укажите имя',
                'last_name' => 'Укажите фамилию',
                'iban' => 'Укажите IBAN',
                'swift_code' => 'Укажите SWIFT/BIC',
                'bank_name' => 'Укажите банк',
                'bank_address' => 'Укажите адрес банка',
                'director' => 'Укажите директора',
                'registration_date' => 'Укажите дату регистрации',
                'registration_number' => 'Укажите регистрационный номер',
                'vat' => 'Укажите НДС',
                'web' => 'Укажите веб-сайт',
                'example' => 'Укажите пример',
                'task' => 'Укажите задачу',
                'conditions' => 'Укажите условия',
            ],
            'button' => [
                'submit' => 'Подвтердить заявку'
            ],
            'label' => [
                'first_name' => 'Имя получателя',
                'last_name' => 'Фамилия получателя',
                'iban' => 'Счёт получателя (IBAN):',
                'swift_code' => 'SWIFT/BIC код банка получателя:',
                'bank_name' => 'Название банка получателя:',
                'bank_address' => 'Адрес банка получателя:',
                'director' => 'Директор:',
                'registration_date' => 'Дата регистрации компании:',
                'registration_number' => 'Регистрационный номер:',
                'vat' => 'НДС:',
                'web' => 'Веб-сайт:',
                'example' => 'Пример:',
                'task' => 'ЗАДАЧА: оплатить сумму в 3 млн долларов - отдаем USDT:',
                'conditions' => 'Условия: За оборудование СОГЛАСНО ИНВОЙСА:',
            ]
        ],
        'order' => [
            'form' => 'Реквизиты оплаты инфойсов:',
        ],
    ],

    'return_foreign_currency_revenue' => [
        'form' => [
            'title' => 'Опросник по возврату валютной выручки',
            'text' => 'Внимательно заполните форму',
            'label' => [
                'beneficiary_name' => 'Имя отправителя',
                'beneficiary_address' => 'Адрес отправителя',
                'iban' => 'Счёт отправителя (IBAN)',
                'swift_code' => 'SWIFT/BIC код банка отправителя',
                'bank_name' => 'Название банка отправителя',
                'bank_address' => 'Адрес банка отправителя',
                'director' => 'Директор',
                'registration_date' => 'Дата регистрации компании',
                'registration_number' => 'Регистрационный номер',
                'vat' => 'НДС',
                'web' => 'Веб-сайт',
                'example' => 'Пример',
                'task' => 'ЗАДАЧА: Принять сумму в 3 млн долларов - мы хотим получить USDT',
                'conditions' => 'Условия: За оборудование СОГЛАСНО ИНВОЙСА',
            ],
            'button' => [
                'submit' => 'Подвтердить заявку'
            ],
            'placeholder' => [
                'beneficiary_name' => 'Укажите имя отправителя:',
                'beneficiary_address' => 'Укажите адрес отправителя:',
                'iban' => 'Счёт отрпавилетеля (IBAN):',
                'swift_code' => 'SWIFT/BIC код банка отправителя:',
                'bank_name' => 'Название банка отправителя:',
                'bank_address' => 'Адрес банка отправителя:',
                'director' => 'Директор:',
                'registration_date' => 'Дата регистрации компании:',
                'registration_number' => 'Регистрационный номер:',
                'vat' => 'НДС:',
                'web' => 'Веб-сайт:',
                'example' => 'Пример:',
                'task' => 'Укажите задачу',
                'conditions' => 'Укажите условия',
            ]
        ],
        'order' => [
            'form' => 'Реквизиты по возврату валютной выручки:',
        ],
    ],

    'business_relocation' => [
        'message' => [
            'business_type' => 'Укажите вид бизнеса:',
            'country' => 'Укажите страну релокации:',
            'amount' => 'Укажите сумму:',
            'currency' => 'Укажите валюту:',
            'custom_currency' => 'Укажите свою валюту:',
        ],
        'order' => [
            'services_type' => 'Тип сервиса:',
            'business_type' => 'Вид бизнеса:',
            'country' => 'Страна релокации:',
            'amount' => 'Cумма:',
            'currency' => 'Валюта:',
        ]
    ],

    'payment_agency_agreement' => [
        'message' => [
            'aim' => 'Укажите назначение платежа:',
            'country' => 'Укажите страну отправителя:',
            'amount' => 'Укажите сумму:',
            'currency' => 'Укажите валюту:',
            'custom_currency' => 'Укажите свою валюту:',
            'city' => 'Укажите место получения:',
            'custom_city' => 'Укажите свое место получения:',
            'file' => 'Отправте файл или нажмити продложить:',
            'success_file' => 'Фаил получен:',
        ],
        'button' => [
            'forward' => 'Продолжить ➡'
        ],
        'order' => [
            'services_type' => 'Тип сервиса:',
            'aim' => 'Назначение платежа:',
            'country' => 'Страна отправителя:',
            'amount' => 'Cумма:',
            'currency' => 'Валюта:',
            'city' => 'Место получения:',
            'attachment' => 'Приложение:',
            'files' => ':count файлов'
        ]
    ],

    'checkout' => [
        'order' => 'Заказ №:number',
        'name' => 'Имя: :name',
        'surname' => 'Фамилия: :surname',
        'nickname' => 'Ник: @:nickname',
        'completed' => 'Ваша заявка подтверждена ждите ответа оператора',


    ],
    'message' => [
        'start' => 'Финансовые услуги:',
        'services' => 'Выберите необходимую услугу:',
        'city' => 'Выберите город:',
        'international_transfer_recipient_city' => 'Выберите город получения:',
        'service_type' => 'Тип услуги:',
        'crypto_type' => 'Выберите криптовалюту:',
        'currency' => 'Выберите валюту:',
        'amount' => 'Укажите желаемую сумму:',
        'currency_type' => 'Выберите тип валюты:',
        'custom_currency' => 'Укажите желаюмую валюту:',
        'crypto_sell' => 'Укажите желаюмую валюту:',
        'crypto_pay_type' => 'Выберите тип оплаты:',
        'pay_type' => 'Выберите тип оплаты:',
        'crypto_currency_type' => 'Выберите валюту:',
        'international_transfers' => 'Выберите тип перевода:',
        'international_transfer_pay_system' => 'Выберите платежную систему:',
        'international_transfer_transaction_type' => 'Получение / Выдача:',
        'receipt_type' => 'Выберите место получения:',
        'custom_receipt_type' => 'Укажите свое место получения:',
        'custom_pay_type' => 'Укажите свое тип оплаты:',
        'country_city' => 'Укажите страну и город:',
        'financial_resource_legalization_aim' => 'Укажите цель:',
        'financial_resource_legalization_receipt_location' => 'Где отдают денежные средства:',
        'payment_invoices_purpose' => 'Назнаяение платежа:',
        'payment_invoices_receipt_location' => 'Страна получателя:',
        'payment_invoices_city' => 'Где отдаете:',
        'payment_invoices_file' => 'Отправте файл или нажмити продложить:',
        'success_file' => 'Фаил получен:',
    ],
    'button' => [
        'home' => '🏠 Главная',
        'individuals' => '👨 Частным лицам',
        'legal_entities' => '🏬 Юридическим лицам',

        'currency_exchange' => '💰Обмен валюты',
        'crypto_exchange' => '🪙 Обмен криптовалюты',
        'international_transfers' => '🏧 Международные переводы',
        'sailor_services' => '🚢 Морякам дальнего плаванья',
        'payment_invoices' => '📜 Оплата инвойсов',
        'return_foreign_currency_revenue' => '🏪 Возврат валютной выручки',
        'payment_agency_agreement' => '🚢 Платежи для импортеров по договору',
        'business_relocation' => '🏢 Релокация бизнеса',

        'usd' => '💵 Дол. США',
        'eur' => '💶 Евро',
        'rub' => 'Рубль',
        'cny' => 'Юань',
        'custom_currency' => '💴 Свой вариант',

        'moscow' => '🌆 Москва',
        'simferopol' => '🌆 Симферополь',
        'sevastopol' => '🌆 Севастополь',
        'custom_city' => '🌆 Cвой вариант',

        'buy' => '⬇ Я покупаю',
        'sell' => '⬆ Я продаю',

        'vng' => 'ВНЖ',

        'wise_revolut' => 'Wise/Revolut',
        'visa_mastercard' => 'Visa/Mastercard',
        'transaction_in_office' => 'В офисе',
        'transaction_by_card' => 'На карту',

        'office' => 'Офис',
        'cart' => 'Карта',

        'financial_consulting' => '💲Финансовый консалтинг',

        'checkout' => '✅ Подвердить заказ',
        'completed' => '✅ Выполнена',
        'forward' => 'Продолжить ➡',
        'back' => '⬅ Назад',
    ],
    'order' => [
        'start' => 'Финансовые услуги:',
        'services' => 'Услуга:',
        'city' => 'Город:',
        'service_type' => 'Тип услуги:',
        'currency' => 'Валюта:',
        'amount' => 'Cумма:',
        'currency_type' => 'Тип валюты:',
        'crypto_type' => 'Тип криптовалюты:',
        'crypto_bank' => 'Банк:',
        'pay_type' => 'Тип оплаты:',
        'custom_sailor_pay_type' => 'Тип оплаты:',
        'crypto_currency_type' => 'Тип валюты:',
        'recipient_city' => 'Город получения:',
        'international_transfer_custom_currency' => 'Валюта:',
        'pay_system' => 'Система оплаты:',
        'international_sepa_transfer_transaction_type' => 'Получение / Выдача:',
        'international_swift_transfer_transaction_type' => 'Получение / Выдача:',
        'currency_amount' => 'Cумма:',
        'receipt_type' => 'Место получения:',
        'sailor_receipt_type' => 'Место получения:',
        'sailor_custom_receipt_type' => 'Место получения:',
        'vng_location' => 'Страна/город:',
        'country_city' => 'Страна/город:',
        'financial_resource_legalization_aim' => 'Цель легализации:',
        'financial_resource_legalization_receipt_location' => 'Отдают денежные средства:',
        'payment_invoices_purpose' => 'Назнаяение платежа:',
        'payment_invoices_receipt_location' => 'Страна получателя:',
        'payment_invoices_city' => 'Где отдают:',
    ],
    'errors' => [
        'str_length' => '🤷 Значение должно быть не меньше 3 символов и не больше 200 символов',
        'not_numeric' => '🤷 Ввведите пожалуйста числовое значение:',
        'invalid_amount' => '🤷 Сумма должна быть не меньше :amount. Повторите пожалуйста ввод.'
    ]
];
