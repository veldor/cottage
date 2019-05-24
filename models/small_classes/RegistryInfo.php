<?php


namespace app\models\small_classes;


use yii\base\Model;

class RegistryInfo extends Model
{
    /**
     * @var string
     * Наименование поля Обязательное Тип поля Маска
     *
     * Дата платежа ДА числовой ДД-ММ-ГГГГ
     */
    public $payDate;
    /**
     * @var string
     * Время платежа ДА числовой 00-00-00
     */
    public $payTime;
    /**
     * @var string
     * Номер отделения ДА числовой
     */
    public $departmentNumber;
    /**
     * @var string
     * Номер кассира/УС/СБОЛ ДА числовой
     */
    public $handlerNumber;
    /**
     * @var string
     * Уникальный код операции в ЕПС ДА числовой
     */
    public $sberBillId;
    /**
     * @var string
     * Лицевой счет ДА текстовый
     */
    public $personalAcc;
    /**
     * @var string
     * Фамилия, Имя, Отчество ДА текстовый Фамилия, Имя, Отчество
     */
    public $fio;
    /**
     * @var string
     * Адрес ДА текстовый Населенный пункт, улица,
    номер дома, номер квартиры
     */
    public $address;
    /**
     * @var string
     * Период оплаты ДА текстовый ММГГ
     */
    public $period;
    /**
     * @var string
     * Сумма операции ДА цифровой 999999
     */
    public $operationSumm;
    /**
     * @var string
     * Сумма перевода ДА цифровой 999999
     */
    public $transactionSumm;
    /**
     * @var string
     * Сумма комиссии банку ДА цифровой 999999
     */
    public $commissionSumm;

    public function rules(): array
    {
        return [
            [['payDate', 'payTime', 'departmentNumber', 'handlerNumber', 'sberBillId', 'personalAcc', 'operationSumm', 'transactionSumm', 'commissionSumm'], 'required'],
        ];
    }
}