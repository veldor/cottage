<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 06.11.2018
 * Time: 13:22
 */

namespace app\models;

use yii\db\ActiveRecord;

class Balance_table extends ActiveRecord
{
    public static function tableName()
    {
        return 'balance';
    }
}