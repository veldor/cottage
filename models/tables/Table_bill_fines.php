<?php


namespace app\models\tables;


use yii\db\ActiveRecord;

/**
 * @property int $id [bigint(20) unsigned]  Глобальный идентификатор
 * @property int $bill_id [int(10) unsigned]  Идентификатор платежа
 * @property int $fines_id [bigint(20) unsigned]  Идентификатор пени
 * @property float $start_summ [double unsigned]  Стоимость пени, включенная в счёт
 * @property int $start_days [int(10) unsigned]  Дней оплаты
 * @property bool $is_double [tinyint(1)]  Дополнительный участок
 */

class Table_bill_fines extends ActiveRecord
{
    public static function tableName():string
    {
        return 'bill_fines';
    }
}