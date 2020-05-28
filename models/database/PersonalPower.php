<?php


namespace app\models\database;


use yii\db\ActiveRecord;

/**
 * Class Mailing
 * @package app\models\database
 *
 * @property int $id [int(10) unsigned]
 * @property string $month [varchar(7)]
 * @property string $cottage_number [varchar(10)]
 * @property string $cost [float unsigned]
 * @property string $over_cost [float unsigned]
 * @property int $limit [int(10) unsigned]
 * @property string $fixed_amount [float unsigned]
 */

class PersonalPower extends ActiveRecord
{

    public static function tableName():string
    {
        return 'personal_power';
    }
}