<?php


namespace app\models\tables;

use yii\db\ActiveRecord;

/**
 * @property int $id [bigint(20) unsigned]  Глобальный идентификатор
 * @property int $fine_id [bigint(20) unsigned]  Идентификатор пени
 * @property int $transaction_id [int(10) unsigned]  Идентификатор транзакции
 * @property int $pay_date [bigint(20) unsigned]  Дата платежа
 * @property string $summ [double unsigned]  Сумма оплаты
 * @property int $transactionId [int(10) unsigned]
 */

class Table_payed_fines extends ActiveRecord
{
    public static function tableName()
    {
        return 'payed_fines';
    }
}