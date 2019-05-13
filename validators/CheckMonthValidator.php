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
use yii\db\ActiveRecord;
use yii\validators\Validator;

class CheckMonthValidator extends Validator{
	/**
	 * @param $model ActiveRecord
	 * @param $attribute
	 */
	public function validateAttribute($model, $attribute)
	{
		try {
		    if(!empty($model->$attribute)){
                $month = TimeHandler::isMonth($model->$attribute);
                $model->$attribute = $month['full'];
            }
            else{
		        $model->$attribute = TimeHandler::getPreviousShortMonth();
            }
		} catch (InvalidArgumentException $e) {
			$model->addError($attribute, $e->getMessage());
		}
	}
}