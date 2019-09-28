<?php

return [
//    AUTH BLOCK
	'login' => 'auth/login', // Форма входа на сайт
	'logout' => 'auth/logout', // Выход из учётной записи
//    END AUTH BLOCK
//    COTTAGE BLOCK
	'add-cottage' => 'cottage/add',
	'add-cottage/<cottageNumber:[0-9]+>' => 'cottage/add',
	'create/additional-cottage/<cottageNumber:[0-9]+>' => 'cottage/additional',
	'save/additional-cottage/<cottageNumber:[0-9]+>' => 'cottage/additional-save',
	'change-cottage/<cottageNumber:[0-9]+>' => 'cottage/change',
	'change-cottage' => 'cottage/change',
	'add-cottage/save/<type:add|change>' => 'cottage/save',
	'show-cottage/<cottageNumber:[0-9]+>' => 'cottage/show',
//    END COTTAGE BLOCK
//    TARRIFS BLOCK
//    END TARRIFS BLOCK
//    PAYMENTS BLOCK
	'payment/get-form/<type:complex|single>/<cottageNumber:[0-9]+>' => 'payments/form', // используется
	'payment/get-form/<type:complex|single>/<double:double>/<cottageNumber:[0-9]+>' => 'payments/form', // используется
	'payment/validate/single' => 'payments/validate-single',
	'payment/get/<type:power|membership|target|complex>/<cottageNumber:[0-9]+>' => 'payments/history',
	//'payment/<type:power|membership|target|complex>/<cottageNumber:[0-9]+>' => 'payments/form',
	'payment/<type:single|complex|complex-double>/save' => 'payments/save',
	'invoice/show/<invoiceId:[0-9]+>' => 'payments/invoice-show',
	'show/previous/power/<cottageNumber:[0-9]+>' => 'payments/show-previous',


//    END PAYMENTS BLOCK

//    MANAGEMENT BLOCK
	'update/create/form' => 'management/get-update-form',
	'update/validate' => 'management/validate-update',
	'update/create' => 'management/create-update',
	'updates/check' => 'management/check-update',
	'updates/install' => 'management/install-update',
//    END MANAGEMENT BLOCK
//    GLOBAL FILL BLOCK
//    'create/<type:payment>/<purpose:target>' => 'filling/create'
    'filling' => 'filling/view',
	'fill/<type:power>/<cottageNumber:[0-9]+>' => 'filling/fill', // используется
	'fill/power/current/<cottageNumber:[0-9]+>' => 'filling/fill-current', // используется
    'fill/<type:power>/<period:\d{4}-[\d]{2}>' => 'tariffs/fill',
//    'fill/<type:target>/<period:year>' => 'tariffs/fill',
	'fill/<type:membership>/<period:\d{4}-[1-4]{1}>' => 'tariffs/fill', // используется, заполнение ставок по членским взносам
	'fill/<type:membership-personal|membership-personal-additional>/<cottageNumber:[0-9]+>/<period:\d{4}-[1-4]{1}>' => 'tariffs/fill-personal', // используется, заполнение ставок по членским взносам
//    'fill/<type:target>/<period:\d{4}>' => 'tariffs/fill',
//    END GLOBAL FILL BLOCK
//    CHECKS BLOCK
	'check/<type:membership>/interval/<from:\d{4}-[1-4]{1}>' => 'tariffs/check',
	'check/<type:target>/interval/<from:\d{4}>' => 'tariffs/check',
//    END CHECKS BLOCK
//    MEMBERSHIP BLOCK
	'pay/<type:single>/<cottageNumber:[0-9]+>' => 'payments/validate-payment',
	'get/future-quarters/<quartersNumber:\d+>/<cottageNumber:[0-9]+>' => 'filling/future-quarters',
	'get/future-quarters/<additional:additional>/<quartersNumber:\d+>/<cottageNumber:[0-9]+>' => 'filling/future-quarters',
//    END MEMBERSHIP BLOCK

//    COMPLEX PAYMENT BLOCK
	'create/payment/complex/<cottageNumber:[0-9]+>' => 'payments/create-complex',
	'get-info/bill/<identificator:[0-9]+>' => 'payments/bill-info',
	'get-info/bill/<double:double>/<identificator:[0-9]+>' => 'payments/bill-info',
	'invoice/<identificator:[0-9]+>' => 'payments/print-invoice',
	'bank-invoice/<identificator:[0-9]+>' => 'payments/print-bank-invoice',
	'bank-invoice/<double:double>/<identificator:[0-9]+>' => 'payments/print-bank-invoice',
	'bank-invoice/send/<identificator:[0-9]+>' => 'payments/send-bank-invoice',
	'bank-invoice/<double:double>/send/<identificator:[0-9]+>' => 'payments/send-bank-invoice',
	'send-invoice/<identificator:[0-9]+>' => 'payments/send-invoice',
	'bill/delete/<identificator:[0-9]+>' => 'payments/delete-bill',
	'bill/delete/<double:double>/<identificator:[0-9]+>' => 'payments/delete-bill',
	'bill/save/<type:cash|no-cash>/<identificator:[0-9]+>' => 'payments/save-bill',
	'get/bills/<cottageNumber:[0-9]+>' => 'payments/get-bills',
	'get/bills/<double:double>/<cottageNumber:[0-9]+>' => 'payments/get-bills',
	'get-form/pay/<identificator:[0-9]+>' => 'payments/get-pay-confirm-form',
	'get-form/pay/<identificator:[0-9]+>/<bankTransaction:[0-9]+>' => 'payments/get-pay-confirm-form',
	'get-form/pay/<double:double>/<identificator:[0-9]+>' => 'payments/get-pay-confirm-form',
	'pay/confirm/check/<identificator:[0-9]+>' => 'payments/validate-pay-confirm',
	'pay-double/cash/check/<identificator:[0-9]+>' => 'payments/validate-cash-double',
	'pay/confirm/<identificator:[0-9]+>' => 'payments/confirm-pay',
	'pay/cash-double/confirm/' => 'payments/confirm-cash-double',
    'pay/close/<identificator:[0-9]+>' => 'payments/close',
    'pay/close/<double:double>/<identificator:[0-9]+>' => 'payments/close',
//    END COMPLEX PAYMENT BLOCK
	'get-form/change/<cottageNumber:[0-9]+>' => 'cottage/change',
//    NOTIFIER BLOCK======================================================================================
	'send/duties/<cottageNumber:[0-9]+>' => 'notify/duties',
	'send/reg-info/<cottageNumber:[0-9]+>' => 'notify/reg-info',
	'send/pay/<billId:[0-9]+>' => 'notify/pay',
	'send/pay-double/<billId:[0-9]+>' => 'notify/pay-double',
	'notifications/check-unsended' => 'notify/check-unsended',
	'errors/send' => 'notify/send-errors',

//    END NOTIFIER BLOCK==================================================================================
//    SERVICES BLOCK======================================================================================
	'service/change-counter/<cottageNumber:[0-9]+>' => 'service/change-counter',
//    END SERVICES BLOCK==================================================================================
//    BALANCE BLOCK======================================================================================
	'balance/show/<type:day-in|month-in|year-in|day-out|month-out|year-out>' => 'count/show',
	'balance/show-transactions/<type:day|month|year>' => 'count/show-transactions',
	'balance/show-summary/<type:day|month|year>' => 'count/show-summary',
//    END BALANCE BLOCK==================================================================================
	'power/cancel-previous/<cottageNumber:[0-9]+>' => 'filling/cancel-power',
	'power/cancel-previous/<additional:additional>/<cottageNumber:[0-9]+>' => 'filling/cancel-power',

	'print/cottage-report/<start:[0-9]+>/<end:[0-9]+>/<cottageNumber:[0-9]+>' => 'print/cottage-report',
	'tariff/personal/enable/<cottageNumber:[0-9]+>' => 'tariffs/make-personal',
	'tariff/personal/enable/additional/<cottageNumber:[0-9]+>' => 'tariffs/make-additional-personal',
	'tariff/personal/disable/<cottageNumber:[0-9]+>' => 'tariffs/disable-personal',
	'tariff/personal-additional/disable/<cottageNumber:[0-9]+>' => 'tariffs/disable-personal-additional',
	'tariff/personal/change/<cottageNumber:[0-9]+>' => 'tariffs/change-personal',
	'tariff/personal-additional/change/<cottageNumber:[0-9]+>' => 'tariffs/change-personal-additional',
	'show/personal-tariff/<cottageNumber:[0-9]+>' => 'tariffs/show-personal',
	'show/personal-tariff-additional/<cottageNumber:[0-9]+>' => 'tariffs/show-personal-additional',
	'show/debt/detail/<type:power|membership|target|single|power_additional|membership_additional|target_additional|single_additional>/<cottageNumber:[0-9]+>' => 'report/debt-details',
	'search' => 'search/search',
    'single/<type:delete|delete_double|edit|edit_double>/<cottageNumber:[0-9]+>/<id:[0-9]+>' => 'payments/edit-single',
    'deposit/add' => 'payments/direct-to-deposit',
    'show/all-bills' => 'payments/show-all-bills',
    'serial-payments/get-cottages' => 'filling/get-serial-cottages',
    'serial-payments/confirm' => 'filling/confirm-serial-payments',
    'mailing/get-list' => 'notify/get-mail-list',
    'mailing/<own:main|double>/<type:owner|contacter>/<cottageNumber:[0-9]+>' => 'notify/mailing',
    'chain/<billId:[0-9]+>/<transactionId:[0-9]+>' => 'payments/chain',
    'chain/confirm' => 'payments/chain-confirm',
    'chain/confirm-manual' => 'payments/chain-confirm-manual',
    //    CHECKS ==================================================================================
    'check/individual' => 'checks/individual',
    'individual/fill' => 'filling/fill-missing-individuals',
    'change/transaction-time' => 'payments/change-transaction-date',
    'fines/count/<cottageNumber:[0-9]+(-a)?>' => 'payments/count-fines',
    'bill/reopen/<billId:[0-9]+>' => 'payments/bill-reopen',
//    FINES ==================================================================================
    'fines/<action:enable|disable>/<finesId:[0-9]+>' => 'fines/change',
    'transaction/change-date/<id:[0-9]+(-a)?>' => 'payments/change-transaction-date',
    'transaction/change-date' => 'payments/change-transaction-date',
    'pay/confirm' => 'payments/confirm-payment',
    'counter/discard-change/<cottageNumber:[0-9]+(-a)?>/<month:[0-9]+-[0-9]+>' => 'filling/discard-counter-change'
];