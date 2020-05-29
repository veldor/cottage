<?php


namespace app\models\database;


use yii\db\ActiveRecord;

/**
 * Class Mailing
 * @package app\models\database
 *
 * @property int $id [int(10) unsigned]
 * @property string $body
 * @property string $subject [varchar(255)]
 */

class SingleMail extends ActiveRecord
{

    public static function tableName():string
    {
        return 'single_mail';
    }
}