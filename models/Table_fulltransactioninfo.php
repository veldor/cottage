<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 05.12.2018
 * Time: 8:26
 */

namespace app\models;


use yii\db\ActiveRecord;

/**
 * Class Table_fulltransactioninfo
 * @package app\models
 * @property int $cottage_number [int(5) unsigned]
 * @property int $bill_id [int(10) unsigned]
 * @property string $bill_content
 * @property string $discount [float unsigned]
 * @property string $depositUsed [float unsigned]
 * @property string $toDeposit [float unsigned]
 * @property string $payedSumm [float unsigned]
 * @property int $transactionDate [int(20) unsigned]
 * @property string $transactionType [enum('cash', 'no-cash')]
 * @property string $transactionWay [enum('in', 'out')]
 * @property bool $partialPayed [tinyint(4)]
 * @property string $transactionSumm [float unsigned]
 * @property string $billCast Слепок счёта на момент транзакции
 * @property float $usedDeposit [double]  Использованный депозит
 * @property float $gainedDeposit [double]  Зачислено на депозит
 * @property bool $partial [tinyint(1)]
 * @property int $transactionId [int(10) unsigned]
 */

class Table_fulltransactioninfo extends ActiveRecord
{
    public static function tableName()
    {
        return 'fulltransactioninfo';
    }
}