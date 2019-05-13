<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 19.12.2018
 * Time: 14:45
 */

namespace app\validators;


use app\models\TargetHandler;
use yii\validators\Validator;

class CheckTargetValidator extends Validator {
	public function validateAttribute($model, $attribute)
	{
		// разберу массив целевых взносов
		$tariffs = TargetHandler::getCurrentRates();
		if (count($tariffs) !== count($model->target)) {
			$model->addError($attribute, 'Заполнены не все данные');
		} elseif (empty($model->cottageSquare)) {
			$model->addError($attribute, 'Не заполнена площадь участка');
		}
	}
}