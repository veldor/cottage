<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 19.12.2018
 * Time: 13:50
 */

namespace app\validators;

use app\models\CashHandler;
use yii\base\InvalidArgumentException;
use yii\base\Model;
use yii\validators\Validator;

class CashValidator extends Validator{

	public function validateAttribute($model, $attribute)
	{
		try {
			$model->$attribute = CashHandler::toRubles($model->$attribute);
		} catch (InvalidArgumentException $e) {
            /** @var Model $model */
            $model->addError($attribute, $e->getMessage());
		}
	}
}