<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 18.09.2018
 * Time: 23:04
 */

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Class Table_transactions_double
 * @package app\models
 * @property int $id [int(10) unsigned]
 * @property int $cottageNumber [int(5) unsigned]
 * @property int $billId [int(10) unsigned]
 * @property int $transactionDate [int(20) unsigned]
 * @property string $transactionType [enum('cash', 'no-cash')]
 * @property string $transactionSumm [float unsigned]
 * @property string $transactionWay [enum('in', 'out')]
 * @property string $transactionReason
 * @property string $billCast Слепок счёта на момент транзакции
 * @property float $usedDeposit [double]  Использованный депозит
 * @property float $gainedDeposit [double]  Зачислено на депозит
 * @property bool $partial [tinyint(1)]
 * @property int $payDate [int(10) unsigned]  Дата проведения платежа
 * @property int $bankDate [int(10) unsigned]  Дата поступления средств на счёт
 */

class Table_transactions_double extends ActiveRecord
{
    public static function tableName()
    {
        return 'transactions_double';
    }
}