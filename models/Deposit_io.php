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
 * @property int $id [bigint(20) unsigned]  Глобальный идентификатор
 * @property int $cottageNumber [int(10) unsigned]
 * @property int $billId [int(10) unsigned]
 * @property string $destination [enum('in', 'out')]
 * @property string $summ [float unsigned]
 * @property string $summBefore [float unsigned]
 * @property string $summAfter [float unsigned]
 * @property int $actionDate [int(20) unsigned]
 * @property int $transactionId [int(10) unsigned]
 */

class Deposit_io extends ActiveRecord
{

}