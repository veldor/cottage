<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 19.12.2018
 * Time: 14:31
 */

namespace app\validators;

use yii\validators\Validator;

class CheckPhoneNumberValidator extends Validator{
	public function validateAttribute($model, $attribute)
	{
		$regex = '/[\D]/';
		$result = preg_replace($regex, '', $model->$attribute);
		$len = strlen($result);
		if ($len === 7) {
			$model->$attribute = '+7 831 ' . substr($result, 0, 3) . '-' . substr($result, 3, 2) . '-' . substr($result, 5, 2);
		} elseif ($len === 10) {
			$model->$attribute = '+7 ' . substr($result, 0, 3) . ' ' . substr($result, 3, 3) . '-' . substr($result, 6, 2) . '-' . substr($result, 8, 2);
		} elseif ($len === 11) {
			$model->$attribute = '+7 ' . substr($result, 1, 3) . ' ' . substr($result, 4, 3) . '-' . substr($result, 7, 2) . '-' . substr($result, 9, 2);
		} else {
			$model->addError($attribute, 'Проверьте номер телефона, что-то не так.');
		}
	}
}