<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 19.12.2018
 * Time: 13:50
 */

namespace app\validators;

use app\models\AdditionalCottage;
use app\models\Cottage;
use app\models\Table_cottages;
use yii\validators\Validator;

class CheckCottageNoRegistred extends Validator {

	public function validateAttribute($model, $attribute)
	{
		if (!$model->currentCondition = (isset($model->additional) && $model->additional) ? AdditionalCottage::getCottage($model->$attribute) : Cottage::getCottageInfo($model->$attribute)) {
			$model->addError($attribute, 'Участок с данным номером не существует');
		}
	}
}