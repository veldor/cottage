<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 02.12.2018
 * Time: 12:53
 */

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Class Table_payed_power
 * @package app\models
 * @property int $id [int(10) unsigned]
 * @property int $billId [int(10) unsigned]
 * @property int $cottageId [int(10) unsigned]
 * @property string $month [varchar(10)]
 * @property float $summ [float unsigned]
 * @property int $paymentDate [int(20) unsigned]
 * @property int $transactionId [int(10) unsigned]  Идентификатор транзакции
 */

class Table_payed_power extends ActiveRecord
{
    public static function tableName():string
    {
        return 'payed_power';
    }

    /**
     * Проверю оплату данного периода
     * @param Table_power_months $powerData <p>Экземпляр сведений об потраченной электроэнергии</p>
     * @return int <p>Верну количество оплат</p>
     */
    public static function isPayed(Table_power_months $powerData): int
    {
        return self::find()->where(['cottageId' => $powerData->cottageNumber, 'month' => $powerData->month])->count();
    }

    /**
     * Получу все платежи по данному месяцу
     * @param Table_power_months $dutyItem
     * @return Table_payed_power[]|null
     */
    public static function getPayed(Table_power_months $dutyItem): ?array
    {
        if($dutyItem !== null){
            return self::findAll(['month' => $dutyItem->month, 'cottageId' => $dutyItem->cottageNumber]);
        }
        return null;
    }
}