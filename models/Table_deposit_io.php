<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 06.11.2018
 * Time: 12:34
 */

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int cottageNumber
 * @property int $id [int(10) unsigned]
 * @property int $billId [int(10) unsigned]
 * @property string $destination [enum('in', 'out')]
 * @property string $summ [float unsigned]
 * @property string $summBefore [float unsigned]
 * @property string $summAfter [float unsigned]
 * @property int $actionDate [int(20) unsigned]
 * @property int $transactionId [int(10) unsigned]
 */
class Table_deposit_io extends ActiveRecord
{
    /**
     * Class Table_deposit_io
     * @package app\models
     */
    public static function tableName():string
    {
        return 'deposit_io';
    }
}