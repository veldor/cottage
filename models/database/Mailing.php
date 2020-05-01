<?php


namespace app\models\database;


use yii\db\ActiveRecord;

/**
 * Class Mailing
 * @package app\models\database
 *
 * @property int $id [int(10) unsigned]
 * @property string $title
 * @property string $body
 * @property int $mailing_time [bigint(20)]  Время рассылки
 * @property string $mails_info
 */

class Mailing extends ActiveRecord
{

    public static function tableName():string
    {
        return 'mailings';
    }
}