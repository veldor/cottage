<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 19.12.2018
 * Time: 14:31
 */

namespace app\validators;


use app\models\TimeHandler;
use yii\base\InvalidArgumentException;
use yii\validators\Validator;

class CheckQuarterValidator extends Validator{
	public function validateAttribute($model, $attribute)
	{
		try {
			$quarter = TimeHandler::isQuarter($model->$attribute);
			$model->$attribute = $quarter['full'];
		} catch (InvalidArgumentException $e) {
			$model->addError($attribute, $e->getMessage());
		}
	}
}