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
 * Class Table_tariffs_power
 * @package app\models
 * @property int $id [int(5) unsigned]
 * @property string $targetMonth [varchar(20)]  Месяц для расчёта суммы платежа
 * @property int $powerLimit [int(5) unsigned]  Порог потребления электроэнергии
 * @property string $powerCost [float unsigned]  Цена электроэнергии до порога
 * @property string $powerOvercost [float unsigned]  Цена электроэнергии при перерасходе
 * @property int $searchTimestamp [int(20) unsigned]  Временная метка для поиска дат
 * @property string $fullSumm [float unsigned]  Сумма начисленная за месяц
 * @property string $payedSumm [float unsigned]  Фактически заплаченная сумма
 * @property string $paymentInfo Подробная информация об оплате
 */

class Table_tariffs_power extends ActiveRecord
{

    public static function tableName():string
    {
        return 'tariffs_power';
    }

    public function rules():array
    {
        return [
            [['id', 'targetMonth', 'powerLimit', 'powerCost', 'powerOvercost'], 'safe'],
        ];
    }

    public function attributeLabels():array
    {
        return [
          'powerLimit' => 'Порог потребления электроэнергии',
          'powerCost' => 'Льготная ставка',
          'powerOvercost' => 'Обычная ставка',
        ];
    }
}