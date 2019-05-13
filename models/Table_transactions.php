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
 * Class Table_transactions
 * @package app\models
 * @property int $id [int(10) unsigned]
 * @property int $cottageNumber [int(5) unsigned]
 * @property int $billId [int(10) unsigned]
 * @property int $transactionDate [int(20) unsigned]
 * @property string $transactionType [enum('cash', 'no-cash')]
 * @property string $transactionSumm [float unsigned]
 * @property string $transactionWay [enum('in', 'out')]
 * @property string $transactionReason
 */

class Table_transactions extends ActiveRecord
{
    public static function tableName()
    {
        return 'transactions';
    }
}