<?php


namespace app\models\tables;


use yii\db\ActiveRecord;

/**
 * @property int $fines_id [bigint(20) unsigned]  Идентификатор пени
 * @property int $bill_id [int(10) unsigned]  Идентификатор платежа
 * @property string $start_summ [double unsigned]  Стоимость пени, включенная в счёт
 * @property string $pay_type [enum('membership', 'power', 'target')]  Тип взноса
 * @property string $summ [double unsigned]  Начисленная сумма
 * @property string $payed_summ [double unsigned]  Оплаченная сумма
 * @property string $period [varchar(7)]  Период оплаты
 * @property int $start_days [int(10) unsigned]  Дней оплаты
 */

class Table_view_fines_info extends ActiveRecord
{
    public static function tableName()
    {
        return 'view_fines_info';
    }
}