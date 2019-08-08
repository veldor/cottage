<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 02.12.2018
 * Time: 12:56
 */

namespace app\models;


use yii\db\ActiveRecord;

/**
 * Class Table_additional_payed_target
 * @package app\models
 * @property int $id [int(10) unsigned]
 * @property int $billId [int(10) unsigned]
 * @property int $cottageId [int(10) unsigned]
 * @property int $year [int(4) unsigned]
 * @property string $summ [float unsigned]
 * @property int $paymentDate [int(20) unsigned]
 * @property int $transactionId [int(10) unsigned]
 */

class Table_additional_payed_target extends ActiveRecord
{
    public static function tableName()
    {
        return 'additional_payed_target';
    }
}