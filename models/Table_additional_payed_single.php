<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 02.12.2018
 * Time: 12:57
 */

namespace app\models;


use yii\db\ActiveRecord;

/**
 * Class Table_additional_payed_single
 * @package app\models
 */

class Table_additional_payed_single extends ActiveRecord
{
    public static function tableName()
    {
        return 'additional_payed_single';
    }
}