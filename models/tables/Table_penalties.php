<?php

namespace app\models\tables;

use yii\db\ActiveRecord;
/**
 * @property int $id [bigint(20) unsigned]  Глобальный идентификатор
 * @property string $cottage_number [varchar(10)]  Номер участка
 * @property string $pay_type [enum('membership', 'power', 'target')]  Тип взноса
 * @property string $period [varchar(7)]  Период оплаты
 * @property int $payUpLimit [bigint(20)]  Крайняя дата оплаты
 * @property int $summ [bigint(20)]  Начисленная сумма
 * @property int $payed_summ [bigint(20)]  Оплаченная сумма
 * @property bool $is_partial_payed [tinyint(1)]  Чатично оплачено
 * @property bool $is_full_payed [tinyint(1)]  Полностью оплачено
 * @property bool $is_enabled [tinyint(1)]  Активность пени
 */

class Table_penalties extends ActiveRecord
{
    /**
     * @return string
     */
    public static function tableName() : string
    {
        return 'penalties';
    }
}