<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 17.09.2018
 * Time: 19:18
 */

namespace app\models;

/**
 * Class Table_payment_bills_double
 * @package app\models
 * @property int $id [int(10) unsigned]
 * @property int $cottageNumber [int(5) unsigned]
 * @property string $bill_content
 * @property bool $isPayed [tinyint(1)]
 * @property int $creationTime [int(20) unsigned]
 * @property int $paymentTime [int(20) unsigned]
 * @property string $depositUsed [float unsigned]
 * @property string $totalSumm [float unsigned]
 * @property string $payedSumm [float unsigned]
 * @property string $discount [float unsigned]
 * @property string $discountReason
 * @property string $toDeposit [float unsigned]
 * @property bool $isPartialPayed [tinyint(1)]
 * @property bool $isMessageSend [tinyint(1)]  Уведомление отправлено
 * @property bool $isInvoicePrinted [tinyint(1)]  Квитанция распечатана
 * @property string $payer_personals [varchar(255)]  Имя плательщика
 */

class Table_payment_bills_double extends Table_payment_bills
{
    public static function tableName():string
    {
        return 'payment_bills_double';
    }
}