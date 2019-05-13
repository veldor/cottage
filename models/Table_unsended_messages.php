<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 16.11.2018
 * Time: 9:52
 */

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Class Table_unsended_messages
 * @package app\models
 * @property int $id [int(5) unsigned]
 * @property int $cottageNumber [int(10) unsigned]
 * @property string $subject
 * @property string $body
 */

class Table_unsended_messages extends ActiveRecord
{
    public static function tableName()
    {
        return 'unsended_messages';
    }
}