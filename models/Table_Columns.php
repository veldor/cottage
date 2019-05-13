<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 06.01.2019
 * Time: 15:32
 */

namespace app\models;


use yii\db\ActiveRecord;

class Table_Columns extends ActiveRecord {
	public static function tableName()
	{
		return 'INFORMATION_SCHEMA.COLUMNS';
	}
}