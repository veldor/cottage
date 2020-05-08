<?php


namespace app\models\database;


use yii\db\ActiveRecord;

/**
 * Class Mailing
 * @package app\models\database
 *
 * @property int $id [int(10) unsigned]
 * @property string $cottage_number [varchar(10)]
 * @property int $start [bigint(20)]
 * @property int $finish [bigint(20)]
 */

class CottageReport extends ActiveRecord
{

    public static function tableName():string
    {
        return 'cottage_report';
    }
}