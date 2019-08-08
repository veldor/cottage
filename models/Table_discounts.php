<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 06.11.2018
 * Time: 12:45
 */

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Class Table_discounts
 * @package app\models
 * @property int $id [int(10) unsigned]
 * @property int $billId [int(10) unsigned]
 * @property int $cottageNumber [int(10) unsigned]
 * @property string $summ [float unsigned]
 * @property string $reason
 * @property int $actionDate [int(20) unsigned]
 * @property int $transactionId [int(10) unsigned]
 */

class Table_discounts extends ActiveRecord
{
	public static function tableName():string
	{
		return 'discounts';
	}
}