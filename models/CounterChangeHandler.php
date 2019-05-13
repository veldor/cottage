<?php


namespace app\models;


use yii\base\Model;

class CounterChangeHandler extends Model
{

    /**
     * @param $globalInfo Table_cottages
     */
    public static function checkChange($globalInfo)
    {
        return Table_counter_changes::find()->where(['cottageNumber' => $globalInfo->cottageNumber, 'changeMonth' => TimeHandler::getCurrentShortMonth()])->orWhere(['cottageNumber' => $globalInfo->cottageNumber, 'changeMonth' => TimeHandler::getPreviousShortMonth()])->orderBy('change_time')->one();
    }
}