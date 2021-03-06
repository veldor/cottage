<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 17.09.2018
 * Time: 19:18
 */

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Class Table_tariffs_target
 * @package app\models
 * @property int $year [int(4) unsigned]  Год регистрации целевого платежа
 * @property string $fixed_part [float unsigned]  Фиксированная часть оплаты
 * @property string $float_part [float unsigned]  Стоимость платежа с сотки
 * @property string $description Цели платежа
 * @property string $fullSumm [float unsigned]  Расчёт полной суммы платежей с садоводства
 * @property string $payedSumm [float unsigned]  Сумма оплаченных счетов
 * @property string $paymentInfo Полная информация по платежам
 * @property int $payUpTime [int(11)]  Срок оплаты
 */

class Table_tariffs_target extends ActiveRecord
{
    public static function tableName():string
    {
        return 'tariffs_target';
    }

    /**
     * Верну массив тарифов с ключами- годами оплаты
     * @return array
     */
    public static function getArrayTariffs(): array
    {
        $answer = [];
        $tariffs = self::find()->all();
        if(!empty($tariffs)){
            foreach ($tariffs as $tariff) {
                $answer[$tariff->year] = $tariff;
            }
        }
        return $answer;
    }
}