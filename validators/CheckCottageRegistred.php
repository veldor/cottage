<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 19.12.2018
 * Time: 13:50
 */

namespace app\validators;

use app\models\Table_cottages;
use yii\validators\Validator;

class CheckCottageRegistred extends Validator {

	public function validateAttribute($model, $attribute)
	{
		if (Table_cottages::find()->where(['cottageNumber' => $model->$attribute])->count()) {
			$model->addError($attribute, 'Участок с данным номером уже существует');
		}
	}
}