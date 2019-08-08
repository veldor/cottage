<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 13.12.2018
 * Time: 8:54
 */

namespace app\models;


use yii\db\ActiveRecord;

/**
 * Class Table_bank_invoices
 * @package app\models
 *
 * @property int $bank_operation_id [int(10) unsigned]  Уникальный код операции в
 * @property string $pay_date [char(10)]  Дата платежа
 * @property string $pay_time [char(8)]  Время платежа
 * @property string $filial_number [varchar(100)]  Номер отделения
 * @property string $handler_number [varchar(100)]  Номер кассира/УС/СБОЛ
 * @property string $account_number [varchar(100)]  Лицевой счет
 * @property string $fio [varchar(200)]  Фамилия, Имя, Отчество
 * @property string $address [varchar(200)]  Адрес
 * @property string $payment_period [varchar(100)]  Период оплаты
 * @property string $payment_summ [varchar(100)]  Сумма операции
 * @property string $transaction_summ [varchar(100)]  Сумма перевода
 * @property string $commission_summ [varchar(100)]  Сумма комиссии банку
 * @property int $bounded_bill_id [int(11)]  Идентификатор платежа в системе
 * @property bool $bounded_bill_is_double [tinyint(1)]  Тип участка
 * @property string $real_pay_date [char(10)]  Истинная дата платежа
 */

class Table_bank_invoices extends ActiveRecord
{
    public static function tableName()
    {
        return 'bank_invoices';
    }
}