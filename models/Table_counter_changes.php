<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 11.11.2018
 * Time: 12:03
 */

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Class Table_counter_changes
 * @package app\models
 * @property int $id [int(10) unsigned]
 * @property int $cottageNumber [int(10) unsigned]
 * @property int $oldCounterStartData [int(10) unsigned]
 * @property int $oldCounterNewData [int(10) unsigned]
 * @property int $newCounterData [int(10) unsigned]
 * @property int $change_time [int(20) unsigned]
 * @property int $newCounterFinishData [int(20) unsigned]
 * @property string $changeMonth [varchar(7)]
 * @property int $newCounterStartData [int(10) unsigned]
 */

class Table_counter_changes extends ActiveRecord
{
    public static function tableName()
    {
        return 'counter_changes';
    }

}