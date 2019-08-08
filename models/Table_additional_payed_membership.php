<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 02.12.2018
 * Time: 12:55
 */

namespace app\models;


use yii\db\ActiveRecord;

/**
 * Class Table_additional_payed_membership
 * @package app\models
 * @property int $id [int(10) unsigned]
 * @property int $cottageId [int(10) unsigned]
 * @property int $billId [int(10) unsigned]
 * @property string $quarter [varchar(10)]
 * @property string $summ [float unsigned]
 * @property int $paymentDate [int(20) unsigned]
 * @property int $transactionId [int(10) unsigned]
 */

class Table_additional_payed_membership extends ActiveRecord
{
    public static function tableName()
    {
        return 'additional_payed_membership';
    }
}