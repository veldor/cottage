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
 * Class Table_tariffs_membership
 * @package app\models
 * @property string $quarter [varchar(8)]
 * @property string $fixed_part [float unsigned]
 * @property string $changed_part [float unsigned]
 * @property int $search_timestamp [int(20) unsigned]
 * @property string $fullSumm [float unsigned]
 * @property string $payedSumm [float unsigned]
 * @property string $paymentInfo
 */

class Table_tariffs_membership extends ActiveRecord
{
    public static function tableName()
    {
        return 'tariffs_membership';
    }
}