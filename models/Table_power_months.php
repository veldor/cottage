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
 * Class Table_power_months
 * @package app\models
 * @property int $id [int(6) unsigned]
 * @property int $cottageNumber [int(5) unsigned]
 * @property string $month [varchar(20)]
 * @property int $fillingDate [int(20) unsigned]
 * @property int $oldPowerData [int(10) unsigned]
 * @property int $newPowerData [int(10) unsigned]
 * @property int $searchTimestamp [int(20) unsigned]
 * @property string $payed [enum('yes', 'no')]
 * @property int $difference [int(10) unsigned]
 * @property float $totalPay [float unsigned]
 * @property int $inLimitSumm [int(10) unsigned]
 * @property int $overLimitSumm [int(10) unsigned]
 * @property float $inLimitPay [float unsigned]
 * @property float $overLimitPay [float unsigned]
 */

class Table_power_months extends ActiveRecord
{
    public static function tableName():string
    {
        return 'months_power';
    }

    /**
     * @param Table_cottages $cottage
     * @return Table_power_months
     */
    public static function getLastFilled(Table_cottages $cottage): Table_power_months
    {
        return self::find()->where(['cottageNumber' => $cottage->cottageNumber])->orderBy('month DESC')->one();
    }

    /**
     * @param Table_cottages $cottageInfo
     * @return Table_power_months[]
     */
    public static function getAllData(Table_cottages $cottageInfo): array
    {
        return self::findAll(['cottageNumber' => $cottageInfo->cottageNumber]);
    }
}